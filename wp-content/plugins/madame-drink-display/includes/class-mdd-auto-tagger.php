<?php
if (!defined('ABSPATH')) exit;

/**
 * Analisa descrições dos drinks e sugere/atribui tags de perfil automaticamente.
 * Útil para mapear drinks já cadastrados sem precisar tagear manualmente.
 */
class MDD_Auto_Tagger {

    /**
     * Mapa de palavras-chave para tags de perfil
     */
    private static function get_keyword_map() {
        return [
            // Sabores
            'citrico' => [
                'limão', 'lima', 'laranja', 'tangerina', 'siciliano', 'tahiti',
                'citrus', 'cítrico', 'cítricas', 'citric', 'grapefruit', 'pomelo',
                'limao', 'acido', 'ácido',
            ],
            'doce' => [
                'doce', 'açúcar', 'açucar', 'leite condensado', 'mel', 'rapadura',
                'xarope', 'licor', 'grenadine', 'caramelo', 'chocolate', 'baunilha',
                'sweet', 'sugar', 'condensado',
            ],
            'tropical' => [
                'maracujá', 'maracuja', 'abacaxi', 'coco', 'manga', 'kiwi',
                'morango', 'frutas', 'fruta', 'tropical', 'açaí', 'acai',
                'banana', 'goiaba', 'lichia', 'pitaya',
            ],
            'refrescante' => [
                'refrescante', 'gelado', 'gelo', 'soda', 'tônica', 'tonica',
                'água com gás', 'sparkling', 'refreshing', 'ice', 'frozen',
                'fresco', 'cool', 'mint', 'hortelã', 'hortela',
            ],
            'cremoso' => [
                'cremoso', 'cream', 'leite', 'creme', 'condensado', 'nata',
                'batida', 'smoothie', 'milkshake', 'encorpado', 'espesso',
                'baileys', 'irish cream',
            ],
            'forte' => [
                'forte', 'intenso', 'puro', 'straight', 'neat', 'double',
                'dose dupla', 'whisky', 'whiskey', 'bourbon', 'cachaça ouro',
                'envelhecida', 'aged', 'reserva',
            ],
            'suave' => [
                'suave', 'leve', 'light', 'delicado', 'mild', 'soft',
                'fraco', 'pouco álcool', 'low abv',
            ],
            'herbaceo' => [
                'hortelã', 'hortela', 'manjericão', 'manjericao', 'alecrim',
                'ervas', 'herbal', 'herb', 'menta', 'tomilho', 'gengibre',
                'camomila', 'capim-limão', 'botanical',
            ],
            'efervescente' => [
                'espumante', 'prosecco', 'champagne', 'sparkling', 'gás',
                'gas', 'tônica', 'tonica', 'soda', 'schweppes', 'sprite',
                'energético', 'energetico', 'red bull',
            ],

            // Bases
            'vodka' => [
                'vodka', 'smirnoff', 'absolut', 'ciroc', 'grey goose',
                'ketel one', 'belvedere', 'stolichnaya',
            ],
            'gin' => [
                'gin', 'tanqueray', 'hendricks', 'bombay', 'beefeater',
                'gordon', 'monkey 47', 'bulldog', 'aviation',
            ],
            'rum' => [
                'rum', 'ron', 'bacardi', 'havana club', 'captain morgan',
                'malibu', 'kraken', 'mount gay',
            ],
            'cachaca' => [
                'cachaça', 'cachaca', 'pinga', 'caipirinha', 'aguardente',
                'caninha', 'ypioca', 'velho barreiro', 'salinas', 'rapadura',
            ],
            'whisky' => [
                'whisky', 'whiskey', 'bourbon', 'jack daniel', 'johnnie walker',
                'chivas', 'jameson', 'ballantine', 'grant',
            ],
            'sem-alcool' => [
                'sem álcool', 'sem alcool', 'zero álcool', 'zero alcool',
                'mocktail', 'virgin', 'analcóolico', 'analcolico',
                'suco', 'refrigerante', 'não alcoólico', 'nao alcoolico',
            ],

            // Categorias de momento
            'aperitivo' => [
                'aperol', 'campari', 'spritz', 'negroni', 'aperitivo',
                'bitter', 'vermouth', 'vermute',
            ],
            'classico' => [
                'clássico', 'classico', 'classic', 'tradicional', 'original',
                'old fashioned', 'manhattan', 'martini', 'daiquiri',
            ],
            'premium' => [
                'premium', 'especial', 'exclusivo', 'signature', 'reserva',
                'top shelf', 'deluxe', 'seleção', 'selecao',
            ],
        ];
    }

