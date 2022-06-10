<?php

namespace Drupal\idc_ui_module\Plugin\search_api\processor;

use Drupal\search_api\Datasource\DatasourceInterface;
use Drupal\search_api\Item\ItemInterface;
use Drupal\search_api\Processor\ProcessorPluginBase;
use Drupal\search_api\Processor\ProcessorProperty;
// Do not need to include EDTFUtils unless EDTF parsing needed to pull out the years
// use Drupal\controlled_access_terms\EDTFUtils;

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
        'type' => 'date',
        'is_list' => TRUE,
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
          $dates = $this->_getDatesFrom($node, 'field_date_published', $dates);
        }
        if ($node->hasField('field_date_created') && !$node->field_date_created->isEmpty()) {
          $dates = $this->_getDatesFrom($node, 'field_date_created', $dates);
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

  function _getDatesFrom($node, $fieldname, array $dates) {
    foreach ($node->get("$fieldname") as $node_field_item) {
      $this_date = $node_field_item->value;
      $date = '';
      if ($this_date != "nan") {
        // Special handling for YYYY/YYYY
        // There are several cases of EDTF date formats that will break this or can potentially be handled individually.
        if (strstr($this_date, "?") || strstr($this_date, '~') || strstr($this_date, "..") || strstr($this_date, "%") || strstr($this_date, "[")) {
        }
        // Potential handling for formats like YYYY-MM/YYYY-MM and YYYY-MM-DD/YYYY-MM-DD, but
        // the loop size is hard to guess... do we create an additional entry for every month
        // between YYYY-MM (a) and YYYY-MM (b) or does this just skip year values?
        //
        // YYYY (a) to YYYY (b) is easy to assume it loops on a year-basis.
        /*
        elseif (strstr($this_date, "/") && (strlen($this_date) == 9) && (substr($this_date, 4,1) == "/")) {
          $parts = explode("/", $this_date, 2);
          if (count($parts) == 2 && is_numeric(0 + $parts[0]) && is_numeric(0 + $parts[1])) {
            $date_from = ($parts[0] < $parts[1]) ? $parts[0] : $parts[1];
            $date_to = ($parts[0] < $parts[1]) ? $parts[1] : $parts[0];
            for ($d = $date_from; $d < $date_to; $d++) {
              $dates[$d . '-01'] = $d . '-01';
            }
          }
        }
        elseif (strstr($this_date, "/") && (strlen($this_date) == 15) && (substr($this_date, 7,1) == "/")) {
          $parts = explode("/", $this_date, 2);
          if (count($parts) == 2 && is_numeric(0 + $parts[0]) && is_numeric(0 + $parts[1])) {
            $date_from = ($parts[0] < $parts[1]) ? $parts[0] : $parts[1];
            $date_to = ($parts[0] < $parts[1]) ? $parts[1] : $parts[0];
            for ($d = $date_from; $d < $date_to; $d++) {
              $dates[$d] = $d;
            }
          }
        }
        */
        elseif (strstr($this_date, "/") && (strlen($this_date) == 21) && (substr($this_date, 10,1) == "/")) {
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
          $iso = strtotime($this_date);
          $date = $this_date;
        }
        $dates[$date] = $date;
      }
    }
    return $dates;
  }

}
