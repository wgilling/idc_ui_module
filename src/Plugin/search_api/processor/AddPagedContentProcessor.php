<?php
namespace Drupal\idc_ui_module\Plugin\search_api\processor;
use Drupal\Core\Entity\EntityInterface;
use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\SearchApiException;
/**
 * Allows indexing of nested reverse entity reference, specifically the Media object
 * of a Page in a Paged Content.
 *
 * @SearchApiProcessor(
 *   id = "add_page_media_to_paged_content",
 *   label = @Translation("Page metadata"),
 *   description = @Translation("Adds Page metadata to related Paged Content"),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = false
 * )
 */
class AddPagedContentProcessor extends ProcessorPluginBase {
  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Page metadata'),
        'description' => $this->t('Adds Page metadata to related Paged Content'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['idc_reverse_reference_parent_of_page'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $datasourceId = $item->getDatasourceId();
    if ($datasourceId == 'entity:node') {
      $entity = $item->getOriginalObject()->getValue();
      $node_id = $entity->id();
      $node = \Drupal::entityTypeManager()->getStorage('node')->load($node_id);
      $node_type = $node->get('type')->getString();

      if ($node_type === 'islandora_object') {
        $model_type_expected = $this->checkModelType($entity, 'Paged Content');

        if (!$model_type_expected) {
          return false;
        }

        $children = \Drupal::entityTypeManager()
          ->getListBuilder('node')
          ->getStorage()
          ->loadByProperties([
            'status' => 1,
            'field_member_of' => $entity->id(),
          ]);

        $edited_text_fields = array();

        foreach ($children as $child_node) {
          if (!$this->checkModelType($child_node, 'Page')) {
            continue;
          }

          $taxonomy = \Drupal::entityTypeManager()
            ->getListBuilder('taxonomy_term')
            ->getStorage()
            ->loadByProperties([
              'status' => 1,
              'name' => 'Extracted Text',
            ]);

          $medias = \Drupal::entityTypeManager()
            ->getListBuilder('media')
            ->getStorage()
            ->loadByProperties([
              'status' => 1,
              'field_media_use' => array_values($taxonomy)[0]->id(),
              'field_media_of' => $child_node->id(),
            ]);

          foreach ($medias as &$media) {
            $edited_text = $media->get('field_edited_text')->getValue()[0]['value'];

            array_push($edited_text_fields, $edited_text);
          }
        }

        // A view count was found, add it to the relevant field.
        $fields = $this->getFieldsHelper()->filterForPropertyPath($item->getFields(), NULL, 'idc_reverse_reference_parent_of_page');
        foreach ($fields as $field) {
          $extracted_text_string = implode(" ", $edited_text_fields);
          $field->addValue($extracted_text_string);
        }
      }
    }
  }

  private function checkModelType($node, $expected_type) {
    $model_references = $node->get('field_model')->referencedEntities();

    if ($model_references) {
      $model_type = $model_references[0]->get('name')->getString();
    }

    return $model_type == $expected_type;
  }
}
