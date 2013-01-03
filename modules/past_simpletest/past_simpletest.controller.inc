<?php

/**
 * @file
 * Contains the controller classes for the Past simpletest backend.
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
    // Save the arguments.
    foreach ($entity->getArguments() as $argument) {
      $argument->event_id = $entity->event_id;
      $argument->save();
    }

    // Debug outputs are shown on the Simpletest frontend.
    debug($entity);
  }

  public function delete($ids, \DatabaseTransaction $transaction = NULL) {
    // Nothing to do here, we don't store entities...
  }

}

/**
 * Controller class for the past event argument entity.
 */
class PastEventArgumentController extends EntityAPIController {

  public function create(array $values = array()) {
    return parent::create($values);
  }

  public function save($entity, DatabaseTransaction $transaction = NULL) {

    // Make sure the type is set.
    $entity->ensureType();

    // Normalize the data (original_data) to get a pretty output in simpletest.
    if ($data = $entity->getOriginalData()) {
      /*$this->insert = db_insert('past_event_data')
        ->fields(array('argument_id', 'parent_data_id', 'type', 'name', 'value', 'serialized'));
      $this->normalizeData($entity->argument_id, $data);
      $this->insert->execute();*/
    }
  }

  public function delete($ids, \DatabaseTransaction $transaction = NULL) {
    // Nothing to do here, we don't store entities...
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

    // Show a list with all modules instead a text field.
    $prop['module']['options list'] = array(get_class($this), 'moduleOptionsList');

    return $info;
  }

  /**
   * An OptionsList callback to return all Severity levels.
   * @return array
   */
  public function severityOptionsList() {
    return past_event_severities();
  }

  /**
   * Return an associative array with all enabled modules.
   * There might be applications where "module" is not cleanly used. You cannot search for those values.
   *
   * @return array
   *   Associative array wit key as the module name and the Value the same with uppercase first char.
   */
  public function moduleOptionsList() {
    if (empty(self::$modules)) {
      self::$modules = array();
      $result = db_query("SELECT name FROM {system} WHERE type='module' AND status=1 ORDER BY name;");

      foreach ($result as $res) {
        self::$modules[$res->name] = ucfirst($res->name);
      }
    }
    return self::$modules;
  }

}


class PastDBEventViewsController extends EntityDefaultViewsController {

  public function views_data() {
    $data = parent::views_data();
    $data['past_event']['event_id']['field']['handler'] = 'past_db_handler_field_event_id';
    return $data;
  }
}

class PastDBEventUIController extends EntityDefaultUIController {

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
      //'file' => $this->entityInfo['admin ui']['file'],
      //'file path' => $this->entityInfo['admin ui']['file path'],
    );
    return $items;
  }
}
