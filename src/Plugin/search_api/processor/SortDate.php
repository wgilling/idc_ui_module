<?php

namespace Drupal\idc_ui_module\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
use Drupal\controlled_access_terms\EDTFUtils;

/**
 * Adds the item's single sort year value to the indexed data.
 *
 * @SearchApiProcessor(
 *   id = "sort_date",
 *   label = @Translation("Sort Date"),
 *   description = @Translation("Derived from item's 'Sort date', 'Pub dates', or 'Creation dates' to the indexed data."),
 *   stages = {
 *     "add_properties" = 0,
 *   },
 *   locked = true,
 *   hidden = true,
 * )
 */
class SortDate extends ProcessorPluginBase {

  /**
   * {@inheritdoc}
   */
  public function getPropertyDefinitions(DatasourceInterface $datasource = NULL) {
    $properties = [];

    if (!$datasource) {
      $definition = [
        'label' => $this->t('Sort Date'),
        'description' => $this->t('The sort date for each item.'),
        'type' => 'date',
        'processor_id' => $this->getPluginId(),
      ];
      $properties['sort_date'] = new ProcessorProperty($definition);
    }

    return $properties;
  }

  /**
   * {@inheritdoc}
   */
  public function addFieldValues(ItemInterface $item) {
    $node = $item->getOriginalObject()->getValue();
    /*
     This logic needs to save only ONE year value -- which
     should be:
    
      field_sort_date
        If this has a value, use this and we are done.
    
     else take the earliest year value from either: 
      field_date_published
      field_date_created
    */
    if ($node) {
      if ($node->hasField('field_sort_date') && !$node->field_sort_date->isEmpty()) {
        $date = $node->field_sort_date->value;
      }
      else {
        // Pick the lower of the possible values from pub or created dates.
        $date = FALSE;
        if ($node->hasField('field_date_published') && !$node->field_date_published->isEmpty()) {
          $date = $this->_getEarliestDateFrom($node, 'field_date_published');
        }
        if ($node->hasField('field_date_created') && !$node->field_date_created->isEmpty()) {
          $tmp = $this->_getEarliestDateFrom($node, 'field_date_created');
          $date = ($tmp < $date) ? $tmp : $date;
        }
      }
      if ($date) {
        $fields = $item->getFields(FALSE);
        $fields = $this->getFieldsHelper()
          ->filterForPropertyPath($fields, NULL, 'sort_date');
        foreach ($fields as $field) {
          $field->addValue($date);
        }
      }
    }
  }

  function _getEarliestDateFrom($node, $fieldname) {
    $dates = [];
    foreach ($node->get("$fieldname") as $node_field_item) {
      $this_date = $node_field_item->value;
      $date = '';
      if ($this_date != "nan") {
        // Special handling for YYYY/YYYY
        if (strstr($this_date, "/") && (strlen($this_date) == 9) && (substr($this_date, 4,1) == "/")) {
          $parts = explode("/", $this_date, 2);
          if (count($parts) == 2 && is_numeric(0 + $parts[0]) && is_numeric(0 + $parts[1])) {
            $date = ($parts[0] < $parts[1]) ? $parts[0] : $parts[1];
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
    $min_date = strtotime(min(array_values($dates)));
    return $min_date;
  }

}
