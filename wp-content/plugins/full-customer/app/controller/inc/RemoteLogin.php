<?php

namespace Full\Customer;

defined('ABSPATH') || exit;

class RemoteLogin
{
  const QUERY_ARG = 'full_customer';

  private function __construct()
  {
  }

  public static function attach(): void
  {
    $cls     = new self();
    $request = filter_input(INPUT_GET, self::QUERY_ARG);

    if (!$request || !method_exists($cls, $request)) :
      return;
    endif;

    add_action('init', function () use ($cls, $request): void {
      call_user_func([$cls, $request]);
    });
  }

  public function login(): void
  {
    add_filter('application_password_is_api_request', '__return_true', PHP_INT_MAX);

    $hash   = filter_input(INPUT_GET, 'auth');
    $auth   = explode(':', base64_decode($hash));

    if (count($auth) !== 2) :
      wp_send_json_error();
    endif;

    $user = wp_authenticate_application_password(null, $auth[0], $auth[1]);

    if (!$user || is_wp_error($user)) :
      wp_send_json_error();
    endif;

    wp_clear_auth_cookie();

    wp_set_current_user($user->ID, $user->user_login);
    wp_set_auth_cookie($user->ID);

    do_action('wp_login', $user->user_login, $user);

    add_action('template_redirect', [$this, 'redirect'], 0);
  }

  public function redirect(): void
  {
    wp_safe_redirect(admin_url());
  }
}

RemoteLogin::attach();
