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
   *
   * @param type $module
   * @param type $machine_name
   */
  public function __construct($module, $machine_name);

  /**
   *
   * @param type $text
   *
   * @return PastEventInterface
   */
  public function setText($text);

  /**
   *
   * @param type $text
   *
   * @return string
   */
  public function getText($text);

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
  public function setTimestamp();

  /**
   * @return integer
   */
  public function getTimestamp();

  public function getMachineName();

  public function getModule();
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
