<?php

namespace Drupal\idc_ui_module\Controller;

use Drupal\Core\Controller\ControllerBase;

class SearchController extends ControllerBase {

  public function searchPage() {
    return [
      '#theme' => 'page--search'
    ];
  }

}
