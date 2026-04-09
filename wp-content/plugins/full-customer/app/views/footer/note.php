<?php $full = fullCustomer(); ?>
<a href="https://full.services/" style="visibility: hidden; user-select: none; pointer-events: none; display: none;">plugins premium WordPress</a>

<?php if ($full->getBranding('backlink_url')) : ?>
  <a href="<?php echo esc_url($full->getBranding('backlink_url')) ?>" style="visibility: hidden; user-select: none; pointer-events: none; display: none;"><?php echo $full->getBranding('backlink_text')  ?></a>
<?php endif; ?>