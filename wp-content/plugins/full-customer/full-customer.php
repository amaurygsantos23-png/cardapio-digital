<?php defined('ABSPATH') || exit;

/**
 * Plugin Name:         FULL - Cliente
 * Description:         Este plugin adiciona novas extensões úteis e conecta-o ao painel da FULL. para ativações de outros plugins.
 * Version:             3.5.2
 * Requires at least:   6.3
 * Tested up to:        6.9
 * Requires PHP:        7.4
 * Author:              FULL.
 * Author URI:          https://full.services/
 */

if (!defined('FULL_CUSTOMER_VERSION')) {
  define('FULL_CUSTOMER_VERSION', '3.5.2');
  define('FULL_CUSTOMER_FILE', __FILE__);
  define('FULL_CUSTOMER_APP', __DIR__ . '/app');
  require_once FULL_CUSTOMER_APP . '/init.php';
}
