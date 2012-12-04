<?php

/**
 * @file
 * Contains the entity classes for Past DB.
 */
class PastEvent extends Entity implements PastEventInterface {

  public $module;
  public $machine_name;
  public $message;
  public $severity;
  public $timestamp;
  public $parent_event_id;

  protected $arguments;

  public function addArgument($key, $data, array $options = array()) {
    if (!is_array($this->arguments)) {
      $this->arguments = array();
    }

    // If it is an object, clone it to avoid changing the original and log it
    // at the current state.
    if (is_object($data)) {
      $data = clone $data;
    }

    // Special support for exceptions, convert them to something that can be
    // stored.
    if (isset($data) && $data instanceof Exception) {
      $data = _drupal_decode_exception($data) + array('backtrace' => $data->getTraceAsString());
    }

    // Remove values which were explicitly added to the exclude filter.
    if (!empty($options['exclude'])) {
      foreach ($options['exclude'] as $exclude) {
        if (is_array($data)) {
          unset($data[$exclude]);
        }
        elseif (is_object($data)) {
          unset($data->$exclude);
        }
      }
      unset($options['exclude']);
    }

    $this->arguments[$key] = entity_create('past_event_argument', array('name' => $key, 'original_data' => $data) + $options);
    return $this->arguments[$key];
  }

  protected function loadArguments() {
    if (!is_array($this->arguments)) {
      $this->arguments = array();
      foreach (entity_load_multiple_by_name('past_event_argument', FALSE, array('event_id' => $this->event_id)) as $argument) {
        $this->arguments[$argument->name] = $argument;
      }
    }
  }

  public function getArgument($key) {
    $this->loadArguments();
    return isset($this->arguments[$key]) ? $this->arguments[$key] : NULL;
  }

  public function getArguments() {
    $this->loadArguments();
    return $this->arguments;
  }

  public function addException(Exception $exception, array $options = array()) {
    $this->addArgument('exception', $exception, $options);
  }

  public function getMachineName() {
    return $this->machine_name;
  }

  public function getModule() {
    return $this->module;
  }

  public function getSeverity() {
    return $this->severity;
  }

  public function getMessage() {
    return $this->message;
  }

  public function getTimestamp() {
    return $this->timestamp;
  }

  public function id() {
    return $this->event_id;
  }

  public function setParentEventId($event_id) {
    $this->parent_event_id = $event_id;
  }

  public function setSeverity($severity) {
    $this->severity = $severity;
  }

  public function setMessage($message) {
    $this->message = $message;
  }

  public function setTimestamp($timestamp) {
    $this->timestamp = $timestamp;
  }

  public function setMachineName($machine_name) {
    $this->machine_name = $machine_name;
  }

  public function setModule($module) {
    $this->module = $module;
  }

}

/**
 * Contains the entity classes for Past DB.
 */
class PastEventArgument extends Entity implements PastEventArgumentInterface {

  public $argument_id;
  public $event_id;
  protected $original_data;
  public $name;
  public $type;
  public $raw;

  public function getData() {
    $return = NULL;
    $result = db_query('SELECT * FROM {past_event_data} WHERE argument_id = :argument_id', array(':argument_id' => $this->argument_id));
    if ($this->type == 'array') {
      $return = array();
      foreach ($result as $row) {
        $return[$row->name] = $row->serialized ? unserialize($row->value) : $row->value;
      }
    }
    elseif (!in_array($this->type, array('integer', 'string', 'float', 'boolean'))) {
      $return = new stdClass();
      foreach ($result as $row) {
        $return->{$row->name} = $row->serialized ? unserialize($row->value) : $row->value;
      }
    }
    else {
      if ($row = $result->fetchObject()) {
        $return = $row->value;
      }
    }
    return $return;
  }

  public function getKey() {
    return $this->name;
  }

  public function getRaw() {
    return $this->raw;
  }

  public function getType() {
    return $this->type;
  }

  public function setRaw($data, $json_encode = TRUE) {

  }

  public function getOriginalData() {
    return $this->original_data;
  }

  public function ensureType() {
    if (isset($this->original_data)) {
      if (is_object($this->original_data)) {
        $this->type = get_class($this->original_data);
      }
      else {
        $this->type = gettype($this->original_data);
      }
    }
  }
}
