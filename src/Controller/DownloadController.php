<?php

namespace Drupal\idc_ui_module\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use ZipStream\ZipStream;


/**
 * Class DownloadController.
 */
class DownloadController extends ControllerBase {
  /**
   * The main download method.
   *
   * @param string $entity_type
   *   The entity type.
   * @param int $entity_id
   *   The entity id.
   *
   * @return \Symfony\Component\HttpFoundation\Response
   *   Return the file.
   *
   * @throws \InvalidArgumentException
   */
  public function download($entity_type, $entity_id) {
    try {
      $media_links = $this->getFiles($entity_id);

      if (count($media_links) === 0) {
        $this->messenger()->addError($this->t('No files found for this entity to be downloaded'));

        return new RedirectResponse("$entity_type/$entity_id");
      }

      $filename = "$entity_type-$entity_id.zip";

      ob_clean();

      $zip = new ZipStream($filename);
      foreach ($media_links as $media_link) {
        if (!$media_link->url) {
          continue;
        }

        $zip->addFileFromPath($media_link->file_name, $media_link->url);
      }

      $zip->finish();

      ob_end_flush();
      exit();

    } catch (StorageException $e) {
      $this->messenger()->addError($this->t('There were issues generating the package of media downloads.'));

      return new RedirectResponse("$entity_type/$entity_id");
    }
  }

  private function getFiles($node_id) {
    $current_user = \Drupal::currentUser();
    $authorized_roles = ['administrator', 'collection_level_admin', 'global_admin'];
    $is_authorized = !!count(array_intersect($authorized_roles, array_values($current_user->getRoles())));

    $variables['is_authorized'] = $is_authorized;

    $MEDIA_USES = array('Original File', 'Intermediate File', 'Service File', 'FITS File');
    $MEDIA_TYPES = array(
      (object) [
        'type' => 'image',
        'source_field' => 'field_media_image',
      ],
      (object) [
        'type' => 'file',
        'source_field' => 'field_media_file',
      ],
      (object) [
        'type' => 'document',
        'source_field' => 'field_media_document',
      ],
      (object) [
        'type' => 'audio',
        'source_field' => 'field_media_audio_file',
      ],
      (object) [
        'type' => 'video',
        'source_field' => 'field_media_video_file',
      ],
      (object) [
        'type' => 'extracted_text',
        'source_field' => 'field_media_file',
      ],
      (object) [
        'type' => 'fits_technical_metadata',
        'source_field' => 'field_media_file',
      ],
    );

    $media_links = array();

    foreach ($MEDIA_TYPES as $media_type_obj) {
      foreach ($MEDIA_USES as $media_use) {
        $taxonomy = \Drupal::entityTypeManager()
        ->getListBuilder('taxonomy_term')
        ->getStorage()
        ->loadByProperties([
          'status' => 1,
          'name' => $media_use,
        ]);

        $medias = \Drupal::entityTypeManager()
        ->getListBuilder('media')
        ->getStorage()
        ->loadByProperties([
          'bundle' => $media_type_obj->type,
          'status' => 1,
          'field_media_use' => array_values($taxonomy)[0]->id(),
          'field_media_of' => $node_id,
        ]);

        foreach ($medias as &$media) {

          $dynamic_field_media = $media_type_obj->source_field;

          $file_id = $media->$dynamic_field_media->target_id;

          $file = \Drupal::entityTypeManager()
          ->getStorage('file')
          ->load($file_id);

          if ($file) {
            $url = $file->get('uri')->value;

            if ($media->get('field_restricted_access')->getString() == "1" && !$is_authorized && $media_use != 'Service File') {
              $url = null;
            }

            $obj = (object) ['url' => $url, 'file_name' => $file->get('filename')->getString(), 'media_type' => str_replace("_", " ", ucwords($media_type_obj->type, "_")), 'media_use' => $media_use];

            array_push($media_links, $obj);
          }
        }
      }
    }

    return $media_links;
  }
}
