<?php

namespace Full\Customer\Elementor\Filters;

defined('ABSPATH') || exit;

function manageElementorLibraryPostsColumns(array $columns): array
{
  if (get_option('full/template-status', 0)) {
    $columns['full_templates'] = 'FULL.templates';
  }

  return $columns;
}
