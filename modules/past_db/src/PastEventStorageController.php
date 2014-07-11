<?php

/**
 * @file
 * Definition of Drupal\taxonomy\TermStorageController.
 */

namespace Drupal\past_db;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal;

/**
 * Defines a Controller class for past events.
 */
class PastEventStorageController extends ContentEntityDatabaseStorage {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::buildPropertyQuery().
   */
  protected function buildPropertyQuery(QueryInterface $entity_query, array $values) {
    // @todo - any query customisations?
    parent::buildPropertyQuery($entity_query, $values);
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::postDelete().
   */
  protected function postDelete($entities) {
    $query = Drupal::entityQuery('past_event_argument');
    $query->andConditionGroup()->condition('event_id', array_keys($entities));
    $result = $query->execute();
    if ($result) {
      entity_delete_multiple('past_event_argument', array_keys($result['past_event_argument']));
    }
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    parent::doSave($id, $entity);
    // Save the arguments.
    foreach ($entity->getArguments() as $argument) {
      $argument->event_id = $entity->event_id;
      $argument->save();
    }

    // Update child events to use the parent_event_id.
    if ($child_events = $entity->getChildEvents()) {
      db_update('past_event')
          ->fields(array(
            'parent_event_id' => $entity->event_id
          ))
          ->condition('event_id', $child_events)
          ->execute();
    }
  }

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::resetCache().
   */
  public function resetCache(array $ids = NULL) {
    // @todo - any cache reset?
    parent::resetCache($ids);
  }
}
