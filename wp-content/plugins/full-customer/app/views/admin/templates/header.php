<?php $full = fullCustomer(); ?>
<div class="templately-header">
  <div class="templately-logo">
    <img src="<?php echo esc_url(fullGetImageUrl('logo-novo.png')) ?>" alt="Logo FULL">
  </div>
  <div class="templately-nav-wrapper templately-menu">
    <ul class="templately-nav">
      <?php if ($full->isServiceEnabled('full-templates')) : ?>
        <li class="tn-item templately-nav-item <?php echo 'templates' === $endpointView ? 'templately-nav-active' : '' ?>">
          <a href="<?php echo esc_url(fullGetTemplatesUrl('templates')) ?>" data-endpoint="templates">
            Templates
          </a>
        </li>
      <?php endif; ?>
      <?php if ($full->isServiceEnabled('full-cloud')) : ?>
        <li class="tn-item templately-nav-item <?php echo 'cloud' === $endpointView ? 'templately-nav-active' : '' ?>">
          <a href="<?php echo esc_url(fullGetTemplatesUrl('cloud')) ?>" data-endpoint="cloud">
            Cloud
          </a>
        </li>
      <?php endif; ?>
    </ul>
  </div>
</div>