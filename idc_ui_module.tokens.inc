<?php

/**
 * Implements hook_token_info().
 */
function idc_ui_module_token_info() {
  $info = [];
  $info['types']['idc_token_group'] = array(
    'name' => t('IDC tokens'),
    'description' => t('IDC custom tokens'),
  );
  $info['tokens']['idc_token_group']['repo_item_parent'] = array(
    'name' => t('Repository item parent collection'),
    'description' => t('Get the parent collection of the repository item on this page.'),
  );

  return $info;
}

/**
 * Implements hook_tokens().
 */
function idc_ui_module_tokens($type, $tokens, array $data, array $options, \Drupal\Core\Render\BubbleableMetadata $bubbleable_metadata) {
  $replacements = [];

  if ($type == 'idc_token_group') {
    foreach ($tokens as $name => $original) {
      switch ($name) {
        case 'repo_item_parent':
          $parents = \Drupal::routeMatch()
            ->getParameter('node')
            ->get('field_member_of')
            ->referencedEntities();

          if (!empty($parents) && count($parents) > 0) {
            $replacements[$original] = array_values($parents)[0]->id();
          }

          break;
      }
    }
  }

  return $replacements;
}
