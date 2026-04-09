<?php

namespace Full\Customer\Api;

use \FullCustomerController;
use Plugin_Upgrader;
use WP_Ajax_Upgrader_Skin;
use \WP_REST_Server;
use \WP_REST_Request;
use \WP_REST_Response;

defined('ABSPATH') || exit;

class PluginUpdate extends FullCustomerController
{
  public function __construct()
  {
    parent::__construct();
  }

  public static function registerRoutes(): void
  {
    $api = new self();

    register_rest_route(self::NAMESPACE, '/update-plugin/(?P<plugin>\S+)', [
      [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => [$api, 'updatePlugin'],
        'permission_callback' => [$api, 'receivedValidUpdateToken'],
      ]
    ]);
  }

  public function receivedValidUpdateToken(WP_REST_Request $request): bool
  {
    $token = $request->get_header('x-full');
    $uri  = getFullDashboardApiUrl('-customer/v1/valid-update-plugin-token');

    $response = wp_remote_post($uri, [
      'sslverify' => false,
      'headers' => [
        'Content-type' => 'application/json',
      ],
      'body'  => wp_json_encode(['token' => $token])
    ]);

    $data = json_decode(wp_remote_retrieve_body($response));

    return isset($data->success) && $data->success;
  }

  public function updatePlugin(WP_REST_Request $request): WP_REST_Response
  {
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';

    wp_update_plugins();

    $plugin   = sanitize_title($request->get_param('plugin'));
    $plugin   = $this->getPlugin($plugin);

    if ($plugin === null || $plugin === '' || $plugin === '0') :
      return new WP_REST_Response(['error' => 'Plugin não localizado para atualização.']);
    endif;

    $skin     = new WP_Ajax_Upgrader_Skin();
    $upgrader = new Plugin_Upgrader($skin);

    $result   = $upgrader->bulk_upgrade([$plugin]);

    if (is_wp_error($skin->result)) :
      return new WP_REST_Response(['error' => $skin->result->get_error_message()]);

    elseif ($skin->get_errors()->has_errors()) :
      return new WP_REST_Response(['error' => $skin->get_error_messages()]);

    elseif (is_array($result) && !empty($result[$plugin])) :
      if (true === $result[$plugin]) :
        return new WP_REST_Response(['error' => 'O plugin já está em sua versão mais recente']);
      endif;

      return new WP_REST_Response(['success' => true]);
    elseif (false === $result) :
      return new WP_REST_Response(['error' => 'Não foi possível conectar-se ao sistema de arquivos. Por favor, confirme suas credenciais.']);

    endif;

    return new WP_REST_Response(['error' => 'Um erro desconhecido aconteceu']);
  }

  private function getPlugin(string $slug): ?string
  {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';

    $plugin = null;

    foreach (array_keys(get_plugins()) as $key) :
      $keySlug = explode('/', $key)[0];

      if ($keySlug === $slug) :
        $plugin = $key;
        break;
      endif;
    endforeach;

    return $plugin;
  }
}
