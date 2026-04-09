<?php
if (!defined('ABSPATH')) exit;

class MDD_CPT_Bridge {

    private $post_type;  // Primary CPT (backward compat)
    private $post_types; // Array of all selected CPTs

    public function __construct($post_types = null) {
        if ($post_types) {
            $this->post_types = is_array($post_types) ? $post_types : [$post_types];
        } else {
            $this->post_types = get_option('mdd_drink_post_types', []);
            if (empty($this->post_types)) {
                $this->post_types = [get_option('mdd_drink_post_type', 'drink')];
            }
        }
        $this->post_type = $this->post_types[0]; // Primary for single-type operations
    }

    /**
     * Get all drinks with optional filters
     */
    public function get_drinks($args = []) {
        $defaults = [
            'post_type'      => $this->post_types, // Supports array of CPTs
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'orderby'        => 'menu_order date',
            'order'          => 'ASC',
        ];

        // Category filter (search across all CPT taxonomies)
        if (!empty($args['category'])) {
            foreach ($this->post_types as $pt) {
                $taxonomies = get_object_taxonomies($pt, 'names');
                $cat_tax = $this->find_category_taxonomy($taxonomies);
                if ($cat_tax) {
                    $defaults['tax_query'] = [[
                        'taxonomy' => $cat_tax,
                        'field'    => 'slug',
                        'terms'    => sanitize_text_field($args['category']),
                    ]];
                    break;
                }
            }
        }

        // Featured only
        if (!empty($args['featured'])) {
            $defaults['meta_query'] = [[
                'key'   => '_mdd_featured',
                'value' => '1',
            ]];
        }

        $query = new WP_Query($defaults);
        $drinks = [];

        foreach ($query->posts as $post) {
            // Filter hidden drinks in PHP (safe, no meta_query issues)
            $hidden = get_post_meta($post->ID, '_mdd_hide_from_display', true);
            if ($hidden === '1') continue;

            $drinks[] = $this->format_drink($post);
        }

        wp_reset_postdata();
        return $drinks;
    }

    /**
     * Get single drink by ID
     */
    public function get_drink($id) {
        $post = get_post($id);
        if (!$post || $post->post_type !== $this->post_type) {
            return null;
        }
        return $this->format_drink($post, true);
    }

    /**
     * Format a drink post into structured data
     */
    public function format_drink($post, $full = false) {
        $id = $post->ID;

        // Get thumbnail
        $thumb_id = get_post_thumbnail_id($id);
        $thumb_url = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'large') : '';
        $thumb_full = $thumb_id ? wp_get_attachment_image_url($thumb_id, 'full') : '';

        // Use field mapper (user-defined) with fallback to auto-detect
        $price     = $this->get_mapped_or_fallback($id, 'price', [
            '_price', 'price', 'preco', '_preco', 'drink_price', 'valor',
            'product_price', 'preco_produto', 'bebida_preco', 'drink-price',
            'preco-drink', 'valor_produto', 'valor-produto',
        ]);
        // Handle cases where price is stored as array or serialized
        if (is_array($price)) {
            $price = reset($price); // Get first value
        }
        // Price variants (mapped fields)
        $price_2 = $this->get_mapped_or_fallback($id, 'price_2', ['_price_2', 'price_2', 'preco_2', 'preco2', 'valor_2']);
        $price_3 = $this->get_mapped_or_fallback($id, 'price_3', ['_price_3', 'price_3', 'preco_3', 'preco3', 'valor_3']);
        if (is_array($price_2)) $price_2 = reset($price_2);
        if (is_array($price_3)) $price_3 = reset($price_3);
        $short_desc = $this->get_mapped_or_fallback($id, 'short_desc', ['_mdd_short_description', '_short_description', 'short_description', 'descricao_curta', 'excerpt']);
        $video     = $this->get_mapped_or_fallback($id, 'video', ['_mdd_video', '_video', 'video', 'video_url', 'drink_video', 'video_curto']);

        if (empty($short_desc)) {
            $short_desc = wp_trim_words($post->post_content, 20, '...');
        }

        // Get profile tags
        $profile_tags = wp_get_object_terms($id, 'mdd_drink_profile', ['fields' => 'slugs']);
        if (is_wp_error($profile_tags)) {
            $profile_tags = [];
        }

        // Get category
        $categories = [];
        $taxonomies = get_object_taxonomies($this->post_type, 'names');
        $cat_tax = $this->find_category_taxonomy($taxonomies);
        if ($cat_tax) {
            $terms = wp_get_object_terms($id, $cat_tax, ['fields' => 'all']);
            if (!is_wp_error($terms)) {
                foreach ($terms as $term) {
                    $categories[] = [
                        'slug' => $term->slug,
                        'name' => $term->name,
                    ];
                }
            }
        }

        $drink = [
            'id'           => $id,
            'title'        => $post->post_title,
            'slug'         => $post->post_name,
            'short_desc'   => $short_desc,
            'price'        => $price ? floatval($price) : null,
            'price_formatted' => $price ? 'R$ ' . number_format(floatval($price), 2, ',', '.') : '',
            'price_2'      => $price_2 ? floatval($price_2) : null,
            'price_2_formatted' => $price_2 ? 'R$ ' . number_format(floatval($price_2), 2, ',', '.') : '',
            'price_3'      => $price_3 ? floatval($price_3) : null,
            'price_3_formatted' => $price_3 ? 'R$ ' . number_format(floatval($price_3), 2, ',', '.') : '',
            'image'        => $thumb_url,
            'image_full'   => $thumb_full,
            'video'        => $video ?: null,
            'categories'   => $categories,
            'profile_tags' => $profile_tags,
            'featured'     => (bool) get_post_meta($id, '_mdd_featured', true),
            'permalink'    => get_permalink($id),
            'order'        => $post->menu_order,
        ];

