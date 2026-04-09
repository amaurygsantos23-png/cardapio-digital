<?php

defined('ABSPATH') || exit;

class FullCustomerStaffRepository
{
  public function __construct()
  {
    add_action('admin_footer', [$this, 'modal']);
    add_action('admin_enqueue_scripts', [$this, 'assets'], PHP_INT_MAX);

    add_action('wp_ajax_full/staff/repository', [$this, 'repository']);
    add_action('wp_ajax_full/staff/install-plugin', [$this, 'installPlugin']);
    add_action('wp_ajax_full/staff/install-plugin/progress', [$this, 'installationProgress']);
  }

  public function assets(): void
  {
    if (!current_user_can('manage_options')) {
      return;
    }

    $version = getFullAssetsVersion();
    $baseUrl = trailingslashit(plugin_dir_url(FULL_CUSTOMER_FILE)) . 'app/assets/';
    $id = wp_generate_uuid4();

    wp_enqueue_style('full-staff', $baseUrl . 'css/staff.css', [], $version);
    wp_enqueue_script('full-staff', $baseUrl . 'js/staff.js', ['jquery'], $version, true);
    wp_localize_script('full-staff', 'FULL_STAFF', [
      'wpPluginsUrl' => admin_url('plugins.php'),
      'repository' => add_query_arg([
        'action'  => 'full/staff/repository',
        'nonce'   => wp_create_nonce('full/staff/repository')
      ], admin_url('admin-ajax.php')),
      'installPlugin' => add_query_arg([
        'id'      => $id,
        'action'  => 'full/staff/install-plugin',
        'nonce'   => wp_create_nonce('full/staff/install-plugin')
      ], admin_url('admin-ajax.php')),
      'installPluginProgress' => add_query_arg([
        'id'      => $id,
        'action'  => 'full/staff/install-plugin/progress',
        'nonce'   => wp_create_nonce('full/staff/install-plugin/progress')
      ], admin_url('admin-ajax.php'))
    ]);
  }

  public function modal(): void
  {
    if (!current_user_can('manage_options')) {
      return;
    }

    require_once FULL_CUSTOMER_APP . '/views/wpadmin-footer.php';
  }

  public function repository(): void
  {
    if (!current_user_can('manage_options') || !wp_verify_nonce(filter_input(INPUT_GET, 'nonce'), 'full/staff/repository')) {
      wp_send_json_error();
    }

    $dir = [];
    foreach (FullCustomerUpdate::fetchDirectory() as $item) {
      $dir[] = [
        'plugin' => $item->plugin,
        'name' => $item->name
      ];
    }

    wp_send_json_success($dir);
  }

  public function installPlugin(): void
  {
    if (!current_user_can('manage_options') || !wp_verify_nonce(filter_input(INPUT_GET, 'nonce'), 'full/staff/install-plugin')) {
      wp_send_json_error('Sem permissão');
    }

    require_once ABSPATH . 'wp-admin/includes/plugin.php';

    global $wp_filesystem;

    if (!is_a($wp_filesystem, 'WP_Filesystem_Base')) {
      include_once(ABSPATH . 'wp-admin/includes/file.php');
      $creds = request_filesystem_credentials(site_url());
      wp_filesystem($creds);
    }

    $id  = filter_input(INPUT_GET, 'id') ?? uniqid();
    $dir = FullCustomerUpdate::fetchDirectory(false);
    $key = filter_input(INPUT_POST, 'plugin') ?? '';
    $plugin = $dir[$key] ?? null;

    if (!$plugin) {
      wp_send_json_error('Plugin não localizado');
    }

    $this->enqueueInstallationProgress($id, $key, 'Verificando dependências...');

    foreach ($plugin->dependencies as $dep) {
      $request = new WP_REST_Request('POST', '/wp/v2/plugins');
      $request->set_param('slug', $dep);
      $request->set_param('status', 'active');
      $request->set_param('context', 'edit');

      $this->enqueueInstallationProgress($id, $key, 'Instalando dependência ' . $dep);
      rest_do_request($request);
    }

    $this->enqueueInstallationProgress($id, $key, 'Dependências validadas. Iniciando processo do plugin principal');

    $recoveryLink = ' <a href="' . $plugin->package . '">Baixar plugin</a> ';
    $package = download_url($plugin->package, 300);

    if (is_wp_error($package)) {
      wp_send_json_error('[download] ' . $package->get_error_message() .  $recoveryLink);
    }

    $this->enqueueInstallationProgress($id, $key, 'Arquivo baixado. Iniciando descompactação...');

    $workingDir = $wp_filesystem->wp_content_dir() . 'upgrade/' . $plugin->slug;

    if ($wp_filesystem->is_dir($workingDir)) {
      $wp_filesystem->delete($workingDir, true);
    }

    wp_mkdir_p($workingDir);

    $done = unzip_file($package, $workingDir);

    if (is_wp_error($done)) {
      wp_send_json_error('[unzip] ' . $done->get_error_message() .  $recoveryLink);
    }

    $this->enqueueInstallationProgress($id, $key, 'Arquivo descompactado. Iniciando a transferência...');

    $wp_filesystem->delete($package);

    $done = copy_dir($workingDir, WP_PLUGIN_DIR);
    if (is_wp_error($done)) {
      wp_send_json_error('[copy] ' . $done->get_error_message() .  $recoveryLink);
    }

    $wp_filesystem->delete($workingDir, true);

    $pluginActivationPath = trailingslashit(WP_PLUGIN_DIR) . $plugin->plugin;

    $this->enqueueInstallationProgress($id, $key, 'Arquivo transferido. Solicitando ativação do plugin no WordPress');

    if (!is_plugin_active($pluginActivationPath)) {
      activate_plugin($pluginActivationPath);
    }

    wp_send_json_success();
  }

  public function installationProgress(): void
  {
    $id  = filter_input(INPUT_GET, 'id') ?? uniqid();
    $key = filter_input(INPUT_POST, 'plugin') ?? '';

    $progress = $this->getInstallationProgress($id)[$key] ?? [];

    wp_send_json_success('> ' . implode('<br>> ', $progress));
  }

  private function enqueueInstallationProgress(string $processId, string $plugin, string $message): void
  {
    $progress = $this->getInstallationProgress($processId);

    if (!isset($progress[$plugin])) {
      $progress[$plugin] = [];
    }

    $progress[$plugin][] = $message;

    set_transient($processId, $progress, HOUR_IN_SECONDS);
  }

  private function getInstallationProgress(string $processId): array
  {
    return get_transient($processId) ?: [];
  }
}

new FullCustomerStaffRepository();
