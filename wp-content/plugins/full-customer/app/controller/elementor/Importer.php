<?php

namespace Full\Customer\Elementor;

use Elementor\Plugin as ElementorPlugin;

class Importer
{
  private string $name;
  private array $localJson;

  public function __construct(string $name, ?string $filename = null, ?array $localJson = null)
  {
    $this->name = $name;

    if ($filename) :
      $this->localJson = json_decode(file_get_contents($filename), true);
    elseif ($localJson) :
      $this->localJson = $localJson;
    endif;
  }

  public function import()
  {
    $localJson  = $this->localJson;
    $source     = ElementorPlugin::$instance->templates_manager->get_source('local');

    if (defined('WP_DEBUG') && WP_DEBUG) {
      ini_set('display_errors', false);
    }

    if (!empty($localJson['metadata']['elementor_pro_required']) && !class_exists('\ElementorPro\Plugin')) {
      $localJson['type'] = 'page';
    }

    require_once ABSPATH . '/wp-admin/includes/file.php';
    $temp_wp_json_file = wp_tempnam('elements-tk-import-');
    fullFileSystem()->put_contents($temp_wp_json_file, wp_json_encode($localJson));

    $result = $source->import_template(basename($temp_wp_json_file), $temp_wp_json_file);

    if (file_exists($temp_wp_json_file)) {
      unlink($temp_wp_json_file);
    }

    if (is_wp_error($result)) {
      return new \WP_Error('import_error', 'Failed to import template: ' . esc_html($result->get_error_message()));
    }

    if ($result[0] && $result[0]['template_id']) {
      $imported_template_id = $result[0]['template_id'];

      if ($localJson['metadata'] && !empty($localJson['metadata']['elementor_pro_conditions'])) {
        update_post_meta($imported_template_id, '_elementor_conditions', $localJson['metadata']['elementor_pro_conditions']);
      }

      if ($localJson['metadata'] && !empty($localJson['metadata']['wp_page_template'])) {
        update_post_meta($imported_template_id, '_wp_page_template', $localJson['metadata']['wp_page_template']);
      }

      if ($localJson['metadata'] && !empty($localJson['metadata']['template_type']) && 'global-styles' === $localJson['metadata']['template_type']) {
        update_post_meta($imported_template_id, '_elementor_edit_mode', 'builder');
        update_post_meta($imported_template_id, '_elementor_template_type', 'kit');
        update_option('elementor_active_kit', $imported_template_id);

        wp_update_post([
          'ID'         => $imported_template_id,
          'post_title' => 'Template Kit FULL: ' . $this->name,
        ]);
      }

      return $imported_template_id;
    }

    return new \WP_Error('import_error', 'Unknown import error');
  }
}
