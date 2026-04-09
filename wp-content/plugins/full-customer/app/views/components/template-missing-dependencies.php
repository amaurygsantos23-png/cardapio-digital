<?php if (isset($dependencies) && $dependencies) : ?>
  <p>Precisaremos instalar/ativar alguns plugins para que este template seja usado neste site.</p>

  <?php if ($dependencies['inactive']) : ?>
    <h5>Os seguintes plugins serão ativados em seu site </h5>
    <ul>
      <?php foreach ($dependencies['inactive'] as $item) : ?>
        <li>
          <a href="<?php echo esc_url($item->url) ?>" target="_blank" rel="noopener noreferrer">
            <?php echo $item->name ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
    <br>
  <?php endif; ?>

  <?php if ($dependencies['uninstalled']) : ?>
    <h5>Os seguintes plugins serão instalados e ativados em seu site </h5>
    <ul>
      <?php foreach ($dependencies['uninstalled'] as $item) : ?>
        <li>
          <a href="<?php echo esc_url($item->url) ?>" target="_blank" rel="noopener noreferrer">
            <?php echo $item->name ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
<?php endif; ?>