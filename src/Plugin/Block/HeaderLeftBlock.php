<?php

namespace Drupal\idc_ui_module\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Header Left' Block.
 *
 * @Block(
 *   id = "header_left_block",
 *   admin_label = @Translation("Header Left block"),
 *   category = @Translation("Custom"),
 * )
 */
class HeaderLeftBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    return [
      '#theme' => 'header_left_template',
      '#test_var' => $this->t('moo'),
    ];
  }

}
