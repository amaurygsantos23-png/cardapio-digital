<?php

namespace FULL\Customer\ElementorAddons;

use \Elementor\Widget_Base;

class RandomNumber extends Widget_Base
{

  public function get_name()
  {
    return 'full_random_hash';
  }

  public function get_title()
  {
    return 'Número aleatório';
  }

  public function get_icon()
  {
    return 'eicon-number-field';
  }

  public function get_categories()
  {
    return [Registrar::CATEGORY];
  }

  public function get_keywords()
  {
    return ['hash'];
  }

  protected function render()
  {
    echo random_int(0, 100);
  }
}
