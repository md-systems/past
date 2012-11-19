<?php

/**
 * @file
 * Contains the controller classes.
 */

/**
 * Controller class for the past event entity.
 */
class PastEventController extends EntityAPIController {

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

  public function save($entity, DatabaseTransaction $transaction = NULL) {

    // Make sure the type is set.
    $entity->ensureType();

    parent::save($entity, $transaction);

    // Save data.
    if ($data = $entity->getOriginalData()) {
      $insert = db_insert('past_event_data')
        ->fields(array('argument_id', 'type', 'name', 'value'));
      if (is_array($data) || is_object($data)) {
        foreach ($data as $name => $value) {
          $insert->values(array(
            'argument_id' => $entity->argument_id,
            'type' => gettype($value),
            'name' => $name,
            'value' => is_scalar($value) ? $value : NULl,
          ));
        }
      }
      else {
        $insert->values(array(
          'argument_id' => $entity->argument_id,
          'type' => gettype($data),
          'name' => '',
          'value' => $data,
        ));
      }
    }
    $insert->execute();
  }

}
