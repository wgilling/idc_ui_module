<?php

namespace Drupal\idc_ui_module\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'Footer' Block.
 *
 * @Block(
 *   id = "footer_block",
 *   admin_label = @Translation("Footer block"),
 *   category = @Translation("Custom"),
 * )
 */
class FooterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $logged_in = \Drupal::currentUser()->isAuthenticated();

    return [
      '#theme' => 'footer_template',
    ];
  }

}
