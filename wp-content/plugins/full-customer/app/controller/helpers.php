<?php

use Full\Customer\License;

defined('ABSPATH') || exit;

function getFullEnv(): string
{
  return strtoupper(defined('FULL_CUSTOMER') ? FULL_CUSTOMER : 'PRD');
}

function getFullDashboardApiUrl(string $endpoint = ''): string
{
  $url = 'DEV' === getFullEnv() ? 'https://full.dev/wp-json/full' : 'https://api.full.services/wp-json/full';
  return $url . $endpoint;
}

function isFullConnected(): bool
{
  $data = getFullConnectionData();
  return is_null($data) ? false : $data->success;
}

function getFullConnectionData(): ?stdClass
{
  $data = get_transient('full/site-connection-data');

  if (!$data || 'full-connection' === filter_input(INPUT_GET, 'page')) {
    $request  = wp_remote_get(getFullDashboardApiUrl('-customer/v1/connect-site'), [
      'body' => ['site_url' => home_url()]
    ]);

    $response = wp_remote_retrieve_body($request);
    $response = json_decode($response, true);

    $data = is_array($response) && $response['success'] ? (object) $response : null;

    set_transient('full/site-connection-data', $data, DAY_IN_SECONDS);
  }

  return $data;
}

function fullCustomer(): FullCustomer
{
  return new FullCustomer();
}

function fullGetMetaBox($post, array $metaBox): void
{
  $endpoint = str_replace('full-', '', $metaBox['id']);
  $file = FULL_CUSTOMER_APP . '/views/metaboxes/' . $endpoint . '.php';
  if (file_exists($file)) :
    include $file;
  endif;
}

function fullGetAdminPageView(): void
{
  $file = FULL_CUSTOMER_APP . '/views/admin/' . fullAdminPageEndpoint() . '.php';
  if (file_exists($file)) :
    include $file;
  endif;
}

function fullAdminPageEndpoint(): string
{
  $page     = filter_input(INPUT_GET, 'page');
  return $page ? str_replace('full-', '', $page) : '';
}

function fullGetImageUrl(string $image): string
{
  return trailingslashit(plugin_dir_url(FULL_CUSTOMER_FILE)) . 'app/assets/img/' . $image;
}

function getFullAssetsVersion(): string
{
  return 'PRD' === getFullEnv() ? FULL_CUSTOMER_VERSION : uniqid();
}

function isFullsAdminPage(): bool
{
  $page = filter_input(INPUT_GET, 'page');
  return $page !== null && strpos($page, 'full-') === 0;
}

function fullGetLocalize(): array
{
  $env     = fullCustomer();
  return [
    'rest_url'      => trailingslashit(rest_url()),
    'auth'          => wp_create_nonce('wp_rest'),
    'user_login'    => wp_get_current_user()->user_login,
    'dashboard_url' => getFullDashboardApiUrl('-customer/v1/'),
    'site_url'      => site_url(),
    'store_url'     => 'https://full.services',
    'ai_icon'       => fullGetImageUrl('icon-logo-full-ai.png'),
    'full_pro'      => License::isActive(),
    'enabled_services' => array_values($env->getEnabledServices()),
  ];
}

function fullGetTemplatesUrl(string $endpoint = ''): string
{
  return esc_url(add_query_arg([
    'page'      => 'full-templates',
    'endpoint'  => $endpoint
  ], admin_url('admin.php')));
}

function fullJsonEncode($data): string
{
  return wp_slash(wp_json_encode(
    $data,
    JSON_UNESCAPED_LINE_TERMINATORS | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE
  ));
}

function fullFileSystem()
{
  global $wp_filesystem;

  require_once(ABSPATH . 'wp-admin/includes/plugin.php');
  require_once(ABSPATH . '/wp-admin/includes/file.php');

  WP_Filesystem();

  if (!is_a($wp_filesystem, 'WP_Filesystem_Base')) {
    include_once(ABSPATH . 'wp-admin/includes/file.php');
    $creds = request_filesystem_credentials(site_url());
    WP_Filesystem($creds);
  }

  return $wp_filesystem;
}
