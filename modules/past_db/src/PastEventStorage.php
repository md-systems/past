<?php

/**
 * @file
 * Definition of Drupal\past_db\PastEventStorage.
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
class PastEventStorage extends ContentEntityDatabaseStorage {

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
    db_delete('past_event_argument')
      ->condition('event_id', array_keys($entities))
      ->execute();
  }

  /**
   * {@inheritdoc}
   */
  protected function doSave($id, EntityInterface $entity) {
    parent::doSave($id, $entity);
    /** @var PastEvent $entity */

    // Save the arguments.
    foreach ($entity->getArguments() as $argument) {
      /** @var PastEventArgument $argument */
      $argument->ensureType();
      $insert = db_insert('past_event_argument')
        ->fields(array(
          'event_id' => $entity->id(),
          'name' => $argument->getKey(),
          'type' => $argument->getType(),
          'raw' => $argument->getRaw(),
        ));
      try {
        $argument_id = $insert->execute();
      }
      catch (\Exception $e) {
        watchdog_exception('past', $e);
      }

      // Save the argument data.
      if ($argument->getOriginalData()) {
        $this->insertData($argument_id, $argument->getOriginalData());
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
   * Inserts argument data in the database.
   *
   * @param int $argument_id
   *   Id of the argument that the data belongs to.
   * @param mixed $data
   *   The argument data.
   * @param int $parent_data_id
   *   (optional) Id of the parent data, if data is nested.
   */
  protected function insertData($argument_id, $data, $parent_data_id = 0) {
    $insert = db_insert('past_event_data')
      ->fields(array('argument_id', 'parent_data_id', 'type', 'name', 'value', 'serialized'));
    if (is_array($data) || is_object($data)) {
      foreach ($data as $name => $value) {

        // @todo: Allow to make this configurable. Ignore NULL.
        if ($value === NULL) {
          continue;
        }

        $insert->values(array(
          'argument_id' => $argument_id,
          'parent_data_id' => $parent_data_id,
          'type' => is_object($value) ? get_class($value) : gettype($value),
          'name' => $name,
          // @todo: Support recursive inserts.
          'value' => is_scalar($value) ? $value : serialize($value),
          'serialized' => is_scalar($value) ? 0 : 1,
        ));
      }
    } else {
      $insert->values(array(
        'argument_id' => $argument_id,
        'parent_data_id' => 0,
        'type' => gettype($data),
        'name' => '',
        'value' => $data,
        'serialized' => 0,
      ));
    }
    try {
      $insert->execute();
    }
    catch (\Exception $e) {
      watchdog_exception('past', $e);
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
