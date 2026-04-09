<?php
if (!defined('ABSPATH')) exit;

class MDD_Quiz_Engine {

    const TABLE_NAME = 'mdd_quiz_results';

    public static function create_table() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;
        $charset = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS $table (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            session_id VARCHAR(64) NOT NULL,
            customer_name VARCHAR(100) DEFAULT '',
            customer_phone VARCHAR(20) DEFAULT '',
            shared TINYINT(1) NOT NULL DEFAULT 0,
            responses TEXT NOT NULL,
            recommended_ids TEXT NOT NULL,
            chosen_drink_id BIGINT UNSIGNED DEFAULT NULL,
            quiz_rating TINYINT UNSIGNED DEFAULT NULL,
            drink_rating TINYINT UNSIGNED DEFAULT NULL,
            rating_token VARCHAR(16) DEFAULT NULL,
            drink_rated_at DATETIME DEFAULT NULL,
            completed TINYINT(1) NOT NULL DEFAULT 0,
            created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY session_id (session_id),
            KEY rating_token (rating_token),
            KEY created_at (created_at)
        ) $charset;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    /**
     * Get configured quiz questions
     */
    public function get_questions() {
        $saved = get_option('mdd_quiz_questions', '');

        if (!empty($saved) && is_array($saved)) {
            return $saved;
        }

        // Default questions
        return $this->get_default_questions();
    }

    /**
     * Default quiz questions
     */
    private function get_default_questions() {
        return [
            [
                'id'       => 'flavor',
                'question' => 'Qual sabor te atrai mais?',
                'icon'     => '🍋',
                'options'  => [
                    ['label' => 'Frutas cítricas',  'tags' => ['citrico', 'refrescante', 'tropical'], 'icon' => '🍊'],
                    ['label' => 'Frutas doces',      'tags' => ['doce', 'cremoso', 'tropical'],       'icon' => '🍓'],
                    ['label' => 'Ervas e especiarias','tags' => ['herbaceo', 'aromatico', 'especiado'],'icon' => '🌿'],
                    ['label' => 'Neutro / Suave',    'tags' => ['suave', 'neutro', 'classico'],       'icon' => '✨'],
                ],
            ],
            [
                'id'       => 'texture',
                'question' => 'Como você prefere a textura?',
                'icon'     => '🧊',
                'options'  => [
                    ['label' => 'Bem gelado e líquido', 'tags' => ['refrescante', 'on-the-rocks'],  'icon' => '❄️'],
                    ['label' => 'Cremoso e encorpado',  'tags' => ['cremoso', 'encorpado'],          'icon' => '🥛'],
                    ['label' => 'Com gás / efervescente','tags' => ['efervescente', 'sparkling'],     'icon' => '🫧'],
                ],
            ],
            [
                'id'       => 'strength',
                'question' => 'Qual intensidade de álcool?',
                'icon'     => '💪',
                'options'  => [
                    ['label' => 'Sem álcool',    'tags' => ['sem-alcool', 'zero'],       'icon' => '🚫'],
                    ['label' => 'Suave',         'tags' => ['suave', 'leve'],            'icon' => '🌸'],
                    ['label' => 'Equilibrado',   'tags' => ['equilibrado', 'medio'],     'icon' => '⚖️'],
                    ['label' => 'Forte',         'tags' => ['forte', 'intenso'],         'icon' => '🔥'],
                ],
            ],
            [
                'id'       => 'moment',
                'question' => 'Para qual momento?',
                'icon'     => '🕐',
                'options'  => [
                    ['label' => 'Para começar a noite', 'tags' => ['aperitivo', 'leve', 'refrescante'], 'icon' => '🌅'],
                    ['label' => 'Para acompanhar',      'tags' => ['harmonico', 'equilibrado'],         'icon' => '🍽️'],
                    ['label' => 'Para celebrar!',        'tags' => ['premium', 'especial', 'intenso'],  'icon' => '🥂'],
                    ['label' => 'Para refrescar',        'tags' => ['refrescante', 'gelado', 'tropical'],'icon' => '🏖️'],
                ],
            ],
            [
                'id'       => 'base',
                'question' => 'Base preferida?',
                'icon'     => '🍸',
                'options'  => [
                    ['label' => 'Vodka',          'tags' => ['vodka'],           'icon' => '🇷🇺'],
                    ['label' => 'Gin',            'tags' => ['gin'],             'icon' => '🌿'],
                    ['label' => 'Rum / Cachaça',  'tags' => ['rum', 'cachaca'],  'icon' => '🏝️'],
                    ['label' => 'Tanto faz!',     'tags' => [],                  'icon' => '🎲'],
                ],
            ],
        ];
    }

