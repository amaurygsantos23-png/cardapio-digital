<?php

namespace Full\Customer\Elementor;

use Elementor\TemplateLibrary\Source_Local as ElementorLocal;

class Exporter extends ElementorLocal
{
  public function export(int $postId): string
  {
    $data = $this->get_data(['template_id' => $postId]);
    $data = is_array($data) ? $data : [];

    if (!isset($data['content'])) :
      $data['content'] = $data;
    endif;

    if (!isset($data['type'])) :
      $data['type'] = $this->get_template_type($postId);
    endif;

    return fullJsonEncode($data);
  }
}
