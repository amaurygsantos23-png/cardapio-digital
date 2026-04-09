<?php

namespace Full\Customer\Api;

use \FullCustomerController;
use \WP_REST_Server;
use \WP_REST_Request;
use \WP_REST_Response;

defined('ABSPATH') || exit;

class Copy extends FullCustomerController
{
  public static function registerRoutes(): void
  {
    $api = new self();

    register_rest_route(self::NAMESPACE, '/copy/answer', [
      [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => [$api, 'processAnswer'],
        'permission_callback' => [$api, 'permissionCallback'],
      ]
    ]);
  }

  public function processAnswer(WP_REST_Request $request): WP_REST_Response
  {
    $answer = $request->get_param('answer');
    $post   = get_posts([
      'post_status' => 'any',
      'meta_query'  => [[
        'key'   => 'queueId',
        'value' => $request->get_param('userId')
      ]]
    ]);

    if (!$post) {
      return new WP_REST_Response(['success' => false]);
    }

    $post = $post[0];

    wp_update_post([
      'ID' => $post->ID,
      'post_status' => 'pending',
      'post_content' => $answer,
      'post_title' => explode(PHP_EOL, $answer)[0],
    ]);

    $post = get_post($post->ID);

    wp_mail(
      get_option('admin_email'),
      'Alerta FULL - Post aguardando revisão: '  . $post->post_title,
      'FULL.copy finalizou a criação do artigo solicitado e o post encontra-se em revisão no seu painel wp-admin.'
    );

    return new WP_REST_Response(['success' => true]);
  }
}
