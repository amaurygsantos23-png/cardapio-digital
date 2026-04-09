<?php

namespace Full\Customer\Api;

use Full\Customer\Elementor\Exporter;
use Full\Customer\Elementor\Importer;
use Full\Customer\Elementor\TemplateManager;
use \FullCustomerController;
use stdClass;
use \WP_REST_Server;
use \WP_REST_Request;
use \WP_REST_Response;

defined('ABSPATH') || exit;

class ElementorTemplates extends FullCustomerController
{
  private string $token;

  public static function registerRoutes(): void
  {
    $api = new self();

    register_rest_route(self::NAMESPACE, '/elementor/install-events', [
      [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [$api, 'installEvents'],
        'permission_callback' => '__return_true',
      ]
    ]);

    register_rest_route(self::NAMESPACE, '/elementor/install', [
      [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => [$api, 'install'],
        'permission_callback' => [$api, 'elementorPermissionCallback'],
      ]
    ]);

    register_rest_route(self::NAMESPACE, '/elementor/install-dependencies', [
      [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => [$api, 'installDependencies'],
        'permission_callback' => [$api, 'elementorPermissionCallback'],
      ]
    ]);

    register_rest_route(self::NAMESPACE, '/elementor/download', [
      [
        'methods'             => WP_REST_Server::READABLE,
        'callback'            => [$api, 'download'],
        'permission_callback' => '__return_true',
      ]
    ]);

    register_rest_route(self::NAMESPACE, '/elementor/sync', [
      [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => [$api, 'sync'],
        'permission_callback' => [$api, 'elementorPermissionCallback'],
      ]
    ]);

    register_rest_route(self::NAMESPACE, '/elementor/send-to-cloud', [
      [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => [$api, 'builderSendToCloud'],
        'permission_callback' => [$api, 'elementorPermissionCallback'],
      ]
    ]);

    register_rest_route(self::NAMESPACE, '/elementor/send-to-cloud/(?P<post_id>[0-9\-]+)', [
      [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => [$api, 'sendToCloud'],
        'permission_callback' => [$api, 'elementorPermissionCallback'],
      ]
    ]);

    register_rest_route(self::NAMESPACE, '/elementor/delete-from-cloud/(?P<item_id>[0-9\-]+)', [
      [
        'methods'             => WP_REST_Server::CREATABLE,
        'callback'            => [$api, 'deleteFromCloud'],
        'permission_callback' => [$api, 'elementorPermissionCallback'],
      ]
    ]);
  }

  public function download(WP_REST_Request $request): void
  {
    $itemId = (int) $request->get_param('id');
    $item     = TemplateManager::instance()->getCloudItem($itemId);

    if (!$item instanceof \stdClass || !isset($item->fileUrl) || !$item->fileUrl) :
      wp_send_json_error();
    endif;

    header("Content-type: application/vnd.ms-excel");
    header("Content-Type: application/force-download");
    header("Content-Type: application/download");
    header("Content-disposition: template.json");
    header("Content-disposition: filename=template.json");

    $template = $this->downloadJson($item->fileUrl);

    exit(fullJsonEncode($template));
  }

  public function sync(): WP_REST_Response
  {
    global $wpdb;

    $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_full/cloud/%'"));

    return new WP_REST_Response();
  }

  public function elementorPermissionCallback(): bool
  {
    return $this->isValidUserAuthenticated() && $this->hasElementor();
  }

  public function installEvents(WP_REST_Request $request): WP_REST_Response
  {
    return new WP_REST_Response([
      'data' => get_option('full/import/' . $request->get_param('token'), 'Importação não localizada ou finalizada')
    ]);
  }

  private function logProgress(string $message): void
  {
    $content  = get_option('full/import/' . $this->token, '');
    $content .= '[' . current_time('d/m/Y H:i:s') . '] ' . $message;

    update_option('full/import/' . $this->token, $message, false);
  }

  private function clearLog(): void
  {
    delete_option('full/import/' . $this->token);
  }

