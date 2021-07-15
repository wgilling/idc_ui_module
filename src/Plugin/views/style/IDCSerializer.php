<?php

namespace Drupal\idc_ui_module\Plugin\views\style;

use Drupal\Core\Form\FormStateInterface;
use Drupal\facets\FacetManager\DefaultFacetManager;
use Drupal\rest\Plugin\views\style\Serializer;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * The style plugin for serialized output formats.
 *
 * This module, and the iDC Serializer that is part of this module, incorporates, modifies and otherwise combines parts of the
 * [Facets](https://www.drupal.org/project/facets) and [Pager Serializer](https://www.drupal.org/project/pager_serializer) projects.
 * The date these modifications were first incorporated into this module is March 9, 2021.
 *
 * @ingroup views_style_plugins
 *
 * @ViewsStyle(
 *   id = "idc_serializer",
 *   title = @Translation("iDC serializer"),
 *   help = @Translation("Serializes views row data using the Serializer component."),
 *   display_types = {"data"}
 * )
 */
class IdcSerializer extends Serializer {
  /**
   * Config settings.
   *
   * @var string
   */
  const SETTINGS = 'pager_serializer.settings';

  /**
   * Pager None class.
   *
   * @var string
   */
  const PAGER_NONE = 'Drupal\views\Plugin\views\pager\None';

  /**
   * Pager Some class.
   *
   * @var string
   */
  const PAGER_SOME = 'Drupal\views\Plugin\views\pager\Some';

  /**
   * Tha facet manager.
   *
   * @var \Drupal\facets\FacetManager\DefaultFacetManager
   */
  protected $facetsManager;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('serializer'),
      $container->getParameter('serializer.formats'),
      $container->getParameter('serializer.format_providers'),
      $container->get('facets.manager')
    );
  }

  /**
   * Constructs a FacetsSerializer object.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, SerializerInterface $serializer, array $serializer_formats, array $serializer_format_providers, DefaultFacetManager $facets_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $serializer, $serializer_formats, $serializer_format_providers);
    $this->facetsManager = $facets_manager;
  }

  /**
   * {@inheritdoc}
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['show_facets'] = ['default' => TRUE];

    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    parent::buildOptionsForm($form, $form_state);

    $form['show_facets'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Show facets in the output'),
      '#default_value' => $this->options['show_facets'],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * {@inheritdoc}
   */
  public function render() {
    $rows = [];
    $result = [];
    $config = \Drupal::config(static::SETTINGS);
    $rows_label = $config->get('rows_label');
    $use_pager = $config->get('pager_object_enabled');
    // If the Data Entity row plugin is used, this will be an array of entities
    // which will pass through Serializer to one of the registered Normalizers,
    // which will transform it to arrays/scalars. If the Data field row plugin
    // is used, $rows will not contain objects and will pass directly to the
    // Encoder.

    foreach ($this->view->result as $row_index => $row) {
      // Keep track of the current rendered row, like every style plugin has to
      // do.
      // @see \Drupal\views\Plugin\views\style\StylePluginBase::renderFields
      $this->view->row_index = $row_index;
      $rows[] = $this->view->rowPlugin->render($row);
    }
    unset($this->view->row_index);

    // Get the content type configured in the display or fallback to the
    // default.
    if ((empty($this->view->live_preview))) {
      $content_type = $this->displayHandler->getContentType();
    }
    else {
      $content_type = !empty($this->options['formats']) ? reset($this->options['formats']) : 'json';
    }

    // Processing facets.
    $facetsource_id = "search_api:views_rest__{$this->view->id()}__{$this->view->getDisplay()->display['id']}";
    $facets = $this->facetsManager->getFacetsByFacetSourceId($facetsource_id);
    $this->facetsManager->updateResults($facetsource_id);

    $processed_facets = [];
    $facets_metadata = [];
    foreach ($facets as $facet) {
      $processed_facets[] = $this->facetsManager->build($facet);
      $facets_metadata[$facet->id()] = array(
        'label' => $facet->label(),
        'weight' => $facet->getWeight(),
        'field_id' => $facet->getFieldIdentifier(),
        'url_alias' => $facet->getUrlAlias()
      );
    }
    uasort($facets_metadata, function($a, $b) {
      return $a['weight'] > $b['weight'];
    });

    $pagination = $this->pagination($config, $rows);
    if ($use_pager) {
      $pager_label = $config->get('pager_label');
      $result[$rows_label] = $rows;
      $result[$pager_label] = $pagination;
    }
    else {
      $result = $pagination;
      $result[$rows_label] = $rows;
    }

    $result['facets'] = array_values($processed_facets);
    $result['facets_metadata'] = $facets_metadata;

    return $this->serializer->serialize($result, $content_type, ['views_style_plugin' => $this]);
  }

  /**
   * {@inheritdoc}
   */
  protected function pagination($config, $rows) {

    $pagination = [];
    $current_page = 0;
    $items_per_page = 0;
    $total_items = 0;
    $total_pages = 1;
    $class = NULL;

    $pager = $this->view->pager;

    if ($pager) {
      $items_per_page = $pager->getItemsPerPage();
      $total_items = $pager->getTotalItems();
      $class = get_class($pager);
    }

    if (method_exists($pager, 'getPagerTotal')) {
      $total_pages = $pager->getPagerTotal();
    }
    if (method_exists($pager, 'getCurrentPage')) {
      $current_page = $pager->getCurrentPage();
    }
    if ($class == static::PAGER_NONE) {
      $items_per_page = $total_items;
    }
    elseif ($class == static::PAGER_SOME) {
      $total_items = count($rows);
    }

    if ($config->get('current_page_enabled')) {
      $current_page_label = $config->get('current_page_label');
      $pagination[$current_page_label] = $current_page;
    }
    if ($config->get('total_items_enabled')) {
      $total_items_label = $config->get('total_items_label');
      $pagination[$total_items_label] = $total_items;
    }
    if ($config->get('total_pages_enabled')) {
      $total_pages_label = $config->get('total_pages_label');
      $pagination[$total_pages_label] = $total_pages;
    }
    if ($config->get('items_per_page_enabled')) {
      $items_per_page_label = $config->get('items_per_page_label');
      $pagination[$items_per_page_label] = $items_per_page;
    }

    return $pagination;
  }
}