    /**
     * Analisa um drink e retorna tags sugeridas com scores
     */
    public static function analyze_drink($post_id) {
        $post = get_post($post_id);
        if (!$post) return [];

        // Combine title + content + excerpt + meta for analysis
        $text = strtolower(implode(' ', [
            $post->post_title,
            $post->post_content,
            $post->post_excerpt,
            get_post_meta($post_id, '_short_description', true),
            get_post_meta($post_id, 'short_description', true),
            get_post_meta($post_id, 'descricao_curta', true),
            get_post_meta($post_id, '_ingredients', true),
            get_post_meta($post_id, 'ingredients', true),
            get_post_meta($post_id, 'ingredientes', true),
        ]));

        // Remove accents for matching
        $text_normalized = self::remove_accents($text);
        $map = self::get_keyword_map();
        $scores = [];

        foreach ($map as $tag => $keywords) {
            $score = 0;
            foreach ($keywords as $kw) {
                $kw_lower = strtolower($kw);
                $kw_normalized = self::remove_accents($kw_lower);

                // Check in both original and normalized text
                if (strpos($text, $kw_lower) !== false || strpos($text_normalized, $kw_normalized) !== false) {
                    $score++;
                }
            }
            if ($score > 0) {
                $scores[$tag] = $score;
            }
        }

        arsort($scores);
        return $scores;
    }

    /**
     * Auto-tag a single drink
     */
    public static function auto_tag_drink($post_id, $min_score = 1, $max_tags = 8) {
        $scores = self::analyze_drink($post_id);

        if (empty($scores)) return [];

        // Filter by minimum score and limit
        $tags = [];
        foreach ($scores as $tag => $score) {
            if ($score >= $min_score && count($tags) < $max_tags) {
                $tags[] = $tag;
            }
        }

        if (empty($tags)) return [];

        // Ensure terms exist in taxonomy
        foreach ($tags as $tag_slug) {
            if (!term_exists($tag_slug, 'mdd_drink_profile')) {
                $label = ucfirst(str_replace('-', ' ', $tag_slug));
                wp_insert_term($label, 'mdd_drink_profile', ['slug' => $tag_slug]);
            }
        }

        // Assign tags
        wp_set_object_terms($post_id, $tags, 'mdd_drink_profile');

        return $tags;
    }

    /**
     * Auto-tag all drinks
     */
    public static function auto_tag_all($overwrite = false) {
        $post_type = get_option('mdd_drink_post_type', 'drink');

        $posts = get_posts([
            'post_type'      => $post_type,
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
        ]);

        $results = [];

        foreach ($posts as $post_id) {
            // Skip if already has tags and not overwriting
            if (!$overwrite) {
                $existing = wp_get_object_terms($post_id, 'mdd_drink_profile', ['fields' => 'slugs']);
                if (!is_wp_error($existing) && !empty($existing)) {
                    $results[$post_id] = ['status' => 'skipped', 'tags' => $existing];
                    continue;
                }
            }

            $tags = self::auto_tag_drink($post_id);
            $results[$post_id] = ['status' => 'tagged', 'tags' => $tags];
        }

        return $results;
    }

    /**
     * Get suggestions for a drink without applying them
     */
    public static function get_suggestions($post_id) {
        $scores = self::analyze_drink($post_id);
        $suggestions = [];

        foreach ($scores as $tag => $score) {
            $suggestions[] = [
                'tag'   => $tag,
                'label' => ucfirst(str_replace('-', ' ', $tag)),
                'score' => $score,
            ];
        }

        return $suggestions;
    }

    /**
     * Remove accents from string
     */
    private static function remove_accents($string) {
        $search  = ['á','à','ã','â','é','ê','í','ó','ô','õ','ú','ü','ç','ñ'];
        $replace = ['a','a','a','a','e','e','i','o','o','o','u','u','c','n'];
        return str_replace($search, $replace, $string);
    }
}
