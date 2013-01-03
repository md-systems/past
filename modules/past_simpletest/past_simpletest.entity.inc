<?php

/**
 * @file
 * Contains the entity classes for Past Simpletest backend.
 */
/**
 * The main Event class for the Past Simpletest backend.
 */
class PastEvent extends Entity implements PastEventInterface {

  public $module;
  public $machine_name;
  public $message;
  public $severity;
  public $timestamp;
  public $parent_event_id;

  protected $arguments;
  protected $child_events = array();

  public function addArgument($key, $data, array $options = array()) {
    if (!is_array($this->arguments)) {
      $this->arguments = array();
    }

    // If it is an object, clone it to avoid changing the original and log it
    // at the current state. Except when it can't, like e.g. exceptions.
    if (is_object($data) && !($data instanceof Exception)) {
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

  public function addChildEvent($event_id) {
    $this->child_events[] = $event_id;
  }

  public function getChildEvents() {
    return $this->child_events;
  }
}

/**
 * The Arguments Entity class for the Past Simpletest backend.
 */
class PastEventArgument extends Entity implements PastEventArgumentInterface {

  public $argument_id;
  public $event_id;
  protected $original_data;
  public $name;
  public $type;
  public $raw;

  public function getData() {
    // The Event is not stored, so we cannot load any arguments.
    return NULL;
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
    $this->raw = $json_encode ? drupal_json_encode($data) : $data;
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

  public function defaultLabel() {
    return $this->getKey();
  }
}
