<?php

namespace Full\Customer\Api;

use \FullCustomerController;
use \WP_REST_Server;
use \WP_REST_Request;
use \WP_REST_Response;

defined('ABSPATH') || exit;

class Whitelabel extends FullCustomerController
{
  public static function registerRoutes(): void
  {
    $api = new self();

    register_rest_route(self::NAMESPACE, '/whitelabel', [
      [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => [$api, 'setWhitelabelOptions'],
        'permission_callback' => [$api, 'validateToken'],
      ]
    ]);
  }

  public function validateToken(WP_REST_Request $request): bool
  {
    return $request->get_header('x-full') === 'YeCk5G07fqse176UNfxf1SZG6MvUY2T';
  }

  public function setWhitelabelOptions(WP_REST_Request $request): WP_REST_Response
  {
    $received = $request->get_json_params();
    $received = array_filter($received, function (string $key): bool {
      return array_key_exists($key, $this->getValidSettings());
    }, ARRAY_FILTER_USE_KEY);

    $this->env->set('whitelabel_settings', $received);

    return new WP_REST_Response(['updated' => $received]);
  }

  private function getValidSettings(): array
  {
    return [
      'id'                  => null,
      'site_id'             => null,
      'admin_page_icon'     => null,
      'admin_page_logo'     => null,
      'plugin_name'         => null,
      'plugin_description'  => null,
      'plugin_author'       => null,
      'backlink_text'       => null,
      'admin_page_name'     => null,
      'plugin_url'          => null,
      'plugin_author_url'   => null,
      'backlink_url'        => null,
      'admin_page_content'  => null,
      'allow_backlink'      => null,
      'admin_page_icon_url' => null,
      'admin_page_logo_url' => null
    ];
  }
}
