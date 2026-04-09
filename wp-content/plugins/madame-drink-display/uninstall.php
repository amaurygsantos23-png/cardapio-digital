<?php
/**
 * Uninstall Drink Display
 * Removes all plugin data when the plugin is deleted.
 */

if (!defined('WP_UNINSTALL_PLUGIN')) exit;

// Delete options
$options = [
    'mdd_drink_post_type', 'mdd_tv_slide_duration', 'mdd_tv_transition',
    'mdd_tv_layout', 'mdd_tv_show_price', 'mdd_tv_show_qr',
    'mdd_tablet_columns', 'mdd_tablet_columns_portrait', 'mdd_tablet_timeout',
    'mdd_tablet_show_price', 'mdd_tablet_show_badge', 'mdd_tablet_show_desc',
    'mdd_tablet_quiz_text', 'mdd_tablet_screensaver_text', 'mdd_tablet_header_title',
    'mdd_tablet_font_title', 'mdd_tablet_font_body',
    'mdd_quiz_num_questions',
    'mdd_quiz_questions', 'mdd_quiz_ask_name', 'mdd_quiz_skip_base_if_no_alcohol',
    'mdd_quiz_cta_text', 'mdd_quiz_confirm_text', 'mdd_quiz_pairing_text',
    'mdd_quiz_show_rating', 'mdd_quiz_rating_text',
    'mdd_quiz_share_text', 'mdd_quiz_post_rating_msg', 'mdd_hide_field', 'mdd_primary_color', 'mdd_secondary_color',
    'mdd_accent_color', 'mdd_establishment_logo', 'mdd_event_logo',
    'mdd_event_mode', 'mdd_event_name',
    'mdd_license_key', 'mdd_license_status', 'mdd_license_data', 'mdd_license_last_check',
    'mdd_food_post_type', 'mdd_field_map',
    'mdd_logo_max_height_tv', 'mdd_logo_max_height_tablet', 'mdd_logo_max_height_quiz',
    'mdd_tv_custom_slides', 'mdd_tv_qr_position', 'mdd_tv_qr_text',
    'mdd_qr_fg_color', 'mdd_qr_bg_color',
];

foreach ($options as $option) {
    delete_option($option);
}

// Drop custom tables
global $wpdb;
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}mdd_tokens");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}mdd_quiz_results");

// Remove custom taxonomy terms
$terms = get_terms(['taxonomy' => 'mdd_drink_profile', 'hide_empty' => false, 'fields' => 'ids']);
if (!is_wp_error($terms)) {
    foreach ($terms as $term_id) {
        wp_delete_term($term_id, 'mdd_drink_profile');
    }
}

// Remove post meta
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ('_mdd_featured', '_mdd_video', '_mdd_short_description', '_mdd_hide_from_display')");

// Flush rewrite rules
flush_rewrite_rules();
