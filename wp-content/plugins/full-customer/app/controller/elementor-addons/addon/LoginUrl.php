<?php

namespace FULL\Customer\ElementorAddons;

use \Elementor\Widget_Base;

class LoginUrl extends Widget_Base
{

  public function get_name()
  {
    return 'full_login_url';
  }

  public function get_title()
  {
    return 'URL de login';
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
    echo '<a href="' . esc_url(wp_login_url()) . '">Entrar</a>';
  }
}
