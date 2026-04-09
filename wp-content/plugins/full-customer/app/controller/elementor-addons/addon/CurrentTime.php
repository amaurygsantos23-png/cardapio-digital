<?php

namespace FULL\Customer\ElementorAddons;

use \Elementor\Widget_Base;

class CurrentTime extends Widget_Base
{

  public function get_name()
  {
    return 'full_current_time';
  }

  public function get_title()
  {
    return 'Hora atual';
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
    return ['hora'];
  }

  protected function render()
  {
    echo current_time(get_option('time_format', 'H:i:s'));
  }
}
