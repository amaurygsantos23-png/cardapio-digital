<?php

namespace Full\Customer\Api;

use \FullCustomerController;
use \WP_REST_Server;
use \WP_REST_Request;
use \WP_REST_Response;

defined('ABSPATH') || exit;

class Widgets extends FullCustomerController
{
  public static function registerRoutes(): void
  {
    $api = new self();

    register_rest_route(self::NAMESPACE, 'toggle-widget/(?P<widget>[a-z-0-9\-]+)', [
      [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => [$api, 'toggleWidget'],
        'permission_callback' => [$api, 'permissionCallback'],
      ]
    ]);

    register_rest_route(self::NAMESPACE, 'toggle-widgets', [
      [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => [$api, 'toggleWidgets'],
        'permission_callback' => [$api, 'permissionCallback'],
      ]
    ]);
  }

  public function toggleWidgets(WP_REST_Request $request): WP_REST_Response
  {
    $widgets = explode(',', $request->get_param('widgets'));
    $services = $this->env->getEnabledServices();

    foreach ($widgets as $widget) :
      $index = array_search($widget, $services, true);

      if ($index !== false) :
        unset($services[$index]);
      else :
        $services[] = $widget;
      endif;
    endforeach;

    $this->env->setEnabledServices($services);

    return new WP_REST_Response($this->env->getEnabledServices());
  }

  public function toggleWidget(WP_REST_Request $request): WP_REST_Response
  {
    $widget = $request->get_param('widget');

    $services = $this->env->getEnabledServices();
    $index = array_search($widget, $services, true);

    if ($index !== false) :
      unset($services[$index]);
    else :
      $services[] = $widget;
    endif;

    $this->env->setEnabledServices($services);

    return new WP_REST_Response($this->env->getEnabledServices());
  }
}
