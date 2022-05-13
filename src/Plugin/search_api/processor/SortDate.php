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
        'type' => 'integer',
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
    
     field_date_published
        If any values, use the earliest one - we are done.

     field_date_created
        else, use earliest value from this.
    */
    if ($node) {
      if ($node->hasField('field_sort_date') && !$node->field_sort_date->isEmpty()) {
        $date = $node->field_sort_date->value;
        $this->logger('got field_sort_date, date = ' . $date);
      }
      elseif ($node->hasField('field_date_published') && !$node->field_date_published->isEmpty()) {
        $date = $this->_getEarliestDateFrom($node, 'field_date_published');
        $this->logger('got field_date_published, date = ' . $date);
      }
      elseif ($node->hasField('field_date_created') && !$node->field_date_created->isEmpty()) {
        $date = $this->_getEarliestDateFrom($node, 'field_date_created');
        $this->logger('got field_date_created, date = ' . $date);
      }
      else {
        $date = FALSE;
      }
      if ($date) {
        $this->logger('ok a');
        $fields = $item->getFields(FALSE);
        $fields = $this->getFieldsHelper()
          ->filterForPropertyPath($fields, NULL, 'sort_date');
        $this->logger('ok b');
        foreach ($fields as $field) {
          $this->logger('a field : date = ' . $date);
        //  $this->logger('field keys : ' . print_r($field, true));
          $field->addValue($date);
        }
      }

    }
  }

  function logger($string) {
    \Drupal::logger('idc_ui_module')->info($string);
    // echo $string . "\n";
  }

  function _getEarliestDateFrom($node, $fieldname) {
    $this_date = $node->$fieldname->value;
    $date = '';
    if ($this_date != "nan") {
      $this->logger('_getEarliestDateFrom $this_date = ' . $this_date);
      // Special handling for YYYY/YYYY
      if (strstr($this_date, "/") && (strlen($this_date) == 9) && (substr($this_date, 4,1) == "/")) {
        $parts = explode("/", $this_date, 2);
        if (count($parts) == 2 && is_numeric(0 + $parts[0]) && is_numeric(0 + $parts[1])) {
          $lower_val = ($parts[0] < $parts[1]) ? $parts[0] : $parts[1];
          return $lower_val;
        }
      }
      $iso = EDTFUtils::iso8601Value($this_date);
      $iso_one = explode("T", $iso)[0];
      $components = explode('-', $iso_one);
      $date = array_shift($components);
    }
    return $date;
  }

}
