<?php
namespace Drupal\idc_ui_module\Controller;

use Drupal\Core\Controller\ControllerBase;

class CollectionsController extends ControllerBase {
  public function collections() {
    $featured_collections = \Drupal::entityTypeManager()
      ->getListBuilder('node')
      ->getStorage()
      ->loadByProperties([
        'field_featured_item' => true,
        'type.target_id' => 'collection_object'
      ]);

    $thumbnail_taxonomy_term = \Drupal::entityTypeManager()
      ->getListBuilder('taxonomy_term')
      ->getStorage()
      ->loadByProperties([
        'status' => 1,
        'name' => 'Thumbnail Image',
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
          'field_media_use' => array_values($thumbnail_taxonomy_term)[0]->id(),
        ]);

      $thumbnail_id = array_values($media)[0]->thumbnail->target_id;

      $thumbnail = \Drupal::entityTypeManager()
        ->getStorage('file')
        ->load($thumbnail_id);

      $image_display_url = '';

      if ($thumbnail) {
        $image_url = $thumbnail->getFileUri();
        $image_display_url = file_create_url($image_url);
      }

      $obj= (object) ['url' => $image_display_url, 'title' => $collection->get('title')->getString(), 'id' => $collection->id()];

      array_push($featured_collections_array, $obj);
    }

    return [
      '#theme' => 'page--collections',
      '#featured_collections' => $featured_collections_array,
      '#cache' => [
        'max-age'=> 0
      ]
    ];
  }

  public function collection(\Drupal\node\Entity\Node $collection) {
    $featured_items = \Drupal::entityTypeManager()
      ->getListBuilder('node')
      ->getStorage()
      ->loadByProperties([
        'field_featured_item' => true,
        'type.target_id' => 'islandora_object',
        'field_member_of' => $collection->id()
      ]);

    // Get the 'Thumbnail Image' taxonomy term
    $thumbnail_taxonomy_term = \Drupal::entityTypeManager()
      ->getListBuilder('taxonomy_term')
      ->getStorage()
      ->loadByProperties([
        'status' => 1,
        'name' => 'Thumbnail Image',
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
          'field_media_use' => array_values($thumbnail_taxonomy_term)[0]->id()
        ]);

      $thumbnail_id = array_values($media)[0]->thumbnail->target_id;

      $thumbnail = \Drupal::entityTypeManager()
        ->getStorage('file')
        ->load($thumbnail_id);

      $image_display_url = '';

      if ($thumbnail) {
        $image_url = $thumbnail->getFileUri();
        $image_display_url = file_create_url($image_url);
      }

      $obj = (object) ['url' => $image_display_url, 'title' => $item->get('title')->getString(), 'id' => $item->id()];

      array_push($featured_items_array, $obj);
    }

    $lang_entity_refs = $collection->get('field_description')->referencedEntities();
    $english = null;

    foreach ($lang_entity_refs as &$langTerm) {
      if ($langTerm->get('field_language_code')->getString() == 'eng') {
        $english = $langTerm;
      }
    }

    $descriptions = $collection->get('field_description')->getValue();
    $primary_description = null;

    foreach ($descriptions as &$description) {
      if ($description['target_id'] == $english->get('tid')->getString()) {
        $primary_description = $description['value'];
      }
    }

    $citable_url = $collection->get('field_citable_url')->getValue()[0]["uri"];

    return [
      '#theme' => 'page--collection',
      '#collection' => $collection,
      '#featured_items' => $featured_items_array,
      '#primary_description' => $primary_description,
      '#citable_url' => $citable_url,
      '#cache' => [
        'max-age'=> 0
      ]
    ];
  }

  public function item(\Drupal\node\Entity\Node $item) {
    return [
      '#theme' => 'page--item',
      '#item' => $item,
    ];
  }
}
