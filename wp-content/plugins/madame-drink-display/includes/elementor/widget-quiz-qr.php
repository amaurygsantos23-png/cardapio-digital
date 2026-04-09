<?php
if (!defined('ABSPATH')) exit;

class MDD_Elementor_Quiz_QR_Widget extends \Elementor\Widget_Base {

    public function get_name() { return 'mdd_quiz_qr'; }
    public function get_title() { return __('Quiz QR Code', 'madame-drink-display'); }
    public function get_icon() { return 'eicon-barcode'; }
    public function get_categories() { return ['madame-drink-display']; }
    public function get_keywords() { return ['quiz', 'qr', 'drink', 'madame']; }

    protected function register_controls() {
        $this->start_controls_section('content', [
            'label' => __('Conteúdo', 'madame-drink-display'),
        ]);

        $this->add_control('title', [
            'label'   => __('Título', 'madame-drink-display'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Descubra seu drink ideal!',
        ]);

        $this->add_control('subtitle', [
            'label'   => __('Subtítulo', 'madame-drink-display'),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => 'Escaneie com a câmera do celular',
        ]);

        $this->add_control('qr_size', [
            'label'   => __('Tamanho do QR', 'madame-drink-display'),
            'type'    => \Elementor\Controls_Manager::SELECT,
            'default' => '200',
            'options' => ['150' => '150px', '200' => '200px', '300' => '300px', '400' => '400px'],
        ]);

        $this->add_control('show_title', [
            'label'   => __('Exibir Título', 'madame-drink-display'),
            'type'    => \Elementor\Controls_Manager::SWITCHER,
            'default' => 'yes',
        ]);

        $this->add_responsive_control('align', [
            'label'   => __('Alinhamento', 'madame-drink-display'),
            'type'    => \Elementor\Controls_Manager::CHOOSE,
            'options' => [
                'left'   => ['title' => __('Esquerda', 'madame-drink-display'), 'icon' => 'eicon-text-align-left'],
                'center' => ['title' => __('Centro', 'madame-drink-display'),   'icon' => 'eicon-text-align-center'],
                'right'  => ['title' => __('Direita', 'madame-drink-display'),  'icon' => 'eicon-text-align-right'],
            ],
            'default'   => 'center',
            'selectors' => ['{{WRAPPER}} .mdd-qr-widget' => 'text-align: {{VALUE}};'],
        ]);

        $this->end_controls_section();

        // Style section
        $this->start_controls_section('style', [
            'label' => __('Estilo', 'madame-drink-display'),
            'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('title_color', [
            'label'     => __('Cor do Título', 'madame-drink-display'),
            'type'      => \Elementor\Controls_Manager::COLOR,
            'default'   => get_option('mdd_primary_color', '#C8962E'),
            'selectors' => ['{{WRAPPER}} .mdd-qr-widget__title' => 'color: {{VALUE}};'],
        ]);

        $this->add_control('qr_border_radius', [
            'label'      => __('Borda do QR', 'madame-drink-display'),
            'type'       => \Elementor\Controls_Manager::SLIDER,
            'range'      => ['px' => ['min' => 0, 'max' => 30]],
            'default'    => ['size' => 12],
            'selectors'  => ['{{WRAPPER}} .mdd-qr-widget__img' => 'border-radius: {{SIZE}}px;'],
        ]);

        $this->end_controls_section();
    }

    protected function render() {
        $s = $this->get_settings_for_display();
        $qr_url = MDD_QR_Generator::get_quiz_qr_url(intval($s['qr_size']));
        $quiz_url = MDD_QR_Generator::get_quiz_url();
        ?>
        <div class="mdd-qr-widget">
            <?php if ($s['show_title'] === 'yes'): ?>
                <p class="mdd-qr-widget__title" style="font-size:18px;font-weight:600;margin-bottom:12px">
                    <?php echo esc_html($s['title']); ?>
                </p>
            <?php endif; ?>
            <a href="<?php echo esc_url($quiz_url); ?>" target="_blank">
                <img class="mdd-qr-widget__img" src="<?php echo esc_url($qr_url); ?>" alt="Quiz QR Code"
                     width="<?php echo intval($s['qr_size']); ?>" height="<?php echo intval($s['qr_size']); ?>"
                     style="border:2px solid #eee">
            </a>
            <?php if ($s['show_title'] === 'yes' && !empty($s['subtitle'])): ?>
                <p style="font-size:13px;color:#999;margin-top:8px"><?php echo esc_html($s['subtitle']); ?></p>
            <?php endif; ?>
        </div>
        <?php
    }
}
