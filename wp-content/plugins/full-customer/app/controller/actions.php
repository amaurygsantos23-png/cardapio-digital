<?php

namespace Full\Customer\Actions;

use Full\Customer\License;
use FullCustomerUpdate;
use WP_REST_Request;

defined('ABSPATH') || exit;

function insertFooterNote(): void
{
  $full = fullCustomer();
  $file = FULL_CUSTOMER_APP . '/views/footer/note.php';

  $settings = $full->get('whitelabel_settings');
  $enabled = is_array($settings) && isset($settings['allow_backlink']) ? $settings['allow_backlink'] !== 'no' : true;

  if ($enabled && file_exists($file)) :
    require_once $file;
  endif;
}

function forceLicenseCheck(): void
{
  if (filter_input(INPUT_GET, 'full') === 'verify_license') {
    License::updateStatus();

    wp_safe_redirect(esc_url(remove_query_arg('full')));
    exit;
  }

  if (filter_input(INPUT_GET, 'full') === 'repo_clear') {
    @unlink(FullCustomerUpdate::repositoryFilename());

    global $wpdb;

    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%full/plugin-license%'");

    wp_safe_redirect(esc_url(remove_query_arg('full')));
    exit;
  }
}

function verifySiteConnection(): void
{
  $flag = 'previous-connect-site-check';
  $full = fullCustomer();

  if ($full->get($flag) || $full->hasDashboardUrl()) :
    return;
  endif;

  $response = getFullConnectionData();

  if ($response && $response->success) :
    $full->set('connection_email', sanitize_email($response->connection_email));
    $full->set('dashboard_url', esc_url($response->dashboard_url));
  endif;

  $full->set($flag, 1);
}

function activationAnalyticsHook(): void
{
  $url   = getFullDashboardApiUrl('-customer/v1/analytics');

  wp_remote_post($url, [
    'sslverify' => false,
    'headers'   => ['x-full' => 'Jkd0JeCPm8Nx', 'Content-Type' => 'application/json'],
    'body'      => wp_json_encode([
      'site_url'      => home_url(),
      'admin_email'   => get_bloginfo('admin_email'),
      'plugin_status' => 'active'
    ])
  ]);
}

function deactivationAnalyticsHook(): void
{
  $url   = getFullDashboardApiUrl('-customer/v1/analytics');

  wp_remote_post($url, [
    'sslverify' => false,
    'headers'   => ['x-full' => 'Jkd0JeCPm8Nx', 'Content-Type' => 'application/json'],
    'body'      => wp_json_encode([
      'site_url'      => home_url(),
      'admin_email'   => get_bloginfo('admin_email'),
      'plugin_status' => 'inactive'
    ])
  ]);
}

function addMenuPage(): void
{
  $full = fullCustomer();

  add_menu_page(
    $full->getBranding('admin-page-name', 'FULL.services'),
    $full->getBranding('admin-page-name', 'FULL.services'),
    'manage_options',
    'full-connection',
    'fullGetAdminPageView',
    'data:image/svg+xml;base64,' . base64_encode(fullFileSystem()->get_contents(plugin_dir_url(FULL_CUSTOMER_FILE) . 'app/assets/img/menu-novo.svg')),
    0
  );

  $connectionOk   = isFullConnected();
  $cls = $connectionOk ? 'success' : 'error';
  $text = $connectionOk ? 'conectado' : 'desconectado';

  add_submenu_page(
    'full-connection',
    'Conexão',
    'Conexão <span class="full-badge full-' . $cls . '">' . $text . '</span>',
    'manage_options',
    'full-connection',
    'fullGetAdminPageView'
  );

  $status = License::status();
  $cls = $status['plan'] ? 'full' : 'error';
  $text = $status['plan'] ? $status['plan'] : 'seja PRO';

  add_submenu_page(
    'full-connection',
    'FULL.PRO',
    'FULL.PRO <span class="full-badge full-' . sanitize_title($cls) . '">' . esc_attr($text) . '</span>',
    'manage_options',
    'full-widgets',
    'fullGetAdminPageView'
  );

  add_submenu_page(
    'full-connection',
    'Planos',
    'Planos',
    'manage_options',
    'full-store',
    'fullGetAdminPageView'
  );

  $widgets = apply_filters('full-customer/active-widgets-menu', []);

  uasort($widgets, function ($a, $b) {
    return strcmp($a['name'], $b['name']);
  });

  foreach ($widgets as $widget) :
    add_submenu_page(
      'full-connection',
      $widget['name'],
      $widget['name'],
      'edit_posts',
      $widget['endpoint'],
      'fullGetAdminPageView'
    );
  endforeach;
}

