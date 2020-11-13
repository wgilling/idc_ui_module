<?php
namespace Drupal\idc_ui_module\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * Provides route responses for the Example module.
 */
class PageController extends ControllerBase {

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function PageOne() {
    return [
      '#theme' => 'my_template',
      '#test_var' => $this->t('moo'),
    ];
  }

}
