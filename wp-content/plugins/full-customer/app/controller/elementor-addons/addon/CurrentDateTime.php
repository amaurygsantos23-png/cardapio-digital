<?php

namespace FULL\Customer\ElementorAddons;

use \Elementor\Widget_Base;

class CurrentDateTime extends Widget_Base
{

  public function get_name()
  {
    return 'full_current_date_time';
  }

  public function get_title()
  {
    return 'Data e hora atual';
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
    return ['data'];
  }

  protected function render()
  {
    echo current_time(get_option('date_format', 'd/m/Y') . ' ' . get_option('time_format', 'H:i:s'));
  }
}