        // Full details for single drink view
        if ($full) {
            $drink['content'] = apply_filters('the_content', $post->post_content);
            $drink['content_raw'] = $post->post_content;

            // Ingredients
            $ingredients = $this->get_mapped_or_fallback($id, 'ingredients', ['_ingredients', 'ingredients', 'ingredientes']);
            if (is_string($ingredients)) {
                $ingredients = array_map('trim', explode(',', $ingredients));
            }
            $drink['ingredients'] = $ingredients ?: [];

            // Gallery
            $gallery = $this->get_mapped_or_fallback($id, 'gallery', ['_gallery', 'gallery', 'galeria']);
            $drink['gallery'] = $this->parse_gallery($gallery);

            // Price variants
            $variants = $this->get_mapped_or_fallback($id, 'variants', ['_price_variants', 'price_variants', 'variantes', 'opcoes_preco']);
            $drink['variants'] = $this->parse_variants($variants);

            // Food pairing (for quiz harmonization)
            $drink['food_pairing'] = $this->get_food_pairing($id);

            // Context message (e.g., "Great opening drink!")
            $context = $this->get_mapped_or_fallback($id, 'context_msg', ['_mdd_context_message', '_context_message', 'mensagem_contextual']);
            $drink['context_message'] = $context ?: '';
        }

        return $drink;
    }

    /**
     * Get food pairing data for a drink
     * Returns array of paired dishes with name and image
     */
    public function get_food_pairing($drink_id) {
        $food_cpt = get_option('mdd_food_post_type', '');
        if (empty($food_cpt)) return [];

        // Try mapped field first, then fallback
        $pairing_ids = $this->get_mapped_or_fallback($drink_id, 'food_pairing',
            ['_mdd_food_pairing', '_food_pairing', 'food_pairing', 'harmonizacao', 'pratos_combinam']);

        if (empty($pairing_ids)) return [];

        // Normalize to array of IDs
        if (is_string($pairing_ids)) {
            $pairing_ids = array_filter(array_map('intval', explode(',', $pairing_ids)));
        }
        if (!is_array($pairing_ids)) return [];

        $dishes = [];
        foreach (array_slice($pairing_ids, 0, 3) as $dish_id) {
            $dish = get_post(intval($dish_id));
            if (!$dish || $dish->post_type !== $food_cpt || $dish->post_status !== 'publish') continue;

            $img_id = get_post_thumbnail_id($dish->ID);
            $dishes[] = [
                'id'    => $dish->ID,
                'title' => $dish->post_title,
                'image' => $img_id ? wp_get_attachment_image_url($img_id, 'medium') : '',
            ];
        }
        return $dishes;
    }

    /**
     * Get categories/terms available for drinks
     */
    public function get_categories() {
        $taxonomies = get_object_taxonomies($this->post_type, 'names');
        $cat_tax = $this->find_category_taxonomy($taxonomies);

        if (!$cat_tax) return [];

        $terms = get_terms([
            'taxonomy'   => $cat_tax,
            'hide_empty' => true,
        ]);

        if (is_wp_error($terms)) return [];

        return array_map(function($term) {
            return [
                'slug'  => $term->slug,
                'name'  => $term->name,
                'count' => $term->count,
            ];
        }, $terms);
    }

    /**
     * Try to find the category taxonomy for the CPT
     */
    private function find_category_taxonomy($taxonomies) {
        $priority = ['drink_category', 'categoria', 'category', 'drink_cat', 'tipo'];
        foreach ($priority as $tax) {
            if (in_array($tax, $taxonomies)) return $tax;
        }
        // Return first non-profile taxonomy
        foreach ($taxonomies as $tax) {
            if ($tax !== 'mdd_drink_profile' && $tax !== 'post_tag') return $tax;
        }
        return null;
    }

    /**
     * Get value using mapped field first, then fallback to auto-detect
     */
    private function get_mapped_or_fallback($post_id, $field_name, $fallback_keys) {
        // Check if user has mapped this field
        $mapped = MDD_Settings::get_mapped_field($field_name);
        if (!empty($mapped)) {
            $value = get_post_meta($post_id, $mapped, true);
            if (!empty($value)) return $value;
        }

        // Fallback to trying multiple keys
        return $this->get_meta_value($post_id, $fallback_keys);
    }

    /**
     * Try multiple meta keys to find a value
     */
    private function get_meta_value($post_id, $keys) {
        foreach ($keys as $key) {
            $value = get_post_meta($post_id, $key, true);
            if (!empty($value)) return $value;
        }
        return null;
    }

    /**
     * Parse gallery from various formats
     */
    private function parse_gallery($gallery) {
        if (empty($gallery)) return [];

        // Array of IDs
        if (is_array($gallery)) {
            return array_filter(array_map(function($id) {
                return wp_get_attachment_image_url(intval($id), 'large');
            }, $gallery));
        }

        // Comma-separated IDs
        if (is_string($gallery) && preg_match('/^[\d,\s]+$/', $gallery)) {
            $ids = array_map('intval', explode(',', $gallery));
            return array_filter(array_map(function($id) {
                return wp_get_attachment_image_url($id, 'large');
            }, $ids));
        }

        return [];
    }

    /**
     * Parse price variants
     */
    private function parse_variants($variants) {
        if (empty($variants)) return [];
        if (is_array($variants)) return $variants;
        return [];
    }
}
