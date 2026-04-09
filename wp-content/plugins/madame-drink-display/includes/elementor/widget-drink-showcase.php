<?php
if (!defined('ABSPATH')) exit;

class MDD_Elementor_Drink_Showcase_Widget extends \Elementor\Widget_Base {

    public function get_name() { return 'mdd_drink_showcase'; }
    public function get_title() { return __('Vitrine de Drinks', 'madame-drink-display'); }
    public function get_icon() { return 'eicon-gallery-grid'; }
    public function get_categories() { return ['madame-drink-display']; }
    public function get_keywords() { return ['drink', 'showcase', 'menu', 'madame', 'cardapio']; }

    protected function register_controls() {
        $this->start_controls_section('content', [
            'label' => __('Conteúdo', 'madame-drink-display'),
        ]);

        $this->add_control('count', [
            'label'   => __('Quantidade', 'madame-drink-display'),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 4,
            'min'     => 1,
            'max'     => 20,
        ]);

        // Get available categories
        $bridge = new MDD_CPT_Bridge();
        $categories = $bridge->get_categories();
        $cat_options = ['' => __('Todas as categorias', 'madame-drink-display')];
        foreach ($categories as $cat) {
            $cat_options[$cat['slug']] = $cat['name'];
        }

        $this->add_control('category', [
            'label'   => __('Categoria', 'madame-drink-display'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => '',
            'options' => $cat_options,
        ]);

        $this->add_responsive_control('columns', [
            'label'   => __('Colunas', 'madame-drink-display'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => '2',
            'options' => ['1' => '1', '2' => '2', '3' => '3', '4' => '4'],
            'selectors' => ['{{WRAPPER}} .mdd-showcase-grid' => 'grid-template-columns: repeat({{VALUE}}, 1fr);'],
        ]);

        $this->add_control('show_price', [
            'label'   => __('Exibir Preço', 'madame-drink-display'),
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('show_description', [
            'label'   => __('Exibir Descrição', 'madame-drink-display'),
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('show_category_badge', [
            'label'   => __('Badge de Categoria', 'madame-drink-display'),
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_control('link_to_quiz', [
            'label'       => __('Botão do Quiz', 'madame-drink-display'),
            'type'        => \Elementor\Controls_Manager::SWITCHER,
            'default'     => 'no',
            'description' => __('Adiciona um botão "Faça o Quiz" após a vitrine', 'madame-drink-display'),
        ]);

        $this->end_controls_section();

        // Style
        $this->start_controls_section('card_style', [
            'label' => __('Estilo do Card', 'madame-drink-display'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('card_bg', [
            'label'     => __('Fundo do Card', 'madame-drink-display'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => '#f9f9f9',
            'selectors' => ['{{WRAPPER}} .mdd-showcase-card' => 'background-color: {{VALUE}};'],
        ]);

        $this->add_control('card_border_radius', [
            'label'      => __('Arredondamento', 'madame-drink-display'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'default'    => ['size' => 12],
            'selectors'  => ['{{WRAPPER}} .mdd-showcase-card' => 'border-radius: {{SIZE}}px; overflow: hidden;'],
        ]);

        $this->add_control('price_color', [
            'label'     => __('Cor do Preço', 'madame-drink-display'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => get_option('mdd_primary_color', '#C8962E'),
            'selectors' => ['{{WRAPPER}} .mdd-showcase-price' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('gap', [
            'label'      => __('Espaçamento', 'madame-drink-display'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'range'      => ['px' => ['min' => 0, 'max' => 40]],
            'default'    => ['size' => 16],
            'selectors'  => ['{{WRAPPER}} .mdd-showcase-grid' => 'gap: {{SIZE}}px;'],
        ]);

        $this->end_controls_section();
    }

    protected function render() {
        $s = $this->get_settings_for_display();

        $bridge = new MDD_CPT_Bridge();
        $args = [];
        if (!empty($s['category'])) {
            $args['category'] = $s['category'];
        }

        $drinks = $bridge->get_drinks($args);
        $drinks = array_slice($drinks, 0, intval($s['count']));

        if (empty($drinks)) {
            echo '<p style="text-align:center;color:#999">' . __('Nenhum drink encontrado.', 'madame-drink-display') . '</p>';
            return;
        }

        $primary = get_option('mdd_primary_color', '#C8962E');
        ?>
        <div class="mdd-showcase-grid" style="display:grid;gap:16px">
            <?php foreach ($drinks as $d):
                $cat = $d['categories'][0]['name'] ?? '';
            ?>
                <div class="mdd-showcase-card" style="border:1px solid #eee;overflow:hidden">
                    <?php if ($d['image']): ?>
                        <div style="aspect-ratio:1;overflow:hidden;background:#eee;position:relative">
                            <img src="<?php echo esc_url($d['image']); ?>" alt="<?php echo esc_attr($d['title']); ?>"
                                 style="width:100%;height:100%;object-fit:cover" loading="lazy">
                            <?php if ($s['show_category_badge'] === 'yes' && $cat): ?>
                                <span style="position:absolute;top:10px;right:10px;padding:4px 10px;background:rgba(0,0,0,.6);color:#fff;border-radius:12px;font-size:10px;text-transform:uppercase;letter-spacing:1px"><?php echo esc_html($cat); ?></span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div style="padding:14px 16px">
                        <h4 style="margin:0 0 4px;font-size:16px;font-weight:600"><?php echo esc_html($d['title']); ?></h4>
                        <?php if ($s['show_description'] === 'yes' && $d['short_desc']): ?>
                            <p style="margin:0 0 8px;font-size:13px;color:#777;line-height:1.4"><?php echo esc_html($d['short_desc']); ?></p>
                        <?php endif; ?>
                        <?php if ($s['show_price'] === 'yes' && $d['price_formatted']): ?>
                            <span class="mdd-showcase-price" style="font-size:17px;font-weight:700"><?php echo esc_html($d['price_formatted']); ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?php if ($s['link_to_quiz'] === 'yes'):
            $quiz_url = MDD_QR_Generator::get_quiz_url();
        ?>
            <div style="text-align:center;padding-top:20px">
                <a href="<?php echo esc_url($quiz_url); ?>" target="_blank"
                   style="display:inline-block;padding:12px 32px;background:<?php echo esc_attr($primary); ?>;color:#fff;border-radius:30px;text-decoration:none;font-weight:600;font-size:15px">
                    Descubra seu Drink Ideal 🍸
                </a>
            </div>
        <?php endif; ?>
        <?php
    }
}
