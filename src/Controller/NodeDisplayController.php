<?php

namespace Drupal\idc_ui_module\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Entity\EntityInterface;
use Drupal\node\Controller\NodeViewController;
use Drupal\idc_ui_module\Controller\CollectionsController;

class NodeDisplayController extends NodeViewController {

  public function view(EntityInterface $node, $view_mode = 'full', $langcode = NULL) {
    $node_type = $node->get('type')->getString();

    // if ($node_type == 'collection_object') {
    //   return CollectionsController::collection($node);
    // } elseif ($node_type == 'islandora_object') {
    //   return CollectionsController::item($node);
    // } else {
      return parent::view($node, $view_mode, $langcode);
    // }
  }
}
