<?php

namespace Full\Customer\Elementor;

use stdClass;

defined('ABSPATH') || exit;

class TemplateManager
{
  private static array $instances = [];

  protected function __clone()
  {
    throw new \Exception("Cannot clone a singleton.");
  }

  public function __wakeup()
  {
    throw new \Exception("Cannot wakeup a singleton.");
  }

  public static function instance(): self
  {
    $cls = static::class;
    if (!isset(self::$instances[$cls])) {
      self::$instances[$cls] = new static();
    }

    return self::$instances[$cls];
  }

  public function getItem(int $itemId): ?stdClass
  {
    $url  = getFullDashboardApiUrl('-customer/v1/single-template/' . $itemId);

    $payload = [
      'site'  => site_url(),
      'id'    => $itemId,
    ];

    $request = wp_remote_get($url, ['sslverify' => false, 'body' => $payload]);
    $response = wp_remote_retrieve_body($request);

    $item  = json_decode($response);

    $item->canBeInstalled = true;

    return $item && isset($item->id) ? $item : null;
  }

  public function getCloudItem(int $itemId): ?stdClass
  {
    $item = get_transient('full/cloud/' . $itemId);

    if (!$item) :
      $url  = getFullDashboardApiUrl('-customer/v1/template/cloud/');

      $payload = [
        'site'  => site_url(),
        'id'    => $itemId,
      ];

      $request = wp_remote_get($url, ['sslverify' => false, 'body' => $payload]);
      $response = wp_remote_retrieve_body($request);

      $item  = json_decode($response);
      set_transient('full/cloud/' . $itemId, $item, MONTH_IN_SECONDS);
    endif;

    return $item && isset($item->id) ? $item : null;
  }
}
