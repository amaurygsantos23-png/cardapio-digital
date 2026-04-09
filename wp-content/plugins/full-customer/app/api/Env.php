<?php defined('ABSPATH') || exit;

class FullCustomer
{
  private const PREFIX = '_full_customer-';

  public function set(string  $prop, $value): void
  {
    update_option(self::PREFIX . $this->optionEnvSuffix() . $prop, $value, false);
  }

  public function get(string  $prop)
  {
    return get_option(self::PREFIX . $this->optionEnvSuffix() . $prop, null);
  }

  public function getBranding(string $prop, string $default = ''): ?string
  {
    if (!$this->isServiceEnabled('full-whitelabel')) :
      return $default;
    endif;

    $branding = $this->get('whitelabel_settings');
    $prop = str_replace('-', '_', $prop);

    return isset($branding[$prop]) && $branding[$prop] ? $branding[$prop] : $default;
  }

  public function hasDashboardUrl(): bool
  {
    return (bool) $this->get('dashboard_url');
  }

  public function setEnabledServices(array $services): void
  {
    $this->set('enabled_services', array_values($services));

    $url     = getFullDashboardApiUrl('-customer/v1/widgets');
    $payload = [
      'site'  => site_url(),
      'widgets' => $this->getEnabledServices(),
    ];

    wp_remote_post($url, ['sslverify' => false, 'body' => $payload, 'blocking' => false]);
  }

  public function isServiceEnabled(string $service): bool
  {
    return in_array($service, $this->getEnabledServices(), true);
  }

  public function getEnabledServices(): array
  {
    if (is_null($this->get('enabled_services'))) :
      $default = [
        'full-bot',
        'full-cloud',
        'full-templates',
        'full-access',
        'full-clone',
        'full-update',
        'full-security',
        'full-ai',
      ];
      $this->set('enabled_services', $default);
    endif;

    if (!get_option('full_customer/seo-migrated')) :
      $services   = $this->get('enabled_services');
      $legacySeo  = ['full-ai-images', 'full-ai-meta', 'full-clone'];
      $found      = false;

      foreach ($legacySeo as $key) :
        $index = array_search($key, $services, true);
        if ($index !== false) :
          $found = true;
          unset($services[$index]);
        endif;
      endforeach;

      if ($found) :
        $services[] = 'full-seo';
        $this->set('enabled_services', $services);
      endif;

      update_option('full_customer/seo-migrated', 1, false);
    endif;

    return $this->get('enabled_services') ?? [];
  }

  private function optionEnvSuffix(): string
  {
    return 'PRD' === getFullEnv() ? '' : 'dev-';
  }
}
