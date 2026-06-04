(function () {
    'use strict';

    var config = window.pmrReservations || {};
    var ajaxUrl = config.ajaxUrl || '';
    var i18n = config.i18n || {};

    function text(key, fallback) {
        return i18n[key] || fallback;
    }

    function setMessage(element, message, type) {
        if (!element) {
            return;
        }

        element.textContent = message || '';
        element.classList.remove('pmr-message--success', 'pmr-message--error', 'pmr-message--warning');

        if (type) {
            element.classList.add('pmr-message--' + type);
        }
    }

    function bodyFrom(data) {
        if (data instanceof FormData) {
            return new URLSearchParams(data);
        }

        return new URLSearchParams(data || {});
    }

    function request(data) {
        return fetch(ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: bodyFrom(data)
        })
            .then(function (response) {
                return response.json().catch(function () {
                    return null;
                });
            })
            .then(function (json) {
                if (!json || !json.success) {
                    var message = json && json.data && json.data.message ? json.data.message : text('error', 'Ha ocurrido un error. Inténtalo de nuevo.');
                    throw new Error(message);
                }

                return json.data || {};
            });
    }

    function initQuantities(scope) {
        scope.querySelectorAll('[data-pmr-quantity]').forEach(function (quantity) {
            var input = quantity.querySelector('input[type="number"]');
            var minus = quantity.querySelector('[data-pmr-quantity-minus]');
            var plus = quantity.querySelector('[data-pmr-quantity-plus]');

            if (!input) {
                return;
            }

            function clamp(value) {
                var min = parseInt(input.getAttribute('min') || '1', 10);
                var max = parseInt(input.getAttribute('max') || '50', 10);
                var next = parseInt(value || min, 10);

                if (Number.isNaN(next)) {
                    next = min;
                }

                return Math.min(max, Math.max(min, next));
            }

            function update(value) {
                input.value = clamp(value);
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }

            if (minus) {
                minus.addEventListener('click', function () {
                    update(parseInt(input.value || '1', 10) - 1);
                });
            }

            if (plus) {
                plus.addEventListener('click', function () {
                    update(parseInt(input.value || '1', 10) + 1);
                });
            }

            input.addEventListener('input', function () {
                input.value = clamp(input.value);
            });
        });
    }

    function initPublicForms() {
        document.querySelectorAll('[data-pmr-public-form]').forEach(function (form) {
            var message = form.querySelector('[data-pmr-message]');
            var submit = form.querySelector('button[type="submit"]');

            initQuantities(form);

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                    return;
                }

                var originalContent = submit ? submit.innerHTML : '';
                var formData = new FormData(form);
                formData.append('action', 'pmr_submit_reservation');

                if (submit) {
                    submit.disabled = true;
                    submit.textContent = text('sending', 'Enviando...');
                }

                setMessage(message, text('sending', 'Enviando...'), 'warning');

                request(formData)
                    .then(function (data) {
                        form.reset();
                        form.querySelectorAll('[data-pmr-quantity] input[type="number"]').forEach(function (input) {
                            input.value = input.getAttribute('min') || '1';
                        });
                        setMessage(message, data.message || 'Reserva recibida correctamente.', 'success');
                    })
                    .catch(function (error) {
                        setMessage(message, error.message, 'error');
                    })
                    .finally(function () {
                        if (submit) {
                            submit.disabled = false;
                            submit.innerHTML = originalContent || text('submit', 'Enviar reserva');
                        }
                    });
            });
        });
    }

    function initLoginForms() {
        document.querySelectorAll('[data-pmr-login-form]').forEach(function (form) {
            var message = form.querySelector('[data-pmr-login-message]');
            var submit = form.querySelector('button[type="submit"]');

            form.addEventListener('submit', function (event) {
                event.preventDefault();

                if (typeof form.reportValidity === 'function' && !form.reportValidity()) {
                    return;
                }

                var originalContent = submit ? submit.innerHTML : '';
                var formData = new FormData(form);
                formData.append('action', 'pmr_admin_login');

                if (submit) {
                    submit.disabled = true;
                    submit.textContent = text('sending', 'Enviando...');
                }

                setMessage(message, text('sending', 'Enviando...'), 'warning');

                request(formData)
                    .then(function () {
                        window.location.reload();
                    })
                    .catch(function (error) {
                        setMessage(message, error.message, 'error');
                    })
                    .finally(function () {
                        if (submit) {
                            submit.disabled = false;
                            submit.innerHTML = originalContent || text('login', 'Acceder');
                        }
                    });
            });
        });
    }

    function initAdminPanels() {
        document.querySelectorAll('[data-pmr-admin]').forEach(function (panel) {
            var nonce = panel.getAttribute('data-nonce') || '';
            var refreshInterval = parseInt(panel.getAttribute('data-refresh-interval') || '30', 10);
            var table = panel.querySelector('[data-pmr-admin-table]');
            var message = panel.querySelector('[data-pmr-admin-message]');
            var searchFilter = panel.querySelector('[data-pmr-filter-search]');
            var refreshButton = panel.querySelector('[data-pmr-refresh]');
            var clearButton = panel.querySelector('[data-pmr-clear-filters]');
            var logoutButton = panel.querySelector('[data-pmr-logout]');
            var lastUpdated = panel.querySelector('[data-pmr-last-updated]');
            var searchTimer = null;

            function currentFilters() {
                return {
                    search: searchFilter ? searchFilter.value.trim() : ''
                };
            }

            function updateLastUpdated() {
                if (!lastUpdated) {
                    return;
                }

                var time = new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
                lastUpdated.textContent = text('updatedAt', 'Actualizado a las') + ' ' + time;
            }

            function loadReservations(silent) {
                if (!table) {
                    return Promise.resolve();
                }

                if (!silent) {
                    setMessage(message, text('loading', 'Cargando reservas...'), 'warning');
                }

                table.classList.add('is-loading');
                if (refreshButton) {
                    refreshButton.disabled = true;
                }

                var filters = currentFilters();

                return request({
                    action: 'pmr_admin_list_reservations',
                    nonce: nonce,
                    search: filters.search
                })
                    .then(function (data) {
                        table.innerHTML = data.html || '';
                        updateLastUpdated();
                        if (!silent) {
                            setMessage(message, '', null);
                        }
                    })
                    .catch(function (error) {
                        setMessage(message, error.message, 'error');
                    })
                    .finally(function () {
                        table.classList.remove('is-loading');
                        if (refreshButton) {
                            refreshButton.disabled = false;
                        }
                    });
            }

            function updateReservation(id, status) {
                setMessage(message, text('loading', 'Cargando reservas...'), 'warning');

                return request({
                    action: 'pmr_admin_update_reservation',
                    nonce: nonce,
                    reservation_id: id,
                    status: status
                })
                    .then(function () {
                        return loadReservations(true);
                    })
                    .then(function () {
                        setMessage(message, '', null);
                    })
                    .catch(function (error) {
                        setMessage(message, error.message, 'error');
                    });
            }

            if (searchFilter) {
                searchFilter.addEventListener('input', function () {
                    window.clearTimeout(searchTimer);
                    searchTimer = window.setTimeout(function () {
                        loadReservations(false);
                    }, 300);
                });
            }

            if (refreshButton) {
                refreshButton.addEventListener('click', function () {
                    loadReservations(false);
                });
            }

            if (clearButton) {
                clearButton.addEventListener('click', function () {
                    if (searchFilter) {
                        searchFilter.value = '';
                    }
                    loadReservations(false);
                });
            }

            if (table) {
                table.addEventListener('click', function (event) {
                    var button = event.target.closest('[data-pmr-action]');

                    if (!button) {
                        return;
                    }

                    var action = button.getAttribute('data-pmr-action');
                    var id = button.getAttribute('data-id');

                    if (action === 'status') {
                        updateReservation(id, button.getAttribute('data-status'));
                    }
                });
            }

            if (logoutButton) {
                logoutButton.addEventListener('click', function () {
                    if (!window.confirm(text('logoutConfirm', '¿Cerrar sesión del panel privado?'))) {
                        return;
                    }

                    request({
                        action: 'pmr_admin_logout',
                        nonce: nonce
                    }).finally(function () {
                        window.location.reload();
                    });
                });
            }

            if (!Number.isNaN(refreshInterval) && refreshInterval >= 5) {
                window.setInterval(function () {
                    if (!document.hidden) {
                        loadReservations(true);
                    }
                }, refreshInterval * 1000);
            }
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        initPublicForms();
        initLoginForms();
        initAdminPanels();
    });
})();
