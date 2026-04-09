<?php

namespace FULL\Customer\ElementorAddons;

use Elementor\Elements_Manager;

class Registrar
{
  public const CATEGORY = 'full-addons';

  private function __construct() {}

  public static function attach(): void
  {
    $cls = new self();
    add_action('elementor/elements/categories_registered', [$cls, 'registerCategory']);
    add_action('elementor/widgets/register', [$cls, 'registerWidgets']);
  }

  public function registerCategory(Elements_Manager $manager): void
  {
    $manager->add_category(
      self::CATEGORY,
      [
        'title' => 'FULL.',
        'icon' => 'fa fa-plug',
        'sort' => 'a-z',
        'hideIfEmpty' => false
      ]
    );
  }

  public function registerWidgets($widgets_manager): void
  {
    $baseDir = FULL_CUSTOMER_APP . '/controller/elementor-addons/addon';
    $files = array_diff(scandir($baseDir), ['..', '.', '.htaccess']);

    foreach ($files as $file) :
      if (strpos($file, '.php') === false) continue;

      require_once $baseDir . '/' . $file;

      $className = str_replace('.php', '', $file);
      $className = 'FULL\Customer\ElementorAddons\\' . $className;
      $widgets_manager->register_widget_type(new $className());
    endforeach;
  }
}

Registrar::attach();
