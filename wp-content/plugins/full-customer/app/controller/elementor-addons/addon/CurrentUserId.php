<?php

namespace FULL\Customer\ElementorAddons;

use \Elementor\Widget_Base;

class CurrentUserId extends Widget_Base
{

  public function get_name()
  {
    return 'full_current_user_id';
  }

  public function get_title()
  {
    return 'ID do usuário atual';
  }

  public function get_icon()
  {
    return 'eicon-user-circle-o';
  }

  public function get_categories()
  {
    return [Registrar::CATEGORY];
  }

  public function get_keywords()
  {
    return ['usuario', 'id'];
  }

  protected function render()
  {
    echo is_user_logged_in() ? wp_get_current_user()->ID : '';
  }
}
