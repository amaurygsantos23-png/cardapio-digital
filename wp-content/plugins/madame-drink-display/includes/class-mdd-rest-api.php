<?php
if (!defined('ABSPATH')) exit;

class MDD_Rest_API {

    private $namespace = 'mdd/v1';

    public function register_routes() {

        // Drinks list
        register_rest_route($this->namespace, '/drinks', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_drinks'],
            'permission_callback' => [$this, 'check_token'],
        ]);

        // Single drink
        register_rest_route($this->namespace, '/drinks/(?P<id>\d+)', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_drink'],
            'permission_callback' => '__return_true', // Public: quiz needs drink detail for harmonization
        ]);

        // Featured drinks (for TV)
        register_rest_route($this->namespace, '/drinks/featured', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_featured_drinks'],
            'permission_callback' => [$this, 'check_token'],
        ]);

        // Categories
        register_rest_route($this->namespace, '/categories', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_categories'],
            'permission_callback' => [$this, 'check_token'],
        ]);

        // Quiz questions
        register_rest_route($this->namespace, '/quiz/questions', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_quiz_questions'],
            'permission_callback' => [$this, 'check_license'],
        ]);

        // Quiz result
        register_rest_route($this->namespace, '/quiz/result', [
            'methods'  => 'POST',
            'callback' => [$this, 'process_quiz'],
            'permission_callback' => [$this, 'check_license'],
        ]);

        // Quiz update (chosen drink + quiz rating)
        register_rest_route($this->namespace, '/quiz/update', [
            'methods'  => 'POST',
            'callback' => [$this, 'update_quiz'],
            'permission_callback' => [$this, 'check_license'],
        ]);

        // Drink rating (post-experience, via token)
        register_rest_route($this->namespace, '/quiz/rate-drink', [
            'methods'  => 'POST',
            'callback' => [$this, 'rate_drink'],
            'permission_callback' => '__return_true',
        ]);

        // Drink rating page data (get drink info by rating token)
        register_rest_route($this->namespace, '/quiz/rating-info/(?P<token>[a-zA-Z0-9]+)', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_rating_info'],
            'permission_callback' => '__return_true',
        ]);

        // Display settings
        register_rest_route($this->namespace, '/settings/display', [
            'methods'  => 'GET',
            'callback' => [$this, 'get_display_settings'],
            'permission_callback' => [$this, 'check_token'],
        ]);
    }

    /**
     * Validate token from query param or header
     */
    public function check_token($request) {
        // License check first
        if (!MDD_License::is_valid()) {
            return new WP_Error('mdd_license_inactive', __('Licença do Drink Display inativa ou expirada.', 'madame-drink-display'), ['status' => 403]);
        }

        $token = $request->get_param('token');
        if (empty($token)) {
            $token = $request->get_header('X-MDD-Token');
        }

        if (empty($token)) {
            return new WP_Error('mdd_no_token', __('Token de acesso obrigatório.', 'madame-drink-display'), ['status' => 401]);
        }

        $device = MDD_Token_Manager::validate_token($token);
        if (!$device) {
            return new WP_Error('mdd_invalid_token', __('Token inválido ou revogado.', 'madame-drink-display'), ['status' => 403]);
        }

        // Store device info for use in callbacks
        $request->set_param('_mdd_device', $device);
        return true;
    }

    /**
     * Check license only (for public endpoints like quiz)
     */
    public function check_license($request) {
        if (!MDD_License::is_valid()) {
            return new WP_Error('mdd_license_inactive', __('Licença do Drink Display inativa ou expirada.', 'madame-drink-display'), ['status' => 403]);
        }
        return true;
    }

    public function get_drinks($request) {
        // Check if token has CPT filter
        $device = $request->get_param('_mdd_device');
        $cpt_filter = ($device && !empty($device->cpt_filter)) ? explode(',', $device->cpt_filter) : null;
        $bridge = new MDD_CPT_Bridge($cpt_filter);
        $args = [];

        if ($category = $request->get_param('category')) {
            $args['category'] = $category;
        }

        // Apply device category filter
        if ($device && !empty($device->category_filter)) {
            $args['category'] = $device->category_filter;
        }

        $drinks = $bridge->get_drinks($args);

        $response = rest_ensure_response([
            'success' => true,
            'count'   => count($drinks),
            'drinks'  => $drinks,
        ]);
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        return $response;
    }

    public function get_drink($request) {
        $bridge = new MDD_CPT_Bridge();
        $drink = $bridge->get_drink($request['id']);

        if (!$drink) {
            return new WP_Error('mdd_not_found', __('Drink não encontrado.', 'madame-drink-display'), ['status' => 404]);
        }

        return rest_ensure_response([
            'success' => true,
            'drink'   => $drink,
        ]);
    }

    public function get_featured_drinks($request) {
        $bridge = new MDD_CPT_Bridge();
        $args = ['featured' => true];

        $device = $request->get_param('_mdd_device');
        if ($device && !empty($device->category_filter)) {
            $args['category'] = $device->category_filter;
        }

        $drinks = $bridge->get_drinks($args);

        // If no featured drinks, return all
        if (empty($drinks)) {
            unset($args['featured']);
            $drinks = $bridge->get_drinks($args);
        }

        return rest_ensure_response([
            'success' => true,
            'count'   => count($drinks),
            'drinks'  => $drinks,
        ]);
    }

    public function get_categories($request) {
        $bridge = new MDD_CPT_Bridge();
        $categories = $bridge->get_categories();

        return rest_ensure_response([
            'success'    => true,
            'categories' => $categories,
        ]);
    }

    public function get_quiz_questions($request) {
        $engine = new MDD_Quiz_Engine();
        $questions = $engine->get_questions();

        return rest_ensure_response([
            'success'   => true,
            'questions' => $questions,
        ]);
    }

    public function process_quiz($request) {
        $data = $request->get_json_params();

        if (empty($data) || !isset($data['responses'])) {
            return new WP_Error('mdd_invalid_data', __('Respostas inválidas.', 'madame-drink-display'), ['status' => 400]);
        }

        $engine = new MDD_Quiz_Engine();
        $customer_name = sanitize_text_field($data['customer_name'] ?? '');
        $customer_phone = sanitize_text_field($data['customer_phone'] ?? '');
        $result = $engine->process_answers($data['responses'], $customer_name, $customer_phone);

        // Build drink rating URL
        $rating_url = home_url('/display/quiz/?rate=' . $result['rating_token']);

        return rest_ensure_response([
            'success'       => true,
            'recommended'   => $result['drinks'],
            'session_id'    => $result['session_id'],
            'rating_token'  => $result['rating_token'],
            'rating_url'    => $rating_url,
        ]);
    }

    public function update_quiz($request) {
        $data = $request->get_json_params();
        $session_id = sanitize_text_field($data['session_id'] ?? '');
        if (empty($session_id)) {
            return new WP_Error('mdd_invalid', 'Session ID obrigatório.', ['status' => 400]);
        }

        MDD_Quiz_Engine::update_choice($session_id, [
            'chosen_drink_id' => $data['chosen_drink_id'] ?? null,
            'quiz_rating'     => $data['quiz_rating'] ?? null,
            'completed'       => $data['completed'] ?? null,
            'shared'          => $data['shared'] ?? null,
        ]);

        return rest_ensure_response(['success' => true]);
    }

    public function rate_drink($request) {
        $data = $request->get_json_params();
        $token  = sanitize_text_field($data['token'] ?? '');
        $rating = intval($data['rating'] ?? 0);

        if (empty($token) || $rating < 1 || $rating > 5) {
            return new WP_Error('mdd_invalid', 'Token e rating (1-5) obrigatórios.', ['status' => 400]);
        }

        $result = MDD_Quiz_Engine::rate_drink($token, $rating);
        $code = $result['success'] ? 200 : 404;
        return new WP_REST_Response($result, $code);
    }

    public function get_rating_info($request) {
        $token = sanitize_text_field($request->get_param('token'));
        $session = MDD_Quiz_Engine::get_by_rating_token($token);

        if (!$session) {
            return new WP_Error('mdd_not_found', 'Token não encontrado.', ['status' => 404]);
        }

        $drink_data = null;
        if ($session->chosen_drink_id) {
            $bridge = new MDD_CPT_Bridge();
            $drink_data = $bridge->get_drink($session->chosen_drink_id);
        }

        return rest_ensure_response([
            'success'       => true,
            'customer_name' => $session->customer_name,
            'drink'         => $drink_data,
            'already_rated' => !empty($session->drink_rating),
            'drink_rating'  => $session->drink_rating ? intval($session->drink_rating) : null,
        ]);
    }

    /**
     * Add cache-busting timestamp to image URLs
     */
    private static function cache_bust($url) {
        if (empty($url)) return '';
        $sep = strpos($url, '?') !== false ? '&' : '?';
        return $url . $sep . 'v=' . time();
    }

    private static function cache_bust_slides($slides) {
        if (!is_array($slides)) return [];
        foreach ($slides as &$s) {
            if (!empty($s['bg_image'])) {
                $s['bg_image'] = self::cache_bust($s['bg_image']);
            }
        }
        return $slides;
    }

    public function get_display_settings($request) {
        $device = $request->get_param('_mdd_device');

        // Determine which logo to use
        $event_mode = get_option('mdd_event_mode', 0);

        // Auto-scheduling: check event start/end times
        $event_start = get_option('mdd_event_start', '');
        $event_end = get_option('mdd_event_end', '');
        $now = current_time('Y-m-d\TH:i');

        if ($event_mode && $event_end && $now > $event_end) {
            // Event ended — auto-disable
            update_option('mdd_event_mode', 0);
            $event_mode = 0;
        } elseif ($event_mode && $event_start && $now < $event_start) {
            // Event not started yet — behave as if not active
            $event_mode = 0;
        }
        $logo = '';

        // Priority: device logo override > event logo > establishment logo
        if ($device && !empty($device->logo_override)) {
            // Could be URL or attachment ID
            $logo = is_numeric($device->logo_override) ? wp_get_attachment_url(intval($device->logo_override)) : $device->logo_override;
        } elseif ($event_mode && get_option('mdd_event_logo')) {
            $logo = wp_get_attachment_url(get_option('mdd_event_logo'));
        } elseif (get_option('mdd_establishment_logo')) {
            $logo = wp_get_attachment_url(get_option('mdd_establishment_logo'));
        }

        $settings = [
            'logo'              => self::cache_bust($logo),
            'event_mode'        => (bool) $event_mode,
            'event_name'        => get_option('mdd_event_name', ''),
            'primary_color'     => get_option('mdd_primary_color', '#C8962E'),
            'secondary_color'   => get_option('mdd_secondary_color', '#1A1A2E'),
            'accent_color'      => get_option('mdd_accent_color', '#E8593C'),
            'tv' => [
                'slide_duration' => intval(get_option('mdd_tv_slide_duration', 8)),
                'transition'     => get_option('mdd_tv_transition', 'fade'),
                'layout'         => $device && !empty($device->layout_override) ? $device->layout_override : get_option('mdd_tv_layout', 'fullscreen'),
                'show_price'     => (bool) get_option('mdd_tv_show_price', 1),
                'show_qr'        => (bool) get_option('mdd_tv_show_qr', 1),
                'qr_position'    => get_option('mdd_tv_qr_position', 'bottom-right'),
                'qr_text'        => get_option('mdd_tv_qr_text', 'Faça o Quiz'),
                'custom_slides'  => self::cache_bust_slides(get_option('mdd_tv_custom_slides', [])),
                'bg_image'       => self::cache_bust(get_option('mdd_tv_bg_image', '')),
                'title_color'    => get_option('mdd_tv_title_color', ''),
                'desc_color'     => get_option('mdd_tv_desc_color', ''),
                'price_color'    => get_option('mdd_tv_price_color', ''),
                'cat_color'      => get_option('mdd_tv_cat_color', ''),
                'title_size'     => intval(get_option('mdd_tv_title_size', 32)),
                'desc_size'      => intval(get_option('mdd_tv_desc_size', 14)),
                'price_size'     => intval(get_option('mdd_tv_price_size', 24)),
                'cat_size'       => intval(get_option('mdd_tv_cat_size', 12)),
            ],
            'logo_heights' => [
                'tv'     => intval(get_option('mdd_logo_max_height_tv', 60)),
                'tablet' => intval(get_option('mdd_logo_max_height_tablet', 50)),
                'quiz'   => intval(get_option('mdd_logo_max_height_quiz', 80)),
            ],
            'tablet' => [
                'columns'          => intval(get_option('mdd_tablet_columns', 2)),
                'columns_portrait' => intval(get_option('mdd_tablet_columns_portrait', 1)),
                'timeout'          => intval(get_option('mdd_tablet_timeout', 60)),
                'show_price'       => (bool) get_option('mdd_tablet_show_price', 1),
                'show_badge'       => (bool) get_option('mdd_tablet_show_badge', 1),
                'show_desc'        => (bool) get_option('mdd_tablet_show_desc', 1),
                'quiz_text'        => get_option('mdd_tablet_quiz_text', 'Faça o Quiz ✨'),
                'screensaver_text' => get_option('mdd_tablet_screensaver_text', 'Cardápio de Drinks'),
                'header_title'     => get_option('mdd_tablet_header_title', 'Drinks'),
                'font_title'       => get_option('mdd_tablet_font_title', 'Playfair Display'),
                'font_body'        => get_option('mdd_tablet_font_body', 'Outfit'),
            ],
            'quiz_url' => home_url('/display/quiz/'),
            'quiz' => [
                'ask_name'      => (bool) get_option('mdd_quiz_ask_name', 1),
                'skip_base'     => (bool) get_option('mdd_quiz_skip_base_if_no_alcohol', 1),
                'cta_text'      => get_option('mdd_quiz_cta_text', 'Experimentar'),
                'confirm_text'  => get_option('mdd_quiz_confirm_text', 'Informe ao garçom para confirmar seu pedido.'),
                'pairing_text'  => get_option('mdd_quiz_pairing_text', 'Sabia que esse drink combina com'),
                'show_rating'   => (bool) get_option('mdd_quiz_show_rating', 1),
                'rating_text'   => get_option('mdd_quiz_rating_text', 'Como foi o Quiz?'),
                'food_cpt'      => get_option('mdd_food_post_type', ''),
            ],
        ];

        $response = rest_ensure_response([
            'success'  => true,
            'settings' => $settings,
        ]);
        // Prevent browser caching of settings
        $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
        $response->header('Pragma', 'no-cache');
        $response->header('Expires', '0');
        return $response;
    }
}
