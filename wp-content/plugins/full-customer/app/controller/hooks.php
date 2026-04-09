<?php

namespace Full\Customer\Hooks;

use Full\Customer\License;

defined('ABSPATH') || exit;

register_activation_hook(FULL_CUSTOMER_FILE, '\Full\Customer\Actions\verifySiteConnection');
register_activation_hook(FULL_CUSTOMER_FILE, '\Full\Customer\Actions\activationAnalyticsHook');
register_deactivation_hook(FULL_CUSTOMER_FILE, '\Full\Customer\Actions\deactivationAnalyticsHook');

add_action('rest_api_init', ['\Full\Customer\Api\PluginInstallation', 'registerRoutes']);
add_action('rest_api_init', ['\Full\Customer\Api\Connection', 'registerRoutes']);
add_action('rest_api_init', ['\Full\Customer\Api\Health', 'registerRoutes']);

add_action('admin_init', '\Full\Customer\Actions\forceLicenseCheck');

add_action('wp_footer', '\Full\Customer\Actions\insertFooterNote');
add_action('admin_menu', '\Full\Customer\Actions\addMenuPage');
add_action('admin_enqueue_scripts', '\Full\Customer\Actions\adminEnqueueScripts');
add_action('shutdown', '\Full\Customer\Actions\notifyPluginError');

add_action('plugins_loaded', '\Full\Customer\Actions\initFullElementorTemplates');
add_action('plugins_loaded', '\Full\Customer\Actions\initFullElementorAddons');

add_action('rest_api_init', ['\Full\Customer\Api\ElementorTemplates', 'registerRoutes']);

add_filter('wp_is_application_passwords_available', '__return_true', PHP_INT_MAX);
add_filter('wp_is_application_passwords_available_for_user', '__return_true', PHP_INT_MAX);

add_action('rest_api_init', ['\Full\Customer\Api\Widgets', 'registerRoutes']);

if (License::isActive()) :
  add_action('rest_api_init', ['\Full\Customer\Api\PluginUpdate', 'registerRoutes']);
  add_action('rest_api_init', ['\Full\Customer\Api\Whitelabel', 'registerRoutes']);
  add_action('rest_api_init', ['\Full\Customer\Api\Copy', 'registerRoutes']);

  add_action('plugins_loaded', '\Full\Customer\Actions\upgradePlugin');
endif;

add_filter('full-versions-upgrades', '\Full\Customer\Filters\versionsWithUpgrade');
add_filter('all_plugins', '\Full\Customer\Filters\setPluginBranding');
add_filter('plugin_row_meta', '\Full\Customer\Filters\pluginRowMeta', 10, 2);
add_filter('wp_php_error_args', '\Full\Customer\Filters\notifyPluginError', PHP_INT_MAX, 2);
add_filter('rest_pre_serve_request', '\Full\Customer\Filters\restPreServeRequest', 0, 2);