  public function install(WP_REST_Request $request): WP_REST_Response
  {
    $this->token = $request->get_param('token');

    $this->logProgress('Processando instalação');

    $item   = $request->get_param('item');
    $itemId = (int) $item['id'];
    $origin = sanitize_title($item['origin']);
    $mode   = sanitize_title($request->get_param('mode'));

    $response = ('template' === $origin) ?
      $this->installTemplate($itemId, $mode) :
      $this->installCloud($itemId, $mode);

    $this->clearLog();

    return $response;
  }

  public function installDependencies(WP_REST_Request $request): WP_REST_Response
  {
    $item   = $request->get_param('item');
    $itemId = (int) $item['id'];

    $item     = TemplateManager::instance()->getItem($itemId);

    if (!$item instanceof \stdClass || !$item->canBeInstalled) :
      return new WP_REST_Response(['error' => 'O item selecionado não pode ser instalado.']);
    endif;

    $dependencies = $this->verifyMissingDependencies($item);

    if ($dependencies === null || $dependencies === []) :
      return new WP_REST_Response(['message' => 'Nenhuma dependência pendente localizada']);
    endif;

    if (!function_exists('activate_plugin')) :
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    endif;

    foreach ($dependencies['uninstalled'] as $dep) :
      $request = new WP_REST_Request('POST', '/wp/v2/plugins');
      $request->set_param('slug', $dep->slug);
      $request->set_param('status', 'active');
      $request->set_param('context', 'edit');

      rest_do_request($request);
    endforeach;

    foreach ($dependencies['inactive'] as $dep) :
      activate_plugin($dep->localPath);
    endforeach;

    return new WP_REST_Response(['message' => 'Dependências instaladas! Vamos importar o template escolhi agora.']);
  }

  private function installTemplate(int $itemId, string $mode): WP_REST_Response
  {
    $this->logProgress('Verificando item solicitado');

    $item     = TemplateManager::instance()->getItem($itemId);

    if (!$item instanceof \stdClass || !$item->canBeInstalled) :
      return new WP_REST_Response(['error' => 'O item selecionado não pode ser instalado.']);
    endif;

    $this->logProgress('Item localizado, validando dependências');

    $dependencies = $this->verifyMissingDependencies($item);

    if ($dependencies !== null && $dependencies !== []) :
      ob_start();

      require_once FULL_CUSTOMER_APP . '/views/components/template-missing-dependencies.php';

      return new WP_REST_Response([
        'dependencies'  => ob_get_clean(),
        'mode'          => $mode
      ]);
    endif;

    $this->logProgress('Nenhuma dependência pendente encontrada, seguindo com a instalação');

    if ($item->hasZipFile) :
      return $this->installPack($item, $mode);
    endif;

    $this->logProgress('Realizando o download do arquivo de template');

    $template = $this->downloadJson($item->fileUrl);

    if ($template === null || $template === []) :
      return new WP_REST_Response(['error' => 'O item selecionado não foi localizado.']);
    endif;

    $this->logProgress('Preparando ambiente para importação');

    $importer  = new Importer($item->title, '', $template);

    if ('builder' === $mode) :
      return new WP_REST_Response(['builder' => $template]);
    endif;

    $this->logProgress('Importando arquivo de template: ' . $item->title);
    $templateId = $importer->import();

    if (is_wp_error($templateId)) :
      return new WP_REST_Response([
        'error' => $templateId->get_error_message(),
      ]);
    endif;

    if ('page' === $mode) :
      wp_update_post([
        'ID' => $templateId,
        'post_type' => 'page'
      ]);
    endif;

    return new WP_REST_Response([
      'postId'    => $templateId,
      'editUrl'   => get_edit_post_link($templateId, 'internal'),
      'visitUrl'  => get_permalink($templateId),
      'message'   => 'Template importado com sucesso!'
    ]);
  }

