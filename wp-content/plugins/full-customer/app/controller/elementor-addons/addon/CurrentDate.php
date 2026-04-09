<?php

namespace FULL\Customer\ElementorAddons;

use \Elementor\Widget_Base;

class CurrentDate extends Widget_Base
{

  public function get_name()
  {
    return 'full_current_date';
  }

  public function get_title()
  {
    return 'Data atual';
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
    return ['data', 'hora'];
  }

  protected function render()
  {
    echo current_time(get_option('date_format', 'd/m/Y'));
  }
}
