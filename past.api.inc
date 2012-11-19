<?php

/**
 * @file
 * Contains the interface definition.
 */

/**
 * Represents a logged event.
 */
interface PastEventInterface {

  /**
   * Returns the id of the event, if saved.
   */
  public function id();

  /**
   *
   * @param type $module
   *
   * @return PastEventInterface
   */
  public function setModule($module);

  /**
   *
   * @param type $machine_name
   *
   * @return PastEventInterface
   */
  public function setMachineName($machine_name);

  /**
   *
   * @param type $message
   *
   * @return PastEventInterface
   */
  public function setMessage($message);

  /**
   *
   * @return string
   */
  public function getMessage();

  /**
   * Returns the severity of this event.
   *
   * @return integer
   */
  public function getSeverity();

  /**
   *
   * @param type $severity
   *
   * @return PastEventInterface
   */
  public function setSeverity($severity);

  /**
   *
   * @param type $key
   * @param type $data
   * @param array $options
   *
   * @return PastEventInterface
   */
  public function addArgument($key, $data, array $options = array());

  /**
   * @return array
   */
  public function getArguments();

  /**
   *
   * @param type $key
   *
   * @return PastEventArgumentInterface
   */
  public function getArgument($key);

  /**
   *
   * @param type $event_id
   *
   * @return PastEventInterface
   */
  public function setParentEventId($event_id);

  /**
   * @return PastEventInterface
   */
  public function setTimestamp($timestamp);

  /**
   * @return integer
   */
  public function getTimestamp();

  /**
   * Returns the machine name of this event.
   */
  public function getMachineName();

  /**
   * Returns the module that created this event.
   */
  public function getModule();

  /**
   * Saves the event.
   *
   * @return integer
   *   The event id of the event.
   */
  public function save();
}

/**
 * Represents
 */
interface PastEventArgumentInterface {

  public function getKey();

  public function getData();

  public function getType();

  public function getRaw();

  public function setRaw();
}
