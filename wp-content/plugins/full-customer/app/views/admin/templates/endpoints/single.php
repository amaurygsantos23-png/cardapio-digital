<?php

use Full\Customer\Elementor\TemplateManager;

$itemId = filter_input(INPUT_GET, 'item', FILTER_VALIDATE_INT);
$templateAsScript = isset($templateAsScript);
$item   = $templateAsScript ? null : TemplateManager::instance()->getItem($itemId);
?>

<?php if (!$item instanceof \stdClass && !$templateAsScript) : ?>

  <div class="templately-contents templately-item-details">
    <div class="templately-item-details-header">
      <div class="templately-row align-center justify-between">
        <div class="templately-col">
          <div class="templately-items-header">
            <h3>Item não localizado</h3>
          </div>
        </div>
      </div>
    </div>
  </div>

<?php else : ?>

  <div class="templately-contents templately-item-details">
    <div class="templately-item-details-header">
      <div class="templately-row align-center justify-between">
        <div class="templately-col">
          <div class="templately-items-header" style="text-align: left;">
            <a href="<?php echo esc_url(fullGetTemplatesUrl('templates')) ?>" data-endpoint="templates" class="endpoint-nav" style="cursor: pointer">
              <i class="tio-arrow-backward"></i>
              Voltar
            </a>

            <h1><?php echo $templateAsScript ? '{title}' : $item->title ?></h1>
          </div>
        </div>
      </div>
    </div>
    <div class="templately-row">
      <div class="templately-col-8">
        <div class="templately-items-banner-wrapper">
          <div class="templately-items-banner">
            <div class="templately-badge templately-<?php echo $templateAsScript ? '{priceTag}' : $item->priceTag ?> templately-details-banner-badge">
              <span>
                <?php if ($templateAsScript) : ?>
                  {priceTagTitle}
                <?php else : ?>
                  <?php echo $item->price > 0 ? 'Premium' : 'Grátis' ?>
                <?php endif; ?>
              </span>
            </div>
            <img src="<?php echo $templateAsScript ? '{thumbnailUrl}' : $item->thumbnailUrl ?>" alt="<?php echo $templateAsScript ? '{title}' : $item->title ?>">
          </div>
        </div>
        <div class="templately-item-description" style="text-align: left">
          <?php echo $templateAsScript ? '{description}' : $item->description ?>
        </div>
      </div>
      <div class="templately-col-4">
        <div class="templately-item-details-sidebar-wrapper">
          <div class="templately-items-sidebar templately-item-widget" style="text-align: left">
            <ul>
              <li>
                <span class="label">Categorias:</span> <?php echo $templateAsScript ? '{categoriesList}' : implode(', ', wp_list_pluck($item->categories, 'name')) ?>
              </li>
              <li class="templately-details-price-wrapper">
                <span class="label">Preço:</span>
                <span class="templately-details-price">
                  <?php if ($templateAsScript) : ?>
                    {priceTagTitle}
                  <?php else : ?>
                    <?php echo $item->price > 0 ? 'Premium' : 'Grátis' ?>
                  <?php endif; ?>
                </span>
              </li>
            </ul>

            <br>
            <br>

            <?php if ($templateAsScript) : ?>

              {button}

            <?php else : ?>

              <?php if ($item->canBeInstalled) : ?>
                <a class="templately-button tb-import tb-purchase" data-js="insert-item" data-item='<?php echo wp_json_encode($item) ?>' style="background-color: #eabc32; margin-right: 1em;">
                  <i class="tio-download-to" style="margin-right: 5px;"></i>
                  Inserir
                </a>
              <?php else : ?>
                <a target="_blank" href="<?php echo esc_url($item->purchaseUrl) ?>" class="templately-button tb-import tb-purchase" style="background-color: #eabc32">
                  <i class="tio-shopping-icon" style="margin-right: 5px;"></i>
                  Comprar
                </a>
              <?php endif; ?>
            <?php endif; ?>

          </div>
        </div>

        <?php if ($templateAsScript) : ?>
          {galleryContainer}
        <?php elseif ($item && isset($item->gallery) && $item->gallery) : ?>

          <div class="templately-item-widget templately-layouts-in-packs" id="template-gallery" style="text-align: left">
            <h3>Galeria do template</h3>
            <div class="templately-carousel-wrapper">
              <div class="full-template-carousel">
                <?php foreach ($item->gallery as $src) : ?>
                  <div class="templately-layouts-in-packs-item">
                    <a href="<?php echo esc_url($src) ?>">
                      <div class="templately-layouts-in-packs-item-image">
                        <img src="<?php echo $src ?>" alt="Imagem do template">
                      </div>
                    </a>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>

        <?php endif; ?>


      </div>
    </div>
  </div>

  <script type="text/template" id="tpl-single-button-insert-item">
    <a class="templately-button tb-import tb-purchase" data-js="insert-item" data-item='{json}' style="background-color: #eabc32; margin-right: 1em;">
      <i class="tio-download-to" style="margin-right: 5px;"></i>
      Inserir
    </a>
  </script>

  <script type="text/template" id="tpl-single-button-purchase-item">
    <a target="_blank" href="{purchaseUrl}" class="templately-button tb-import tb-purchase" style="background-color: #eabc32">
      <i class="tio-shopping-icon" style="margin-right: 5px;"></i>
      Comprar
    </a>
  </script>

  <script type="text/template" id="tpl-gallery-container">
    <div class="templately-item-widget templately-layouts-in-packs" id="template-gallery" style="text-align: left">
      <h3>Galeria do template</h3>
      <div class="templately-carousel-wrapper">
        <div class="full-template-carousel">
          {galleryItems}
        </div>
      </div>
    </div>
  </script>

  <script type="text/template" id="tpl-single-gallery-item">
    <div class="templately-layouts-in-packs-item ">
      <a href="{src}">
        <div class="templately-layouts-in-packs-item-image">
          <img src="{src}" alt="Imagem do template">
        </div>
      </a>
    </div>
  </script>


<?php endif; ?>