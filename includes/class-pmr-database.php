<?php

if (! defined('ABSPATH')) {
    exit;
}

final class PMR_Database
{
    private const TABLE_SUFFIX = 'pmr_reservations';

    public static function table_name(): string
    {
        global $wpdb;

        return $wpdb->prefix . self::TABLE_SUFFIX;
    }

    public static function create_table(): void
    {
        global $wpdb;

        $table_name = self::table_name();
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE {$table_name} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            reference varchar(20) NOT NULL,
            pickup_date date NOT NULL,
            basket_count int(10) unsigned NOT NULL DEFAULT 1,
            observations text NULL,
            full_name varchar(190) NOT NULL,
            email varchar(190) NOT NULL,
            phone varchar(60) NOT NULL,
            rgpd_consent tinyint(1) NOT NULL DEFAULT 0,
            marketing_consent tinyint(1) NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'pending',
            ip_address varchar(45) NULL,
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY reference (reference),
            KEY pickup_date (pickup_date),
            KEY status (status),
            KEY created_at (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);

        update_option('pmr_db_version', PMR_DB_VERSION);
    }

    public static function valid_statuses(): array
    {
        return ['pending', 'completed', 'cancelled'];
    }

    public static function insert_reservation(array $data)
    {
        global $wpdb;

        $table_name = self::table_name();
        $next_number = self::get_next_reference_number();
        $now = current_time('mysql');

        for ($attempt = 0; $attempt < 10; $attempt++) {
            $reference = 'A' . (string) ($next_number + $attempt);

            $row = [
                'reference' => $reference,
                'pickup_date' => $data['pickup_date'],
                'basket_count' => (int) $data['basket_count'],
                'observations' => $data['observations'],
                'full_name' => $data['full_name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'rgpd_consent' => (int) $data['rgpd_consent'],
                'marketing_consent' => (int) $data['marketing_consent'],
                'status' => 'pending',
                'ip_address' => $data['ip_address'],
                'created_at' => $now,
                'updated_at' => $now,
            ];

            $inserted = $wpdb->insert(
                $table_name,
                $row,
                ['%s', '%s', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s', '%s', '%s', '%s']
            );

            if ($inserted) {
                $row['id'] = (int) $wpdb->insert_id;
                return $row;
            }

            if (stripos((string) $wpdb->last_error, 'duplicate') === false) {
                break;
            }
        }

        return new WP_Error(
            'pmr_insert_failed',
            __('No se pudo guardar la reserva. Inténtalo de nuevo.', 'pedraza-mahou-reservations')
        );
    }

    private static function get_next_reference_number(): int
    {
        global $wpdb;

        $table_name = self::table_name();
        $max = $wpdb->get_var(
            "SELECT MAX(CAST(SUBSTRING(reference, 2) AS UNSIGNED))
            FROM {$table_name}
            WHERE reference REGEXP '^A[0-9]+$'"
        );

        return max(101, ((int) $max) + 1);
    }

    public static function get_reservations(array $filters = []): array
    {
        global $wpdb;

        $table_name = self::table_name();
        [$where_sql, $args] = self::build_filters_sql($filters);
        $order_sql = 'ORDER BY created_at DESC, id DESC';

        if (($filters['status'] ?? '') === 'pending') {
            $order_sql = 'ORDER BY pickup_date ASC, created_at ASC, id ASC';
        } elseif (($filters['status'] ?? '') === 'completed') {
            $order_sql = 'ORDER BY updated_at DESC, id DESC';
        }

        $sql = "SELECT * FROM {$table_name} {$where_sql} {$order_sql} LIMIT 500";

        if ($args) {
            $sql = $wpdb->prepare($sql, $args);
        }

        $results = $wpdb->get_results($sql, ARRAY_A);

        return is_array($results) ? $results : [];
    }

    public static function get_totals(array $filters = []): array
    {
        global $wpdb;

        $table_name = self::table_name();
        [$where_sql, $args] = self::build_filters_sql($filters);
        $sql = "SELECT
                COUNT(*) AS total_reservations,
                COALESCE(SUM(basket_count), 0) AS total_baskets,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END), 0) AS pending_reservations,
                COALESCE(SUM(CASE WHEN status = 'pending' THEN basket_count ELSE 0 END), 0) AS pending_baskets,
                COALESCE(SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END), 0) AS completed_reservations,
                COALESCE(SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END), 0) AS cancelled_reservations
            FROM {$table_name}
            {$where_sql}";

        if ($args) {
            $sql = $wpdb->prepare($sql, $args);
        }

        $row = $wpdb->get_row($sql, ARRAY_A);

        return [
            'total_reservations' => isset($row['total_reservations']) ? (int) $row['total_reservations'] : 0,
            'total_baskets' => isset($row['total_baskets']) ? (int) $row['total_baskets'] : 0,
            'pending_reservations' => isset($row['pending_reservations']) ? (int) $row['pending_reservations'] : 0,
            'pending_baskets' => isset($row['pending_baskets']) ? (int) $row['pending_baskets'] : 0,
            'completed_reservations' => isset($row['completed_reservations']) ? (int) $row['completed_reservations'] : 0,
            'cancelled_reservations' => isset($row['cancelled_reservations']) ? (int) $row['cancelled_reservations'] : 0,
        ];
    }

    public static function update_status(int $reservation_id, string $status): bool
    {
        if (! in_array($status, self::valid_statuses(), true)) {
            return false;
        }

        global $wpdb;

        $updated = $wpdb->update(
            self::table_name(),
            [
                'status' => $status,
                'updated_at' => current_time('mysql'),
            ],
            ['id' => $reservation_id],
            ['%s', '%s'],
            ['%d']
        );

        return $updated !== false;
    }

    public static function delete_reservation(int $reservation_id): bool
    {
        global $wpdb;

        $deleted = $wpdb->delete(
            self::table_name(),
            ['id' => $reservation_id],
            ['%d']
        );

        return $deleted !== false;
    }

    private static function build_filters_sql(array $filters): array
    {
        global $wpdb;

        $where = [];
        $args = [];

        if (! empty($filters['pickup_date']) && self::is_valid_date($filters['pickup_date'])) {
            $where[] = 'pickup_date = %s';
            $args[] = $filters['pickup_date'];
        }

        if (! empty($filters['status']) && in_array($filters['status'], self::valid_statuses(), true)) {
            $where[] = 'status = %s';
            $args[] = $filters['status'];
        }

        if (! empty($filters['search'])) {
            $search = '%' . $wpdb->esc_like((string) $filters['search']) . '%';
            $where[] = '(reference LIKE %s OR full_name LIKE %s OR phone LIKE %s OR email LIKE %s)';
            array_push($args, $search, $search, $search, $search);
        }

        if (! $where) {
            return ['', []];
        }

        return ['WHERE ' . implode(' AND ', $where), $args];
    }

    public static function is_valid_date(string $date): bool
    {
        $date_object = DateTime::createFromFormat('Y-m-d', $date);

        return $date_object instanceof DateTime && $date_object->format('Y-m-d') === $date;
    }
}
