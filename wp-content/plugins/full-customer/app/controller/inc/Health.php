<?php

namespace Full\Customer;

if (!class_exists('WP_Site_Health')) :
  require_once ABSPATH . '/wp-admin/includes/update.php';
  require_once ABSPATH . '/wp-admin/includes/plugin.php';
  require_once ABSPATH . '/wp-admin/includes/misc.php';

  require_once ABSPATH . 'wp-admin/includes/class-wp-site-health.php';
endif;

use WP_Site_Health;

class Health extends WP_Site_Health
{
  private $response = [
    'site_status' => [
      'direct' => [],
      'async'  => []
    ],
  ];

  public function getResults()
  {
    load_textdomain('default', WP_LANG_DIR . '/admin-pt_BR.mo');

    $tests = self::get_tests();

    array_map([$this, 'runDirectTest'], $tests['direct']);
    array_map([$this, 'runAsyncTest'], $tests['async']);

    return $this->response;
  }

  private function runAsyncTest(array $test): void
  {
    if (!is_string($test['test'])) :
      return;
    endif;

    $this->response['site_status']['async'][] = [
      'test'      => $test['test'],
      'has_rest'  => (isset($test['has_rest']) ? $test['has_rest'] : false),
      'completed' => false,
      'headers'   => isset($test['headers']) ? $test['headers'] : [],
    ];
  }

  private function runDirectTest(array $test): void
  {
    if (is_string($test['test'])) :
      $testFunction = sprintf('get_test_%s', $test['test']);

      if (method_exists($this, $testFunction) && is_callable([$this, $testFunction])) :
        $this->response['site_status']['direct'][] = $this->perform_test([$this, $testFunction]);
        return;
      endif;
    endif;

    if (is_callable($test['test'])) :
      $this->response['site_status']['direct'][] = $this->perform_test($test['test']);
    endif;
  }

  private function perform_test($callback)
  {
    return apply_filters('site_status_test_result', call_user_func($callback));
  }
}