  private function installPack(stdClass $item, string $mode): WP_REST_Response
  {
    $this->logProgress('Realizando o download dos arquivos do pack de templates');

    $templates = $this->downloadJsonPack($item->fileUrl);
    $postsIds  = [];

    if ($templates === []) :
      return new WP_REST_Response([
        'error' => 'Não foi possível fazer o download do pack',
      ]);
    endif;

    $total = count($templates);
    $this->logProgress('Download concluído, iniciando a importação dos ' . $total . ' templates');

    foreach ($templates as $index => $template) :
      $json = json_decode(file_get_contents($template), ARRAY_A);
      $this->logProgress('Importando template: ' . $json['title'] . ' (' . ($index + 1) . ' de ' . $total . ')');

      $importer  = new Importer($item->title, $template);
      $templateId = $importer->import();

      if (is_wp_error($templateId)) :
        error_log('[FULL] Erro ao importar o template: ' . $template->get_error_message());
        continue;
      endif;

      if ('page' === $mode && 'page' === $json['type']) :
        wp_update_post([
          'ID' => $templateId,
          'post_type' => 'page'
        ]);
      endif;

      $postsIds[] = $templateId;
    endforeach;

    return new WP_REST_Response([
      'message'  => 'Pack importado com sucesso!',
    ]);
  }

  private function installCloud(int $itemId, string $mode): WP_REST_Response
  {
    $item     = TemplateManager::instance()->getCloudItem($itemId);
    $template = $this->downloadJson($item->fileUrl);

    if ($template === null || $template === []) :
      return new WP_REST_Response(['error' => 'O item selecionado não foi localizado.']);
    endif;

    if ('builder' === $mode) :
      return new WP_REST_Response(['builder' => $template]);
    endif;

    $importer  = new Importer($item->title, '', $template);

    if ('builder' === $mode) :
      return new WP_REST_Response(['builder' => $template]);
    endif;

    $templateId = $importer->import();

    if (is_wp_error($templateId)) :
      return new WP_REST_Response([
        'error' => $templateId->get_error_message(),
      ]);
    endif;

    if ('page' === $mode) :
      wp_update_post([
        'ID' => $templateId,
        'post_type' => 'page'
      ]);
    endif;

    return new WP_REST_Response([
      'postId'    => $templateId,
      'editUrl'   => get_edit_post_link($templateId, 'internal'),
      'visitUrl'  => get_permalink($templateId),
      'message'   => 'Template cloud importado com sucesso!'
    ]);
  }

  public function builderSendToCloud(WP_REST_Request $request): WP_REST_Response
  {
    $type    = $request->get_param('templateType') ? $request->get_param('templateType') : 'page';
    $content = $request->get_param('templateContent');

    if (isset($content['id'])) :
      $content = [$content];
    endif;

    $payload = [
      'site'  => site_url(),
      'title' => $request->get_param('templateName'),
      'type'  => $type,
      'json'  => fullJsonEncode(['type' => $type, 'content' => $content])
    ];

    $url  = getFullDashboardApiUrl('-customer/v1/template/cloud');

    $request  = wp_remote_post($url, ['sslverify' => false, 'body' => $payload]);
    $response = wp_remote_retrieve_body($request);
    $response = json_decode($response);

    return new WP_REST_Response($response);
  }

  public function sendToCloud(WP_REST_Request $request): WP_REST_Response
  {
    $postId   = (int) $request->get_param('post_id');
    $worker   = new Exporter();
    $payload  = [
      'site'  => site_url(),
      'title' => get_the_title($postId),
      'type'  => $worker->get_template_type($postId),
      'json'  => $worker->export($postId)
    ];

    $url  = getFullDashboardApiUrl('-customer/v1/template/cloud');

    $request  = wp_remote_post($url, ['sslverify' => false, 'body' => $payload]);
    $response = wp_remote_retrieve_body($request);
    $response = json_decode($response);

    update_post_meta($postId, 'full_cloud_id', $response->cloud->id);
    update_post_meta($postId, 'full_cloud_slug', $response->cloud->slug);

    return new WP_REST_Response([
      'postId'  => $postId,
      'button'  => '<a href="' . esc_url(fullGetTemplatesUrl('cloud')) . '">Gerenciar</a>'
    ]);
  }

