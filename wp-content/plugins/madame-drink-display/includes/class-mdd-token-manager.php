<?php
if (!defined('ABSPATH')) exit;

class MDD_Token_Manager {

    const TABLE_NAME = 'mdd_tokens';

    public static function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            token VARCHAR(64) NOT NULL,
            device_name VARCHAR(100) NOT NULL,
            device_type ENUM('tv','tablet','quiz') NOT NULL DEFAULT 'tv',
            category_filter VARCHAR(255) DEFAULT '',
            cpt_filter VARCHAR(500) DEFAULT '',
            layout_override VARCHAR(50) DEFAULT '',
            logo_override VARCHAR(500) DEFAULT '',
            is_active TINYINT(1) NOT NULL DEFAULT 1,
            last_access DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY token (token),
            KEY device_type (device_type),
            KEY is_active (is_active)
        ) $charset;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Generate a new token for a device
     */
    public static function create_token($device_name, $device_type = 'tv', $options = []) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        $token = wp_generate_password(32, false, false);

        $data = [
            'token'           => $token,
            'device_name'     => sanitize_text_field($device_name),
            'device_type'     => in_array($device_type, ['tv', 'tablet', 'quiz']) ? $device_type : 'tv',
            'category_filter' => !empty($options['category_filter']) ? sanitize_text_field($options['category_filter']) : '',
            'cpt_filter'      => !empty($options['cpt_filter']) ? sanitize_text_field($options['cpt_filter']) : '',
            'layout_override' => !empty($options['layout_override']) ? sanitize_text_field($options['layout_override']) : '',
            'logo_override'   => !empty($options['logo_override']) ? esc_url_raw($options['logo_override']) : '',
            'is_active'       => 1,
        ];

        $wpdb->insert($table, $data);

        return $token;
    }

    /**
     * Validate a token and return device info
     */
    public static function validate_token($token) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        $device = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE token = %s AND is_active = 1",
            sanitize_text_field($token)
        ));

        if ($device) {
            // Update last access (use GMT for consistent time comparison)
            $wpdb->update($table, ['last_access' => gmdate('Y-m-d H:i:s')], ['id' => $device->id]);
        }

        return $device;
    }

    /**
     * Get all tokens
     */
    public static function get_all_tokens() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;
        return $wpdb->get_results("SELECT * FROM $table ORDER BY created_at DESC");
    }

    /**
     * Revoke a token
     */
    public static function revoke_token($token_id) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;
        return $wpdb->update($table, ['is_active' => 0], ['id' => intval($token_id)]);
    }

    /**
     * Reactivate a token
     */
    public static function reactivate_token($token_id) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;
        return $wpdb->update($table, ['is_active' => 1], ['id' => intval($token_id)]);
    }

    /**
     * Delete a token permanently
     */
    public static function delete_token($token_id) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;
        return $wpdb->delete($table, ['id' => intval($token_id)]);
    }

    /**
     * Update token settings
     */
    public static function update_token($token_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        $allowed = ['device_name', 'device_type', 'category_filter', 'cpt_filter', 'layout_override', 'logo_override'];
        $update = [];

        foreach ($allowed as $field) {
            if (isset($data[$field])) {
                $update[$field] = sanitize_text_field($data[$field]);
            }
        }

        if (empty($update)) return false;

        return $wpdb->update($table, $update, ['id' => intval($token_id)]);
    }
}
