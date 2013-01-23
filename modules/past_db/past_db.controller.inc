<?php

/**
 * @file
 * Contains the controller classes.
 */

/**
 * Controller class for the past event entity.
 */
class PastEventController extends EntityAPIController {

  /**
   *
   * @var InsertQuery
   */
  protected $insert;

  public function create(array $values = array()) {
    $entity = parent::create($values);

    if (empty($entity->timestamp)) {
      $entity->timestamp = REQUEST_TIME;
    }
    if (empty($entity->severity)) {
      $entity->severity = PAST_SEVERITY_INFO;
    }
    return $entity;
  }

  public function save($entity, DatabaseTransaction $transaction = NULL) {
    parent::save($entity, $transaction);

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

    // If running in a WebTestCase environment, propagate the log into simpletest log.
  }

  public function delete($ids, \DatabaseTransaction $transaction = NULL) {
    // Delete the arguments first.
    $query = new EntityFieldQuery();
    $query->entityCondition('entity_type', 'past_event_argument');
    $query->propertyCondition('event_id', $ids);
    $result = $query->execute();
    if ($result) {
      entity_delete_multiple('past_event_argument', array_keys($result['past_event_argument']));
    }

    parent::delete($ids, $transaction);
  }

}

/**
 * Controller class for the past event argument entity.
 */
class PastEventArgumentController extends EntityAPIController {

  public function create(array $values = array()) {

    $entity = parent::create($values);

    return $entity;
  }

  public function save($entity, DatabaseTransaction $transaction = NULL) {

    // Make sure the type is set.
    $entity->ensureType();

    parent::save($entity, $transaction);

    // Save data.
    if ($data = $entity->getOriginalData()) {
      $this->insert = db_insert('past_event_data')
        ->fields(array('argument_id', 'parent_data_id', 'type', 'name', 'value', 'serialized'));
      $this->normalizeData($entity->argument_id, $data);
      try {
        $this->insert->execute();
      }
      catch (Exception $e) {
        watchdog_exception('past', $e); 
      }
    }
  }

  public function delete($ids, \DatabaseTransaction $transaction = NULL) {
    // Delete the data first.
    db_delete('past_event_data')
      ->condition('argument_id', $ids)
      ->execute();

    parent::delete($ids, $transaction);
  }

  /**
   *
   * @param type $data
   */
  protected function normalizeData($argument_id, $data, $parent_data_id = 0) {
    if (is_array($data) || is_object($data)) {
      foreach ($data as $name => $value) {

        // @todo: Allow to make this configurable. Ignore NULL.
        if ($value === NULL) {
          continue;
        }

        $this->insert->values(array(
          'argument_id' => $argument_id,
          'parent_data_id' => $parent_data_id,
          'type' => is_object($value) ? get_class($value) : gettype($value),
          'name' => $name,
          // @todo: Support recursive inserts.
          'value' => is_scalar($value) ? $value : serialize($value),
          'serialized' => is_scalar($value) ? 0 : 1,
        ));
      }
    }
    else {
      $this->insert->values(array(
        'argument_id' => $argument_id,
        'parent_data_id' => 0,
        'type' => gettype($data),
        'name' => '',
        'value' => $data,
        'serialized' => 0,
      ));
    }
  }

}


/**
 * Controller class for the Entity Metadata.
 */
class PastDBEventMetadataController extends EntityDefaultMetadataController {
  protected static $modules = array();

  /**
   * Overrides EntityDefaultMetadataController::entityPropertyInfo().
   *
   * @see EntityDefaultMetadataController::entityPropertyInfo()
   * @return array|void
   */
  public function entityPropertyInfo() {
    // Loading property information and make em better usable in here.
    $info = parent::entityPropertyInfo();
    $prop = &$info[$this->type]['properties'];

    // The timestamp should be rendered/shown as a date.
    $prop['timestamp']['type'] = 'date';

    // The severity should be a translated options list.
    // We could also call the method 'past_event_severities' directly, but I prefer the OOP-Way for all and not a mix up.
    $prop['severity']['options list'] = array(get_class($this), 'severityOptionsList');

    return $info;
  }

  /**
   * An OptionsList callback to return all Severity levels.
   * @return array
   */
  public function severityOptionsList() {
    return past_event_severities();
  }

}

/**
 * Implements the default UI Controller.
 */
class PastDBEventUIController extends EntityDefaultUIController {

  /**
   * Overrides EntityDefaultUIController::hook_menu().
   * @return array
   */
  public function hook_menu() {
    $id_count = count(explode('/', $this->path));
    $plural_label = isset($this->entityInfo['plural label']) ? $this->entityInfo['plural label'] : $this->entityInfo['label'] . 's';
    $wildcard = isset($this->entityInfo['admin ui']['menu wildcard']) ? $this->entityInfo['admin ui']['menu wildcard'] : '%entity_object';
    $items[$this->path . '/' . $wildcard] = array(
      'title callback' => 'entity_label',
      'title arguments' => array($this->entityType, $id_count),
      'page callback' => 'past_db_event_view',
      'page arguments' => array($id_count),
      'load arguments' => array($this->entityType),
      'access callback' => 'entity_access',
      'access arguments' => array('view', $this->entityType, $id_count),
    );
    return $items;
  }
}
