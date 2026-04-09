<?php $full = fullCustomer(); ?>

<div class="wrap full-customer-page" id="fc-connection">
  <div class="login-container">
    <div class="col-instructions">
      <a href="<?php echo esc_url($full->getBranding('plugin-author-url', 'https://painel.full.services/login/')) ?>" target="_blank" rel="noopener noreferrer" class="logo-img">
        <img src="<?php echo esc_url($full->getBranding('admin-page-logo-url', fullGetImageUrl('logo-novo.png'))) ?>" alt="<?php echo $full->getBranding('plugin-author', 'FULL.') ?>">
      </a>

      <img src="<?php echo esc_url(fullGetImageUrl('wordpress.svg')) ?>" alt="WordPress" class="wordpress-img">

      <div class="instructions-text">
        <?php ob_start(); ?>
        <h2>Facilite a gestão do seu WordPress</h2>

        <ul class="checkmark-list">
          <li>
            <strong>Plugins e temas</strong>
            <span>Atualize, remova e ative plugins e temas premium</span>
          </li>
          <li>
            <strong>Segurança e performance</strong>
            <span>Controle o uptime e segurança do seu site diretamente do dashboard</span>
          </li>
        </ul>
        <?php echo wp_kses_post($full->getBranding('admin-page-content', ob_get_clean())); ?>
      </div>

    </div>

    <div class="col-login">
      <?php if (isFullConnected()) : ?>

        <div id="full-connect" class="full-form">
          <h2>
            Site conectado!
          </h2>

          <p>Site conectado com a conta <strong><?= getFullConnectionData()->connection_email; ?></strong></p>
          <a href="<?= getFullConnectionData()->dashboard_url; ?>" class="full-primary-button full-button-block" target="_blank" rel="noopener noreferrer" style="margin-top: 1rem">Acessar painel</a>
        </div>

      <?php else : ?>

        <form id="full-connect" class="full-form">
          <input type="hidden" name="action" value="full/connect-site">
          <?php wp_nonce_field('full/connect-site'); ?>

          <label for="customer-email">
            <span>Seu e-mail no painel FULL.</span>
            <input placeholder="Insira seu e-mail de acesso" type="email" name="email" id="customer-email" autocomplete="email" required>
          </label>

          <button class="full-primary-button full-button-block">Realizar conexão</button>
        </form>

      <?php endif; ?>

      <div id="full-connection-validate">
        <a href="<?php echo esc_url(admin_url('admin.php?page=full-connection&full=verify_license')) ?>">
          Verificar licença PRO
        </a>
        |
        <a href="<?php echo esc_url(admin_url('admin.php?page=full-connection&full=repo_clear')) ?>">
          Atualizar repositório
        </a>
      </div>

    </div>
  </div>
</div>