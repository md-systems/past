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
      $this->insert->execute();
    }
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
