<?php

namespace FULL\Customer\ElementorAddons;

use \Elementor\Widget_Base;

class LogoutUrl extends Widget_Base
{

  public function get_name()
  {
    return 'full_logout_url';
  }

  public function get_title()
  {
    return 'URL de logout';
  }

  public function get_icon()
  {
    return 'eicon-lock-user';
  }

  public function get_categories()
  {
    return [Registrar::CATEGORY];
  }

  public function get_keywords()
  {
    return ['login'];
  }

  protected function render()
  {
    echo '<a href="' . esc_url(wp_logout_url()) . '">Sair</a>';
  }
}
