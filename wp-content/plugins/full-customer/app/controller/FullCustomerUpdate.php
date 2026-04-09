<?php

defined('ABSPATH') || exit;

class FullCustomerUpdate
{
  public function __construct()
  {
    add_filter('plugins_api', [$this, 'info'], PHP_INT_MAX, 3);
    add_filter('site_transient_update_plugins', [$this, 'pluginUpdate'], PHP_INT_MAX);

    add_action('after_plugin_row_meta', [$this, 'afterPluginRow'], 10, 2);
  }

  public static function repositoryFilename(): string
  {
    return trailingslashit(wp_get_upload_dir()['basedir']) . 'full-plugin-updates.json';
  }

  public function afterPluginRow(string $pluginSlug): void
  {
    $data = $this->fetchDirectory();

    if (!isset($data[$pluginSlug])) {
      return;
    }

    $license = $this->fetchPluginLicense($pluginSlug, $data[$pluginSlug]->info);

    if ($license === 'expired') {
      wp_admin_notice('A licença deste plugin expirou. <a href="https://full.services?utm_source=plugin_row">Clique aqui para renovar</a>', [
        'type'               => 'warning',
        'additional_classes' => ['notice-alt', 'inline'],
      ]);
    }
  }

  public function info($response, $action, $args)
  {
    if ($action !== 'plugin_information') {
      return $response;
    }

    $data = $this->fetchDirectory();

    $localPlugins = array_keys(get_plugins());
    $localPlugins = array_filter($localPlugins, fn($path): bool => basename($path) === $args->slug);
    $path = reset($localPlugins);

    return isset($data[$path]) ? $data[$path] : $response;
  }

  public function pluginUpdate($transient)
  {
    if (!$transient || empty($transient->checked)) {
      return $transient;
    }

    $data = $this->fetchDirectory();

    if (!$data) {
      return $transient;
    }

    foreach ($data as $slug => $remotePlugin) {
      $localPluginVersion = isset($transient->checked[$slug]) ? $transient->checked[$slug] : null;

      if (!$localPluginVersion) {
        continue;
      }

      if (version_compare($remotePlugin->version, $localPluginVersion, '>') && $this->fetchPluginLicense($slug, $remotePlugin->info) !== 'expired') {
        $remotePlugin->new_version = $remotePlugin->version;

        $transient->response[$slug] = $remotePlugin;
        $transient->checked[$slug] = $remotePlugin->version;

        if (isset($transient->no_update[$slug])) {
          unset($transient->no_update[$slug]);
        }
      }
    }

    return $transient;
  }

  public static function fetchDirectory(bool $cache = true): array
  {
    $file = self::repositoryFilename();
    $updatedAt = file_exists($file) ? filemtime($file) : 0;

    $directory = time() - $updatedAt < HOUR_IN_SECONDS ? json_decode(file_get_contents($file)) : [];

    if (!empty($directory) && $cache) {
      return self::fixJsonParse($directory);
    }

    $conn = getFullConnectionData() ?: null;
    $url = getFullDashboardApiUrl('/v1/plugin/directory');
    $url = add_query_arg([
      'userEmail' => is_null($conn) ? '' : $conn->connection_email
    ], $url);

    $response = wp_remote_get($url, [
      'sslverify' => false,
      'headers'   => ['Accept' => 'application/json'],
      'timeout'   => 15
    ]);

    if (
      is_wp_error($response) ||
      wp_remote_retrieve_response_code($response) !== 200
    ) {
      return $directory;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body);

    if (json_last_error() !== JSON_ERROR_NONE) {
      return $directory;
    }

    file_put_contents($file, wp_json_encode($data));

    return self::fixJsonParse($data);
  }

  private function fetchPluginLicense(string $pluginSlug, string $infoUrl): string
  {
    $license = get_option('full/plugin-license/' . $pluginSlug, null);

    if ($license && $license['expireAt'] > current_time('timestamp')) {
      return $license['license'];
    }

    $conn = getFullConnectionData() ?: null;

    if (!$conn) {
      return 'disconnected';
    }

    $infoUrl = add_query_arg([
      'siteUrl' => home_url()
    ], $infoUrl);

    $response = wp_remote_get($infoUrl, [
      'sslverify' => false,
      'headers'   => ['Accept' => 'application/json'],
      'timeout'   => 15
    ]);

    if (
      is_wp_error($response) ||
      wp_remote_retrieve_response_code($response) !== 200
    ) {
      return 'api_error';
    }

    $data = json_decode(wp_remote_retrieve_body($response));
    $license = $data && isset($data->license) ? $data->license : 'unknown';

    update_option('full/plugin-license/' . $pluginSlug, [
      'license' => $license,
      'expireAt' => current_time('timestamp') + DAY_IN_SECONDS
    ], false);

    return $license;
  }

  private static function fixJsonParse(array $data): array
  {
    $directory = [];

    foreach ($data as $plugin) {
      $plugin->sections = (array) $plugin->sections;
      $directory[$plugin->plugin] = $plugin;
    }

    return $directory;
  }
}

new FullCustomerUpdate();