    /**
     * Process quiz answers and return recommended drinks
     */
    public function process_answers($responses, $customer_name = '', $customer_phone = '') {
        // Collect all tags from responses with weights
        $tag_scores = [];

        $questions = $this->get_questions();

        foreach ($responses as $resp) {
            $question_id = sanitize_text_field($resp['question_id'] ?? '');
            $option_index = intval($resp['option_index'] ?? 0);

            // Find the question
            foreach ($questions as $q) {
                if ($q['id'] === $question_id && isset($q['options'][$option_index])) {
                    $tags = $q['options'][$option_index]['tags'];
                    foreach ($tags as $tag) {
                        if (!isset($tag_scores[$tag])) {
                            $tag_scores[$tag] = 0;
                        }
                        $tag_scores[$tag] += 1;
                    }
                    break;
                }
            }
        }

        if (empty($tag_scores)) {
            $bridge = new MDD_CPT_Bridge();
            $all = $bridge->get_drinks();
            shuffle($all);
            $result = array_slice($all, 0, 3);
            $session_data = $this->save_result($responses, $result, $customer_name, $customer_phone);
            return [
                'drinks'       => $result,
                'session_id'   => $session_data['session_id'],
                'rating_token' => $session_data['rating_token'],
            ];
        }

        // Score all drinks
        $bridge = new MDD_CPT_Bridge();
        $all_drinks = $bridge->get_drinks();

        // Determine if user wants alcohol or not
        $wants_alcohol = false;
        $wants_no_alcohol = false;
        $alcohol_tags = ['forte', 'suave', 'medio', 'equilibrado'];
        $no_alcohol_tags = ['sem-alcool', 'zero'];
        
        foreach ($tag_scores as $tag => $score) {
            if (in_array($tag, $alcohol_tags)) $wants_alcohol = true;
            if (in_array($tag, $no_alcohol_tags)) $wants_no_alcohol = true;
        }

        $scored = [];
        foreach ($all_drinks as $drink) {
            $drink_tags = $drink['profile_tags'] ?? [];
            
            // Filter: if user wants alcohol, exclude zero-alcohol drinks
            if ($wants_alcohol && !$wants_no_alcohol) {
                // Check tags
                $is_zero = array_intersect($drink_tags, $no_alcohol_tags);
                if (!empty($is_zero)) continue;
                // Also check has_alcohol field if available
                if (isset($drink['has_alcohol']) && $drink['has_alcohol'] === false) continue;
                // Check title/category for common zero-alcohol indicators
                $title_lower = mb_strtolower($drink['title'] ?? '');
                if (strpos($title_lower, 'sem álcool') !== false || strpos($title_lower, 'zero') !== false 
                    || strpos($title_lower, 'virgin') !== false || strpos($title_lower, 'mocktail') !== false) continue;
            }
            // Filter: if user wants no alcohol, exclude alcoholic drinks
            if ($wants_no_alcohol && !$wants_alcohol) {
                $is_alcoholic = array_intersect($drink_tags, $alcohol_tags);
                $has_zero = array_intersect($drink_tags, $no_alcohol_tags);
                if (!empty($is_alcoholic) && empty($has_zero)) continue; // Skip
                // Keep if drink has 'sem-alcool' tag or has_alcohol=false
            }

            $score = 0;
            foreach ($drink_tags as $tag) {
                if (isset($tag_scores[$tag])) {
                    $score += $tag_scores[$tag];
                }
            }
            // Also check category slugs
            foreach ($drink['categories'] as $cat) {
                if (isset($tag_scores[$cat['slug']])) {
                    $score += $tag_scores[$cat['slug']];
                }
            }

            // Normalize: divide by number of tags to prevent drinks with many tags from always winning
            $tag_count = max(count($drink_tags), 1);
            $normalized = $score + ($score / $tag_count * 0.5);

            $scored[] = [
                'drink' => $drink,
                'score' => $normalized,
            ];
        }

        // Remove drinks with zero score if there are scored ones
        $has_scored = false;
        foreach ($scored as $s) { if ($s['score'] > 0) { $has_scored = true; break; } }
        if ($has_scored) {
            $scored = array_filter($scored, function($s) { return $s['score'] > 0; });
            $scored = array_values($scored);
        }

        // Sort by score descending, with random tiebreaker for variety
        usort($scored, function($a, $b) {
            $diff = $b['score'] - $a['score'];
            if (abs($diff) < 0.5) return rand(-1, 1); // Random order for similar scores
            return $diff > 0 ? 1 : -1;
        });

        // Top 3
        $recommended = array_slice($scored, 0, 3);
        $result = array_map(function($item) {
            $item['drink']['match_score'] = $item['score'];
            return $item['drink'];
        }, $recommended);

        // Save result for analytics
        $session_data = $this->save_result($responses, $result, $customer_name, $customer_phone);

        return [
            'drinks'       => $result,
            'session_id'   => $session_data['session_id'],
            'rating_token' => $session_data['rating_token'],
        ];
    }

