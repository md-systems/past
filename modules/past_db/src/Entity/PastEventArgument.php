<?php

namespace Drupal\past_db\Entity;

use Drupal\Core\Database\Query\Insert;
use \Drupal\past\Entity\PastEventArgumentInterface;

/**
 * An argument for an event.
 */
class PastEventArgument implements PastEventArgumentInterface {

  public $argument_id;
  public $event_id;
  protected $original_data;
  public $name;
  public $type;
  public $raw;

  /**
   * Creates a new argument.
   *
   * @param $name
   * @param $original_data
   * @param array $options
   *   An associative array containing any number of the following properties:
   *     - event_id
   *     - type
   *     - raw
   */
  public function __construct($name, $original_data, array $options = array()) {
    $this->name = $name;
    $this->original_data = $original_data;
    foreach ($options as $key => $value) {
      $this->$key = $value;
    }
  }

  /**
   * {@inheritdoc}
   */
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

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    return $this->name;
  }

  /**
   * {@inheritdoc}
   */
  public function getRaw() {
    return $this->raw;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return $this->type;
  }

  /**
   * {@inheritdoc}
   */
  public function setRaw($data, $json_encode = TRUE) {

  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalData() {
    return $this->original_data;
  }

  /**
   * {@inheritdoc}
   */
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

  /**
   * Defines the argument entity label.
   *
   * @return string
   *   Entity label.
   */
  public function defaultLabel() {
    return $this->getKey();
  }

  /**
   * {@inheritdoc}
   */
  public function normalizeData(Insert $insert, $data, $parent_data_id = 0)
  {
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
  }
}
