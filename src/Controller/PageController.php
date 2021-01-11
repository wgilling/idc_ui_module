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
  public function collections() {
    $featured_collections = \Drupal::entityTypeManager()
      ->getListBuilder('node')
      ->getStorage()
      ->loadByProperties([
        'field_featured_item' => true,
        'type.target_id' => 'collection_object'
      ]);

    $featured_collections_array = array();

    foreach ($featured_collections as &$collection) {
      if (!$collection->isPublished()) {
        continue;
      }

      $collection_id = $collection->id();

      $media = \Drupal::entityTypeManager()
        ->getListBuilder('media')
        ->getStorage()
        ->loadByProperties([
          'bundle' => 'image',
          'status' => 1,
          'field_media_of' => $collection_id,
        ]);

      $thumbnail_id = array_values($media)[0]->thumbnail->target_id;

      $thumbnail = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->load($thumbnail_id);

      if ($thumbnail) {
        $image_url = $thumbnail->getFileUri();
        $image_display_url = file_create_url($image_url);
      }

      $obj= (object) ['url' => $image_display_url, 'collection' => $collection];

      array_push($featured_collections_array, $obj);
    }

    return [
      '#theme' => 'page--collections',
      '#featured_collections' => $featured_collections_array,
    ];
  }

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function collection(\Drupal\node\Entity\Node $collection) {
    $featured_items = \Drupal::entityTypeManager()
      ->getListBuilder('node')
      ->getStorage()
      ->loadByProperties([
        'field_featured_item' => true,
        'type.target_id' => 'islandora_object',
        'field_member_of' => $collection->id()
      ]);

    $featured_items_array = array();

    foreach ($featured_items as &$item) {
      if (!$item->isPublished()) {
        continue;
      }

      $item_id = $item->id();

      $media = \Drupal::entityTypeManager()
        ->getListBuilder('media')
        ->getStorage()
        ->loadByProperties([
          'bundle' => 'image',
          'status' => 1,
          'field_media_of' => $item_id,
        ]);

      $thumbnail_id = array_values($media)[0]->thumbnail->target_id;

      $thumbnail = \Drupal::entityTypeManager()
      ->getStorage('file')
      ->load($thumbnail_id);

      if ($thumbnail) {
        $image_url = $thumbnail->getFileUri();
        $image_display_url = file_create_url($image_url);
      }

      $obj= (object) ['url' => $image_display_url, 'item' => $item];

      array_push($featured_items_array, $obj);
    }

    return [
      '#theme' => 'page--collection',
      '#collection' => $collection,
      '#featured_items' => $featured_items_array,
    ];
  }

  /**
   * Returns a simple page.
   *
   * @return array
   *   A simple renderable array.
   */
  public function item(\Drupal\node\Entity\Node $item) {
    return [
      '#theme' => 'page--item',
      '#item' => $item,
    ];
  }
}
