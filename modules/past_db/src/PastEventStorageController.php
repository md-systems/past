<?php

/**
 * @file
 * Definition of Drupal\taxonomy\TermStorageController.
 */

namespace Drupal\past_db;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\ContentEntityDatabaseStorage;
use Drupal\past_db\Entity\PastEvent;
use Drupal\past_db\Entity\PastEventArgument;

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
   * {@inheritdoc}
   */
  public function getSchema() {
    $schema = parent::getSchema();
    $schema['past_event']['indexes']['severity'] = array('severity');
    $schema['past_event']['indexes']['module'] = array('module');
    $schema['past_event']['indexes']['machine_name'] = array('machine_name');
    $schema['past_event']['indexes']['session_id'] = array('session_id');
    $schema['past_event']['indexes']['type'] = array('type');
    return $schema;
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

    /** @var PastEvent $entity */
    foreach ($entity->getArguments() as $argument) {
      /** @var PastEventArgument $argument */
      $argument->ensureType();

      $data = $argument->getOriginalData();
      if ($data) {
        $insert = db_insert('past_event_argument')
          ->fields(array(
            'event_id' => $entity->id(),
            'name' => $argument->getKey(),
            'type' => $argument->getType(),
            'raw' => $argument->getRaw(),
          ));
        $argument->normalizeData($insert, $data);
        try {
          $insert->execute();
        }
        catch (\Exception $e) {
          watchdog_exception('past', $e);
        }
      }
    }

    // Update child events to use the parent_event_id.
    if ($child_events = $entity->getChildEvents()) {
      db_update('past_event')
          ->fields(array(
            'parent_event_id' => $entity->id(),
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
