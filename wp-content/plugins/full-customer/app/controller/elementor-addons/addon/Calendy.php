<?php

namespace FULL\Customer\ElementorAddons;

use \Elementor\Widget_Base;
use \Elementor\Controls_Manager;

class Calendy extends Widget_Base
{

  public function get_name()
  {
    return 'full_calendly';
  }

  public function get_title()
  {
    return 'Calendly';
  }

  public function get_icon()
  {
    return 'eicon-calendar';
  }

  public function get_categories()
  {
    return [Registrar::CATEGORY];
  }

  public function get_keywords()
  {
    return ['calendly', 'full', 'calendario', 'agendamento', 'booking'];
  }

  protected function register_controls()
  {
    $this->start_controls_section(
      'section_title',
      [
        'label' => 'Configurações',
        'tab' => Controls_Manager::TAB_CONTENT,
      ]
    );

    $this->add_control(
      'embed_url',
      [
        'type' => Controls_Manager::URL,
        'label' => 'URL do calendário'
      ]
    );

    $this->add_control(
      'embed_mode',
      [
        'type' => Controls_Manager::SELECT,
        'label' => 'Modo de exibição',
        'default' => 'inline',
        'options' => [
          'inline' => 'Em linha',
          'popup'  => 'Popup',
          'link'   => 'Botão'
        ]
      ]
    );

    $this->add_control(
      'link_text',
      [
        'type' => Controls_Manager::TEXT,
        'label' => 'Texto do link',
        'default' => 'Agendar',
        'condition' => [
          'embed_mode!' => 'inline',
        ],
      ]
    );

    $this->add_control(
      'link_color',
      [
        'type' => Controls_Manager::COLOR,
        'label' => 'Cor do link',
        'condition' => [
          'embed_mode' => 'link',
        ],
      ]
    );

    $this->add_control(
      'event_details',
      [
        'type'          => Controls_Manager::SWITCHER,
        'label'         => 'Detalhes do evento',
        'label_on'      => 'Exibir',
        'label_off'     => 'Ocultar',
        'return_value'  => 'yes',
        'default'       => 'yes',
      ]
    );

    $this->add_control(
      'cookies',
      [
        'type'          => Controls_Manager::SWITCHER,
        'label'         => 'Aviso de cookies',
        'label_on'      => 'Exibir',
        'label_off'     => 'Ocultar',
        'return_value'  => 'yes',
        'default'       => 'yes',
      ]
    );

    $this->add_control(
      'color_bg',
      [
        'type' => Controls_Manager::COLOR,
        'label' => 'Cor de fundo - Calendário'
      ]
    );

    $this->add_control(
      'color_text',
      [
        'type' => Controls_Manager::COLOR,
        'label' => 'Cor do texto - Calendário'
      ]
    );

    $this->add_control(
      'color_button',
      [
        'type' => Controls_Manager::COLOR,
        'label' => 'Cor do botão - Calendário'
      ]
    );

    $this->end_controls_section();
  }

  protected function render()
  {
    $settings = wp_parse_args($this->get_settings_for_display(), [
      'embed_mode' => 'inline',
      'embed_url'  => [
        'url' => ''
      ],
      'link_text'     => 'Agendar',
      'link_color'    => '',
      'event_details' => 'yes',
      'cookies'       => 'yes',
      'color_bg'      => '',
      'color_text'    => '',
      'color_button'  => '',
    ]);

    $render = 'render_' . $settings['embed_mode'];

    echo '<link href="https://assets.calendly.com/assets/external/widget.css" rel="stylesheet">';
    echo '<script src="https://assets.calendly.com/assets/external/widget.js" type="text/javascript" async></script>';

    if (!$settings['embed_url']['url']) {
      echo 'Preencha a URL do calendário';
      return;
    }

    $config = [
      'text'  => $settings['link_text'],
      'color' => $settings['color_button'],
      'textColor' => $settings['color_text'],
      'url'   => esc_url(add_query_arg([
        'hide_gdpr_banner' => $settings['cookies'] === 'yes' ? 0 : 1,
        'hide_event_type_details' => $settings['event_details'] === 'yes' ? 0 : 1,
        'background_color' => str_replace('#', '', $settings['color_bg']),
        'text_color' => str_replace('#', '', $settings['color_text']),
        'primary_color' => str_replace('#', '', $settings['color_button']),
      ], $settings['embed_url']['url']))
    ];

    $this->$render($config, $settings);
  }

  protected function render_inline(array $config, array $rawSettings)
  {
    echo '<div class="calendly-inline-widget" data-url="' . $config['url'] . '" data-resize="true" style="min-width:320px;height:700px;"></div>';
  }

  protected function render_popup(array $config, array $rawSettings)
  {
    $editor = isset($_REQUEST['action']) && $_REQUEST['action'] === 'elementor_ajax';

    if ($editor) {
      echo 'Esta versão funciona apenas fora do editor do Elementor.';
      return;
    }

    echo '<script>window.onload = function() {Calendly.initBadgeWidget(' . wp_json_encode($config) . ')}</script>';
  }

  protected function render_link(array $config, array $rawSettings)
  {
    $style = $rawSettings['link_color'] ? 'color:' . $rawSettings['link_color'] . ';' : '';
    echo '<a style="' . $style . '" href="" onclick=\'Calendly.initPopupWidget(' . wp_json_encode($config) . ') ; return false;\'>' . $config['text'] . '</a>';
  }
}