  public function deleteFromCloud(WP_REST_Request $request): WP_REST_Response
  {
    $cloudId = (int) $request->get_param('item_id');

    if ($cloudId === 0) :
      return new WP_REST_Response(['error' => 'Item não localizado no Cloud']);
    endif;

    $payload = [
      'site'  => site_url(),
      'id'    => $cloudId
    ];

    $url  = getFullDashboardApiUrl('-customer/v1/template/cloud/');

    $request  = wp_remote_request($url, [
      'method'    => 'delete',
      'sslverify' => false,
      'body'      => $payload
    ]);

    $response = wp_remote_retrieve_body($request);
    $response = json_decode($response);

    if (!$response->success) :
      return new WP_REST_Response(['error' => 'Não foi possível excluir o item do Cloud.']);
    endif;

    global $wpdb;
    $wpdb->delete($wpdb->postmeta, ['meta_key' => 'full_cloud_id', 'meta_value' => $cloudId], ['%s', '%d']);

    return new WP_REST_Response([
      'deleted'  => true,
    ]);
  }

  private function hasElementor(): bool
  {
    return class_exists('Full\Customer\Elementor\TemplateManager');
  }

  private function downloadJson(string $url): ?array
  {
    $request = wp_remote_get($url, ['sslverify' => false]);
    $data    = json_decode(wp_remote_retrieve_body($request), ARRAY_A);

    return $data ? $data : null;
  }

  private function downloadJsonPack(string $url): array
  {
    $zipFile  = uniqid('pack-') . '.zip';
    $unzipDir = get_temp_dir() . str_replace('.zip', '', $zipFile);

    mkdir($unzipDir, 0777, true);

    $download = wp_remote_get($url, [
      'sslverify' => false,
      'timeout'   => 30,
      'stream'    => true,
      'filename'  => $zipFile
    ]);

    if (is_wp_error($download)) :
      return [];
    endif;

    fullFileSystem();
    unzip_file($zipFile, $unzipDir);

    $path = $unzipDir . DIRECTORY_SEPARATOR . 'templates';
    $templates = [];

    if (is_dir($path)) {
      $path  = trailingslashit(realpath($path));
      $path  = str_replace(['\\', '/'], [DIRECTORY_SEPARATOR, DIRECTORY_SEPARATOR], $path);
      $flags = defined('GLOB_BRACE') ? GLOB_MARK | GLOB_BRACE : 0;

      $templates = glob($path . '{,.}[!.,!..]*', $flags);
    }

    return  $templates;;
  }

  private function verifyMissingDependencies(stdClass $item): ?array
  {
    if (!$item->dependencies) :
      return null;
    endif;

    $inactive     = [];
    $uninstalled  = [];

    if (!function_exists('get_plugins')) :
      require_once ABSPATH . 'wp-admin/includes/plugin.php';
    endif;

    $activePlugins = (array) get_option('active_plugins', []);
    $plugins       = array_keys(get_plugins());

    $activePlugins = array_map([$this, 'getPluginSlug'], $activePlugins);
    $pluginsSlugs = array_map([$this, 'getPluginSlug'], $plugins);


    foreach ($item->dependencies as $dependency) :
      if (!in_array($dependency->slug, $pluginsSlugs)) :
        $uninstalled[] = $dependency;

      elseif (!in_array($dependency->slug, $activePlugins)) :
        $key = array_search($dependency->slug, $pluginsSlugs);
        $dependency->localPath = $plugins[$key];

        $inactive[] = $dependency;
      endif;
    endforeach;

    return $inactive || $uninstalled ? ['inactive' => $inactive, 'uninstalled' => $uninstalled] : null;
  }

  private function getPluginSlug(string $plugin): string
  {
    $slug = explode('/', $plugin);
    return array_shift($slug);
  }
}
