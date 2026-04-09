<?php

defined('ABSPATH') || exit;

class FullCustomerHttp
{
  public function __construct()
  {
    add_filter('http_request_args', [$this, 'filterRequestArgs'], PHP_INT_MAX, 2);
  }

  public function filterRequestArgs(array $args, $url): array
  {
    if (is_string($url) && $url && strpos($url, getFullDashboardApiUrl()) !== false) {
      $args['reject_unsafe_urls'] = 'PRD' === getFullEnv();
      $args['sslverify'] = 'PRD' === getFullEnv();
      $args['headers']['X-Full-Site'] = trailingslashit(home_url());
    }

    return $args;
  }
}

new FullCustomerHttp();
