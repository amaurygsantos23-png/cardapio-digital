<?php

namespace FULL\Customer\ElementorAddons;

use \Elementor\Widget_Base;

class RandomHash extends Widget_Base
{

  public function get_name()
  {
    return 'full_random_hash';
  }

  public function get_title()
  {
    return 'Hash aleatório';
  }

  public function get_icon()
  {
    return 'eicon-text-field';
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
    echo strtoupper(bin2hex(random_bytes(5)));
  }
}
