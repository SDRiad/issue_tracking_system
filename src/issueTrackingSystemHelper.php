<?php

namespace Drupal\issue_tracking_system;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\taxonomy\VocabularyStorageInterface;
use Drupal\taxonomy\TermStorageInterface;

/**
 * Class issue_tracking_system_helper
 * @package Drupal\issue_tracking_system\Services
 */
class issueTrackingSystemHelper {

  /**
   * @var EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var TermStorageInterface
   */
  protected TermStorageInterface $termStorage;

  /**
   * @var VocabularyStorageInterface
   */
  protected VocabularyStorageInterface $vocabularyStorage;

  /**
   * @param EntityTypeManagerInterface $entity_type_manager
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->termStorage =  $this->entityTypeManager->getStorage('taxonomy_term');
    $this->vocabularyStorage =  $this->entityTypeManager->getStorage('taxonomy_vocabulary');
  }

  public function createTaxonomyVocabulary($vocabularyInfo){

    $vocabularies = $this->vocabularyStorage->loadMultiple();

    if (!$this->isVocabularyExist($vocabularyInfo["vid"])) {
      $vocabulary = $this->vocabularyStorage->create(array(
        'vid' => $vocabularyInfo["vid"],
        'name' => $vocabularyInfo["name"],
        'description' => $vocabularyInfo["description"],
      ))->save();

      foreach ($vocabularyInfo["terms"] as $termInfo) {
        $this->createTaxonomyTerm($vocabularyInfo["vid"], $vocabularyInfo["name"], $termInfo);
      }

      \Drupal::messenger()->addMessage($vocabularyInfo["name"] . ' vocabulary has been created successfully');
    }
    else {
      \Drupal::messenger()->addWarning($vocabularyInfo["name"] . ' vocabulary already exits');
    }
  }

  public function createTaxonomyTerm($vid, $vocabularyName, $termInfo){

    if (!$this->isTermExist($vid, $termInfo["name"])) {
      $term = $this->termStorage->create(array(
        'vid' => $vid,
        'name' => $termInfo["name"],
        'description' => $termInfo["description"],
        'path' => "/" . str_replace(' ','-',strtolower($vocabularyName)) . "/" . str_replace(' ','-',strtolower($termInfo["name"])),
      ))->save();
    }
    else {
      \Drupal::messenger()->addWarning($termInfo["name"] . ' term already exits');
    }
  }

  function deleteVocabulary($vid) {
    if ($this->isVocabularyExist($vid)) {
      $vocabulary = $this->vocabularyStorage->load($vid);
      if ($vocabulary) {
        $vocabulary->delete();
        \Drupal::messenger()->addMessage($vocabulary->label() . ' vocabulary has been deleted successfully');
      }
    }
  }
  public function isTermExist($vid, $termName){

    $terms = $this->termStorage->loadTree($vid);
    foreach ($terms as $term) {
      if ($term->name == $termName) {
        return TRUE;
      }
    }
    return FALSE;
  }

  public function isVocabularyExist($vid){

    $vocabularies = $this->vocabularyStorage->loadMultiple();

    if (isset($vocabularies[$vid])){
      return TRUE;
    }
    return FALSE;
  }

}
