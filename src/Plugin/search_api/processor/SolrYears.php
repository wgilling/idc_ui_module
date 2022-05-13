<?php

namespace Drupal\idc_ui_module\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\controlled_access_terms\EDTFUtils;

/**
 * Adds the item's year values derived from 'Sort date', 'Pub dates', and 'Created dates' to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "solr_years",
 *   label = @Translation("Solr Years"),
 *   description = @Translation("Derived from all year values taken from item's 'Sort date', 'Pub dates', and 'Created dates' to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class SolrYears extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Solr Years'),
        'description' => $this->t('The year values for each item (Sort date, Pub dates, Created dates).'),
        'type' => 'string',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['solr_years'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $node = $item->getOriginalObject()->getValue();

    /*
     This logic needs to save the year values from: 
      field_sort_date
      field_date_published
      field_date_created
    */
    if ($node) {
      $dates = [];
      if ($node->hasField('field_sort_date') && !$node->field_sort_date->isEmpty()) {
        $dates[$node->field_sort_date->value] = $node->field_sort_date->value;
      }
      else {
        // Pick the lower of the possible values from pub or created dates.
        $date = FALSE;
        if ($node->hasField('field_date_published') && !$node->field_date_published->isEmpty()) {
          $dates[] = array_merge($dates, $this->_getDatesFrom($node, 'field_date_published'));
        }
        if ($node->hasField('field_date_created') && !$node->field_date_created->isEmpty()) {
          $dates[] = array_merge($dates, $this->_getDatesFrom($node, 'field_date_created'));
        }
      }
      if (count($dates) > 0) {
        $fields = $item->getFields(FALSE);
        $fields = $this->getFieldsHelper()
          ->filterForPropertyPath($fields, NULL, 'solr_years');
        foreach ($fields as $field) {
          foreach ($dates as $date) {
            $field->addValue($date);
          }
        }
      }
    }
  }

  function _getDatesFrom($node, $fieldname) {
    $dates = [];
    foreach ($node->get("$fieldname") as $node_field_item) {
      $this_date = $node_field_item->value;
      $date = '';
      if ($this_date != "nan") {
        // Special handling for YYYY/YYYY
        if (strstr($this_date, "/") && (strlen($this_date) == 9) && (substr($this_date, 4,1) == "/")) {
          $parts = explode("/", $this_date, 2);
          if (count($parts) == 2 && is_numeric(0 + $parts[0]) && is_numeric(0 + $parts[1])) {
            $date_from = ($parts[0] < $parts[1]) ? $parts[0] : $parts[1];
            $date_to = ($parts[0] < $parts[1]) ? $parts[1] : $parts[0];
            for ($d = $date_from; $d < $date_to; $d++) {
              $dates[$d] = $d;
            }
          }
        }
        else {
          // Default EDTF handling for a date value coming from the node field.
          $iso = EDTFUtils::iso8601Value($this_date);
          $iso_one = explode("T", $iso)[0];
          $components = explode('-', $iso_one);
          $date = array_shift($components);
        }
        $dates[$date] = $date;
      }
    }
    return $dates;
  }

}
