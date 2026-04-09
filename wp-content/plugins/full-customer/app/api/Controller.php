<?php defined('ABSPATH') || exit;

abstract class FullCustomerController extends WP_REST_Controller
{
  protected const NAMESPACE = 'full-customer';
  protected \FullCustomer $env;

  public function __construct()
  {
    $this->env = fullCustomer();
  }

  public function permissionCallback(): bool
  {
    return $this->isValidUserAuthenticated();
  }

  protected function isValidUserAuthenticated(): bool
  {
    return current_user_can('install_plugins');
  }
}