function adminEnqueueScripts(): void
{
  $version = getFullAssetsVersion();
  $baseUrl = trailingslashit(plugin_dir_url(FULL_CUSTOMER_FILE)) . 'app/assets/';

  if (isFullsAdminPage()) :
    wp_enqueue_style('full-icons', 'https://painel.full.services/wp-content/plugins/full/app/assets/vendor/icon-set/style.css', [], '1.0.0');
    wp_enqueue_style('full-swal', $baseUrl . 'vendor/sweetalert/sweetalert2.min.css', [], '11.4.35');
    wp_enqueue_style('full-flickity', $baseUrl . 'vendor/flickity/flickity.min.css', [], '2.3.0');
    wp_enqueue_style('full-magnific-popup', $baseUrl . 'vendor/magnific-popup/magnific-popup.min.css', [], '1.0.0');
    wp_enqueue_style('full-admin', $baseUrl . 'css/admin.css', [], $version);

    wp_enqueue_script('full-swal', $baseUrl . 'vendor/sweetalert/sweetalert2.min.js', ['jquery'], '11.4.35', true);
    wp_enqueue_script('full-flickity', $baseUrl . 'vendor/flickity/flickity.min.js', ['jquery'], '2.3.0', true);
    wp_enqueue_script('full-magnific-popup', $baseUrl . 'vendor/magnific-popup/magnific-popup.min.js', ['jquery'], '1.0.0', true);
  endif;

  wp_enqueue_style('full-global-admin', $baseUrl . 'css/global-admin.css', [], $version);
  wp_enqueue_script('full-admin', $baseUrl . 'js/admin.js', ['jquery'], $version, true);
  wp_localize_script('full-admin', 'FULL', fullGetLocalize());
}

function upgradePlugin(): void
{
  $env = fullCustomer();
  $siteVersion = $env->get('version') ? $env->get('version') : '0.0.0';

  if (version_compare(FULL_CUSTOMER_VERSION, $siteVersion, '>') && !get_transient('full-upgrading')) :
    set_transient('full-upgrading', 1, MINUTE_IN_SECONDS);

    $upgradeVersions = apply_filters('full-versions-upgrades', []);

    foreach ($upgradeVersions as $pluginVersion) :
      if (version_compare($pluginVersion, $siteVersion, '>=')) :
        do_action('full-customer/upgrade/' . $pluginVersion);
      endif;
    endforeach;

    $env->set('version', FULL_CUSTOMER_VERSION);
  endif;
}

function notifyPluginError(): bool
{
  $error = get_option('full_customer_last_error');

  if (!$error) :
    return false;
  endif;

  $url  = getFullDashboardApiUrl('-customer/v1/error');

  wp_remote_post($url, [
    'sslverify' => false,
    'headers'   => [
      'Content-Type'  => 'application/json',
    ],
    'body'  => wp_json_encode([
      'site_url'  => home_url(),
      'error'     => $error,
      'version'   => FULL_CUSTOMER_VERSION
    ])
  ]);

  delete_option('full_customer_last_error');
  return true;
}

function initFullElementorTemplates(): void
{
  if (class_exists('\Elementor\Plugin')) :
    require_once FULL_CUSTOMER_APP . '/controller/elementor/hooks.php';
    require_once FULL_CUSTOMER_APP . '/controller/elementor/actions.php';
    require_once FULL_CUSTOMER_APP . '/controller/elementor/filters.php';
    require_once FULL_CUSTOMER_APP . '/controller/elementor/TemplateManager.php';
    require_once FULL_CUSTOMER_APP . '/controller/elementor/Importer.php';
    require_once FULL_CUSTOMER_APP . '/controller/elementor/Exporter.php';
  endif;
}

function initFullElementorAddons(): void
{
  if (class_exists('\Elementor\Plugin')) :
    require_once FULL_CUSTOMER_APP . '/controller/elementor-addons/Registrar.php';
  endif;
}