    /**
     * Save quiz result for analytics
     */
    private function save_result($responses, $recommended, $customer_name = '', $customer_phone = '') {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        $ids = array_map(function($d) { return $d['id']; }, $recommended);
        $session = wp_generate_password(16, false, false);
        $rating_token = wp_generate_password(8, false, false);

        $wpdb->insert($table, [
            'session_id'      => $session,
            'customer_name'   => sanitize_text_field($customer_name),
            'customer_phone'  => sanitize_text_field($customer_phone),
            'responses'       => wp_json_encode($responses),
            'recommended_ids' => implode(',', $ids),
            'rating_token'    => $rating_token,
        ]);

        return [
            'session_id'   => $session,
            'rating_token' => $rating_token,
            'quiz_id'      => $wpdb->insert_id,
        ];
    }

    /**
     * Update chosen drink and quiz rating
     */
    public static function update_choice($session_id, $data) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        $update = [];
        if (isset($data['chosen_drink_id'])) {
            $update['chosen_drink_id'] = intval($data['chosen_drink_id']);
        }
        if (isset($data['quiz_rating'])) {
            $update['quiz_rating'] = max(1, min(5, intval($data['quiz_rating'])));
        }
        if (isset($data['completed'])) {
            $update['completed'] = intval($data['completed']) ? 1 : 0;
        }
        if (isset($data['shared'])) {
            $update['shared'] = intval($data['shared']) ? 1 : 0;
        }

        if (empty($update)) return false;

