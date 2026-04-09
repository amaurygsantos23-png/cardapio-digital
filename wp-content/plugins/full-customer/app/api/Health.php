<?php

namespace Full\Customer\Api;

use Full\Customer\Health as CustomerHealth;
use \FullCustomerController;
use \WP_REST_Server;
use \WP_REST_Response;

defined('ABSPATH') || exit;

class Health extends FullCustomerController
{
  public static function registerRoutes(): void
  {
    $api = new self();

    register_rest_route(self::NAMESPACE, '/health', [
      [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [$api, 'getHeathStats'],
        'permission_callback' => [$api, 'permissionCallback'],
      ]
    ]);
  }

  public function getHeathStats(): WP_REST_Response
  {
    return new WP_REST_Response([
      'results' => $this->env->isServiceEnabled('full-security') ? (new CustomerHealth)->getResults() : [],
    ]);
  }
}
