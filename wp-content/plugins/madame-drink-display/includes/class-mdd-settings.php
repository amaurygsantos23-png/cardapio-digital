<?php
if (!defined('ABSPATH')) exit;

class MDD_Settings {

    public static function register() {
        // General
        register_setting('mdd_general', 'mdd_drink_post_type');
        register_setting('mdd_general', 'mdd_food_post_type');
        register_setting('mdd_general', 'mdd_field_map');
        register_setting('mdd_general', 'mdd_primary_color');
        register_setting('mdd_general', 'mdd_secondary_color');
        register_setting('mdd_general', 'mdd_accent_color');
        register_setting('mdd_general', 'mdd_establishment_logo');
        register_setting('mdd_general', 'mdd_event_mode');
        register_setting('mdd_general', 'mdd_event_logo');
        register_setting('mdd_general', 'mdd_event_name');

        // TV
        register_setting('mdd_tv', 'mdd_tv_slide_duration');
        register_setting('mdd_tv', 'mdd_tv_transition');
        register_setting('mdd_tv', 'mdd_tv_layout');
        register_setting('mdd_tv', 'mdd_tv_show_price');
        register_setting('mdd_tv', 'mdd_tv_show_qr');

        // Tablet
        register_setting('mdd_tablet', 'mdd_tablet_columns');
        register_setting('mdd_tablet', 'mdd_tablet_timeout');

        // Quiz
        register_setting('mdd_quiz', 'mdd_quiz_questions');
        register_setting('mdd_quiz', 'mdd_quiz_num_questions');
    }

    /**
     * Get all settings as array
     */
    public static function get_all() {
        return [
            'drink_post_type'    => get_option('mdd_drink_post_type', 'drink'),
            'food_post_type'     => get_option('mdd_food_post_type', ''),
            'field_map'          => self::get_field_map(),
            'primary_color'      => get_option('mdd_primary_color', '#C8962E'),
            'secondary_color'    => get_option('mdd_secondary_color', '#1A1A2E'),
            'accent_color'       => get_option('mdd_accent_color', '#E8593C'),
            'establishment_logo' => get_option('mdd_establishment_logo', ''),
            'event_mode'         => get_option('mdd_event_mode', 0),
            'event_logo'         => get_option('mdd_event_logo', ''),
            'event_name'         => get_option('mdd_event_name', ''),
            'tv_slide_duration'  => get_option('mdd_tv_slide_duration', 8),
            'tv_transition'      => get_option('mdd_tv_transition', 'fade'),
            'tv_layout'          => get_option('mdd_tv_layout', 'fullscreen'),
            'tv_show_price'      => get_option('mdd_tv_show_price', 1),
            'tv_show_qr'         => get_option('mdd_tv_show_qr', 1),
            'tablet_columns'     => get_option('mdd_tablet_columns', 2),
            'tablet_timeout'     => get_option('mdd_tablet_timeout', 60),
        ];
    }

    /**
     * Get field mapping (user-defined or defaults)
     */
    public static function get_field_map() {
        $map = get_option('mdd_field_map', []);
        if (empty($map) || !is_array($map)) {
            $map = self::default_field_map();
        }
        return $map;
    }

    /**
     * Default field mapping (backward compatible)
     */
    public static function default_field_map() {
        return [
            'price'       => '',
            'short_desc'  => '',
            'video'       => '',
            'ingredients' => '',
            'gallery'     => '',
            'variants'    => '',
            'food_pairing'=> '',
            'context_msg' => '',
        ];
    }

    /**
     * Get mapped field key for a given plugin field
     * Returns the user-mapped key or empty string
     */
    public static function get_mapped_field($field) {
        $map = self::get_field_map();
        return isset($map[$field]) ? $map[$field] : '';
    }

    /**
     * Detect available meta keys from a CPT
     */
    public static function detect_meta_keys($post_type) {
        global $wpdb;

        $keys = $wpdb->get_col($wpdb->prepare("
            SELECT DISTINCT pm.meta_key
            FROM {$wpdb->postmeta} pm
            INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
            WHERE p.post_type = %s
            AND p.post_status = 'publish'
            AND pm.meta_key NOT LIKE '\\_wp_%%'
            AND pm.meta_key NOT LIKE '\\_edit_%%'
            AND pm.meta_key NOT LIKE '\\_oembed%%'
            AND pm.meta_key != '_thumbnail_id'
            AND pm.meta_key NOT LIKE '\\_mdd_%%'
            ORDER BY pm.meta_key
        ", $post_type));

        return $keys ?: [];
    }

    /**
     * Auto-detect best field match for a given plugin field
     */
    public static function auto_detect_field($field, $available_keys) {
        $patterns = [
            'price'       => ['_price', 'price', 'preco', '_preco', 'drink_price', 'valor', 'product_price'],
            'short_desc'  => ['_short_description', 'short_description', 'descricao_curta', 'descricao', 'excerpt', 'resumo'],
            'video'       => ['_video', 'video', 'video_url', 'drink_video', 'video_curto'],
            'ingredients' => ['_ingredients', 'ingredients', 'ingredientes', 'composicao'],
            'gallery'     => ['_gallery', 'gallery', 'galeria', 'fotos'],
            'variants'    => ['_price_variants', 'price_variants', 'variantes', 'opcoes_preco'],
            'food_pairing'=> ['_food_pairing', 'food_pairing', 'harmonizacao', 'pratos_combinam'],
            'context_msg' => ['_context_message', 'context_message', 'mensagem_contextual', 'dica_drink'],
        ];

        $try = $patterns[$field] ?? [];
        foreach ($try as $key) {
            if (in_array($key, $available_keys)) {
                return $key;
            }
        }
        return '';
    }

    /**
     * Get the active logo URL (respects event mode and priority)
     */
    public static function get_active_logo($device = null) {
        if ($device && !empty($device->logo_override)) {
            return $device->logo_override;
        }

        $event_mode = get_option('mdd_event_mode', 0);
        if ($event_mode) {
            $event_logo_id = get_option('mdd_event_logo', '');
            if ($event_logo_id) {
                $url = wp_get_attachment_url($event_logo_id);
                if ($url) return $url;
            }
        }

        $logo_id = get_option('mdd_establishment_logo', '');
        if ($logo_id) {
            $url = wp_get_attachment_url($logo_id);
            if ($url) return $url;
        }

        return '';
    }
}