        return $wpdb->update($table, $update, ['session_id' => $session_id]);
    }

    /**
     * Submit drink rating via token (post-experience)
     */
    public static function rate_drink($rating_token, $rating) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        $row = $wpdb->get_row($wpdb->prepare(
            "SELECT id, chosen_drink_id FROM $table WHERE rating_token = %s",
            $rating_token
        ));

        if (!$row) return ['success' => false, 'message' => 'Token inválido.'];

        $wpdb->update($table, [
            'drink_rating'  => max(1, min(5, intval($rating))),
            'drink_rated_at' => current_time('mysql', true),
        ], ['id' => $row->id]);

        return [
            'success'  => true,
            'drink_id' => $row->chosen_drink_id,
        ];
    }

    /**
     * Get quiz session by rating token (for drink rating page)
     */
    public static function get_by_rating_token($token) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE rating_token = %s",
            $token
        ));
    }

    /**
     * Get quiz stats (expanded for dashboard)
     */
    public static function get_stats() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        // Check if table exists before querying
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) {
            return [
                'total' => 0, 'today' => 0, 'completed' => 0, 'completion_rate' => 0,
                'avg_quiz_rating' => 0, 'avg_drink_rating' => 0, 'drink_rating_count' => 0,
                'top_recommended' => [], 'top_chosen' => [],
            ];
        }

        $total = intval($wpdb->get_var("SELECT COUNT(*) FROM $table"));
        $today_total = intval($wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE DATE(created_at) = %s AND completed = 1",
            current_time('Y-m-d')
        )));
        $completed = intval($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE completed = 1"));

        // Ratings
        $avg_quiz = floatval($wpdb->get_var("SELECT AVG(quiz_rating) FROM $table WHERE quiz_rating IS NOT NULL") ?: 0);
        $quiz_rated = intval($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE quiz_rating IS NOT NULL"));
        $avg_drink = floatval($wpdb->get_var("SELECT AVG(drink_rating) FROM $table WHERE drink_rating IS NOT NULL") ?: 0);
        $drink_rated = intval($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE drink_rating IS NOT NULL"));

        // Drinks chosen (count of sessions where a drink was chosen)
        $drinks_chosen = intval($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE chosen_drink_id IS NOT NULL AND chosen_drink_id > 0"));
        $shared_count = intval($wpdb->get_var("SELECT COUNT(*) FROM $table WHERE shared = 1"));

        // Most recommended drinks
        $all_rec = $wpdb->get_col("SELECT recommended_ids FROM $table");
        $rec_count = [];
        foreach ($all_rec as $ids_str) {
            foreach (explode(',', $ids_str) as $id) {
                $id = intval(trim($id));
                if ($id > 0) $rec_count[$id] = ($rec_count[$id] ?? 0) + 1;
            }
        }
        arsort($rec_count);

        // Most chosen drinks
        $chosen_rows = $wpdb->get_results("SELECT chosen_drink_id, COUNT(*) as cnt FROM $table WHERE chosen_drink_id IS NOT NULL GROUP BY chosen_drink_id ORDER BY cnt DESC LIMIT 5");
        $top_chosen = [];
        foreach ($chosen_rows as $r) {
            $top_chosen[intval($r->chosen_drink_id)] = intval($r->cnt);
        }

        return [
            'total'             => $completed, // Only completed quizzes
            'total_sessions'    => $total,     // All sessions (including abandoned)
            'today'             => $today_total,
            'completed'         => $completed,
            'drinks_chosen'     => $drinks_chosen,
            'shared_count'      => $shared_count,
            'completion_rate'   => $total > 0 ? round(($completed / $total) * 100) : 0,
            'avg_quiz_rating'   => round($avg_quiz, 1),
            'quiz_rating_count' => $quiz_rated,
            'avg_drink_rating'  => round($avg_drink, 1),
            'drink_rating_count'=> $drink_rated,
            'top_recommended'   => array_slice($rec_count, 0, 5, true),
            'top_chosen'        => $top_chosen,
        ];
    }

    /**
     * Get daily quiz counts for last N days (for chart)
     */
    public static function get_daily_counts($days = 14) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) return [];

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT DATE(created_at) as day, COUNT(*) as total,
                    SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed
             FROM $table
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL %d DAY)
             GROUP BY DATE(created_at)
             ORDER BY day ASC",
            $days
        ));

        // Fill missing days with zeros
        $data = [];
        for ($i = $days - 1; $i >= 0; $i--) {
            $date = gmdate('Y-m-d', strtotime("-{$i} days"));
            $data[$date] = ['total' => 0, 'completed' => 0];
        }
        foreach ($rows as $r) {
            $data[$r->day] = ['total' => intval($r->total), 'completed' => intval($r->completed)];
        }
        return $data;
    }

    /**
     * Get drink-specific ratings (for satisfaction section)
     */
    public static function get_drink_ratings() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) return [];

        return $wpdb->get_results(
            "SELECT chosen_drink_id, COUNT(*) as times_chosen,
                    AVG(drink_rating) as avg_rating,
                    COUNT(drink_rating) as rated_count
             FROM $table
             WHERE chosen_drink_id IS NOT NULL
             GROUP BY chosen_drink_id
             ORDER BY times_chosen DESC
             LIMIT 10"
        );
    }

    /**
     * Get 7-day average for comparison
     */

    /**
     * Get individual drink rating entries (for listing)
     */
    public static function get_rating_entries($limit = 50) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) return [];

        return $wpdb->get_results($wpdb->prepare(
            "SELECT customer_name, customer_phone, chosen_drink_id, drink_rating, quiz_rating, shared, created_at
             FROM $table
             WHERE drink_rating IS NOT NULL AND chosen_drink_id IS NOT NULL
             ORDER BY created_at DESC
             LIMIT %d",
            $limit
        ));
    }

    /**
     * Get entries with phone but no drink rating yet (for WhatsApp sending)
     */
    public static function get_pending_ratings($limit = 30) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) return [];

        return $wpdb->get_results($wpdb->prepare(
            "SELECT customer_name, customer_phone, chosen_drink_id, rating_token, created_at
             FROM $table
             WHERE customer_phone != '' AND customer_phone IS NOT NULL
               AND chosen_drink_id IS NOT NULL AND chosen_drink_id > 0
               AND (drink_rating IS NULL)
             ORDER BY created_at DESC
             LIMIT %d",
            $limit
        ));
    }

    public static function get_7day_avg() {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_NAME;

        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) return 0;

        $avg = $wpdb->get_var(
            "SELECT COUNT(*) / 7 FROM $table
             WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)"
        );
        return round(floatval($avg), 1);
    }
}
