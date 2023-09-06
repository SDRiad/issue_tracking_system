<?php

namespace Drupal\issue_tracking_system\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a menu term block.
 *
 * @Block(
 *   id = "issue_tracking_system_block",
 *   admin_label = @Translation("Issue tracking system Block"),
 *   category = @Translation("Issue tracking system Block"),
 * )
 */
class IssueTrackingSystemBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;


  /**
   * Route match service.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected RouteMatchInterface $routeMatch;

  /**
   * The language manager service.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new ListPerfumeByPerfumerBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration, i.e. an array with configuration values keyed
   *   by configuration option name. The special key 'context' may be used to
   *   initialize the defined contexts by setting it to an array of context
   *   values keyed by context names.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Routing\RouteMatchInterface $routeMatch
   *   The route match service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityTypeManagerInterface $entity_type_manager,
    RouteMatchInterface $routeMatch,
    LanguageManagerInterface $language_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityTypeManager = $entity_type_manager;
    $this->routeMatch = $routeMatch;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.manager'),
      $container->get('current_route_match'),
      $container->get('language_manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build(){

    return [
      '#theme' => 'issue_tracking_system_block',
      '#nodes' => $this->loadNodes(),
    ];
  }

  public function loadNodes() {
    $nodes_id = $this->entityTypeManager
      ->getStorage('node')->getQuery()
      ->condition('type', 'issue_tracking_system_type')
      ->sort('due_date', 'ASC')
      ->range(0,3)
      ->execute();

    $languageId = $this->languageManager->getCurrentLanguage()->getId();


    $results = [];

    if (!empty($nodes_id)) {
      foreach ($nodes_id as $id) {
        $node = $this->entityTypeManager->getStorage('node')->load($id);
        if ($node->hasTranslation($languageId)) {
          $node = $node->getTranslation($languageId);
        }
        $node_name = $node->label();
        $node_desc = $node->get('body')->getValue();
        $node_reporter = $node->get('reporter')->getValue();
        $node_assignee = $node->get('assignee')->getValue();
        $node_list_watchers = $node->get('list_watchers')->getValue();
        $node_issue_type = $node->get('issue_type')->getValue();
        $node_issue_priority = $node->get('issue_priority')->getValue();
        $node_issue_status = $node->get('issue_status')->getValue();
        $node_due_date = $node->get('due_date')->getValue();
        $result = array(
          'name' => $node_name,
          'desc' => $node_desc[0]['value'],
          'reporter' => $this->loadUser($node_reporter[0]['target_id']),
          'assignee' => $this->loadUser($node_assignee[0]['target_id']),
          'list_watchers' => $this->loadMultipleUser($node_list_watchers),
          'issue_type' => $this->loadTaxonomy($node_issue_type[0]['target_id']),
          'issue_priority' => $this->loadTaxonomy($node_issue_priority[0]['target_id']),
          'issue_status' => $this->loadTaxonomy($node_issue_status[0]['target_id']),
          'due_date' => $node_due_date[0]['value'],
        );
        $results[] = $result;

      }
    }
    return $results;
  }

  public function loadUser($uid) {
    $user = $this->entityTypeManager
      ->getStorage('user')->load($uid);

    $languageId = $this->languageManager->getCurrentLanguage()->getId();

    if ($user->hasTranslation($languageId)) {
      $user = $user->getTranslation($languageId);
    }

    $user_name = $user->get('name')->getValue();
    return $user_name[0]['value'];
  }

  public function loadMultipleUser($uids) {
    foreach ($uids as $uid) {
      $result[] = $this->loadUser($uid['target_id']);
    }
    return $result;
  }

  public function loadTaxonomy($tid) {
    $term = $this->entityTypeManager
      ->getStorage('taxonomy_term')->load($tid);

    $languageId = $this->languageManager->getCurrentLanguage()->getId();

    if ($term->hasTranslation($languageId)) {
      $term = $term->getTranslation($languageId);
    }

    $term_name = $term->get('name')->getValue();
    return $term_name[0]['value'];
  }

}
