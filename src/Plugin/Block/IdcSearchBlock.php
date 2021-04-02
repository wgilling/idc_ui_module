<?php

namespace Drupal\idc_ui_module\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a simple search block intended to be placed in a header
 *
 * @Block(
 *    id = "idc_search_block",
 *    admin_label = @Translation("IDC Search Block"),
 *    category = @Translation("IDC"),
 * )
 */
class IdcSearchBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'idc_search_template',
    ];
  }

}
