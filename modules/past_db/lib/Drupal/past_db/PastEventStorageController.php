<?php

/**
 * @file
 * Definition of Drupal\taxonomy\TermStorageController.
 */

namespace Drupal\past_db;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\Query\QueryInterface;
use Drupal\Core\Entity\DatabaseStorageControllerNG;
use Drupal;

/**
 * Defines a Controller class for past events.
 */
class PastEventStorageController extends DatabaseStorageControllerNG {

  /**
   * Overrides Drupal\Core\Entity\DatabaseStorageController::create().
   */
  public function create(array $values) {
    $entity = parent::create($values);

    if (empty($entity->type)) {
      $entity->type = 'past_event';
    }
    if (empty($entity->timestamp)) {
      $entity->timestamp = REQUEST_TIME;
    }
    if (empty($entity->severity)) {
      $entity->severity = PAST_SEVERITY_INFO;
    }
    if (empty($entity->uid) && !empty($GLOBALS['user'])) {
      $entity->uid = $GLOBALS['user']->uid;
    }
    return $entity;
  }

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
   * Overrides Drupal\Core\Entity\DatabaseStorageController::postSave().
   */
  protected function postSave(EntityInterface $entity, $update) {
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

  /**
   * Overrides \Drupal\Core\Entity\DatabaseStorageControllerNG::baseFieldDefintions().
   */
  public function baseFieldDefinitions() {
    $properties['event_id'] = array(
      'label' => t('Event ID'),
      'description' => t('The event ID.'),
      'type' => 'integer_field',
      'read-only' => TRUE,
    );
    $properties['uuid'] = array(
      'label' => t('UUID'),
      'description' => t('The event UUID.'),
      'type' => 'string_field',
      'read-only' => TRUE,
    );
    $properties['module'] = array(
      'label' => t('Module'),
      'description' => t('The module which logged the event.'),
      'type' => 'string_field',
      'read-only' => TRUE,
    );
    $properties['machine_name'] = array(
      'label' => t('Machine name'),
      'description' => t('The machine name of the event.'),
      'type' => 'string_field',
      'read-only' => TRUE,
    );
    $properties['type'] = array(
      'label' => t('Type'),
      'description' => t('The node type.'),
      'type' => 'string_field',
      'read-only' => TRUE,
    );
    $properties['message'] = array(
      'label' => t('Message'),
      'description' => t('The event log message'),
      'type' => 'string_field',
    );
    $properties['severity'] = array(
      'label' => t('Severity'),
      'description' => t('The event severity.'),
      'type' => 'integer_field',
    );
    $properties['timestamp'] = array(
      'label' => t('Timestamp'),
      'description' => t('The event timestamp.'),
      'type' => 'integer_field',
    );
    $properties['parent_event_id'] = array(
      'label' => t('Parent event ID'),
      'description' => t('The parent event ID.'),
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'past_event'),
    );
    $properties['uid'] = array(
      'label' => t('User ID'),
      'description' => t('The user ID.'),
      'type' => 'entity_reference_field',
      'settings' => array('target_type' => 'user'),
    );
    return $properties;
  }
}
