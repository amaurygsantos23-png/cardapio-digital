<?php

use Full\Customer\License; ?>
<div class="full-templates-admin-body">
  <div class="templately-wrapper">
    <div class="templately-header">
      <div class="templately-logo">
        <img src="<?php echo esc_url(fullGetImageUrl('logo-novo.png')) ?>" alt="Logo FULL">
      </div>
    </div>

    <div class="templately-container templately-pages-container">
      <div class="templately-container-row" id="endpoint-viewport">
        <div class="templately-contents">

          <div class="templately-contents-header">
            <div class="templately-contents-header-inner">
              <div class="templately-header-title">
                <h3>Controle de extensões</h3>
              </div>
            </div>
          </div>

          <div class="full-page-content">
            <div id="full-widgets"></div>

            <br>
            <br>

            <button id="update-widgets">Salvar alterações</button>

            <br><br>
            <br><br>

          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script id="widget-container-template" type="text/template">
  <div class="widget-category-container">
    <h4></h4>
    <div class="widgets-grid">
  </div>
</script>

<script id="widget-template" type="text-template">
  <div class="full-widget">
    <div class="icon"><img></div>
    <div class="description">
      <h4>
        <span class="widget-name"></span>
      </h4>
      <div class="widget-description"></div>
      <a href="{url}" target="_blank" rel="noopener noreferrer">Saiba mais</a>
    </div>

    <div class="status"></div>
  </div>
</script>

<script id="widget-toggle-template" type="text-template">
  <label class="toggle-switch-sm">
    <input type="checkbox" class="toggle-switch-input">
    <span class="toggle-switch-label">
      <span class="toggle-switch-indicator"></span>
    </span>
  </label>
</script>