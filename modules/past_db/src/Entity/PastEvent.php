<?php

/**
 * @file
 * Contains Drupal\past_db\Entity\PastEvent.
 */

namespace Drupal\past_db\Entity;

use Drupal\Component\Utility\String;
use Drupal\Core\Entity\ContentEntityBase;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\BaseFieldDefinition;
use Drupal\Core\Utility\Error;
use Drupal\past\PastEventArgumentInterface;
use Drupal\past\PastEventInterface;
use Drupal\past_db\PastEventArgument;
use Exception;

/**
 * Defines the past event entity.
 *
 * @ContentEntityType(
 *   id = "past_event",
 *   label = @Translation("Past event"),
 *   handlers = {
 *     "storage" = "Drupal\past_db\PastEventStorage",
 *     "view_builder" = "Drupal\past_db\PastEventViewBuilder",
 *     "access" = "Drupal\past_db\PastEventAccessControlHandler",
 *     "views_data" = "Drupal\past_db\PastEventViewsData",
 *   },
 *   base_table = "past_event",
 *   entity_keys = {
 *     "id" = "event_id",
 *     "bundle" = "type",
 *     "label" = "event_id",
 *     "uuid" = "uuid"
 *   },
 *   links = {
 *     "canonical" = "/admin/reports/past/{past_event}",
 *   },
 *   bundle_entity_type = "past_event_type",
 *   field_ui_base_route = "past_db.event_type.manage",
 *   permission_granularity = "entity_type"
 * )
 */
class PastEvent extends ContentEntityBase implements PastEventInterface {

  /**
   * {@inheritdoc}
   */
  public static function baseFieldDefinitions(EntityTypeInterface $entity_type) {
    $fields['event_id'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Event ID'))
      ->setDescription(t('The identifier of the event.'))
      ->setRequired(TRUE)
      ->setReadOnly(TRUE);
    $fields['uuid'] = BaseFieldDefinition::create('uuid')
      ->setLabel(t('UUID'))
      ->setDescription(t('The event UUID.'))
      ->setReadOnly(TRUE);
    $fields['module'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Module'))
      ->setDescription(t('The module that logged this event.'))
      ->setSetting('max_length', 128)
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'weight' => 1,
      ));
    $fields['machine_name'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Machine name'))
      ->setDescription(t('The machine name of this event.'))
      ->setReadOnly(TRUE)
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'weight' => 1,
      ));
    $fields['type'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Type'))
      ->setDescription(t('The type of this event.'))
      ->setRequired(TRUE)
      ->setSetting('target_type', 'past_event_type')
      ->setDefaultValue('past_event')
      ->setReadOnly(TRUE);
    $fields['session_id'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Session id'))
      ->setDescription(t('The session id of the user who triggered the event.'));
    $fields['referer'] = BaseFieldDefinition::create('string')
      ->setLabel('Referer')
      ->setDescription(t('The referer of the request who triggered the event.'))
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'weight' => 1,
      ));
    $fields['location'] = BaseFieldDefinition::create('string')
      ->setLabel('Location')
      ->setDescription(t('The URI of the request that triggered the event.'))
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'weight' => 1,
      ));
    $fields['message'] = BaseFieldDefinition::create('string')
      ->setLabel(t('Message'))
      ->setDescription(t('The event log message'))
      ->setDisplayOptions('view', array(
        'type' => 'string',
        'weight' => 1,
      ));
    $fields['severity'] = BaseFieldDefinition::create('integer')
      ->setLabel(t('Severity'))
      ->setDescription(t('The severity of this event.'))
      ->setSetting('size', 'small')
      ->setRequired(TRUE)
      ->setDefaultValue(PAST_SEVERITY_INFO);
    $fields['timestamp'] = BaseFieldDefinition::create('created')
      ->setLabel(t('Timestamp'))
      ->setDescription(t('The event timestamp.'))
      ->setDisplayOptions('view', array(
        'type' => 'timestamp',
        'weight' => 1,
      ));
    $fields['parent_event_id'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('Parent event ID'))
      ->setDescription(t('The parent event ID.'))
      ->setSetting('target_type', 'past_event');
    $fields['uid'] = BaseFieldDefinition::create('entity_reference')
      ->setLabel(t('User ID'))
      ->setDescription(t('The id of the user who triggered the event.'))
      ->setSetting('target_type', 'user');
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public static function preCreate(EntityStorageInterface $storage, array &$values) {
    parent::preCreate($storage, $values);
    if (empty($values['type'])) {
      $values['type'] = 'past_event';
    }
    if (empty($values['uid'])) {
      $values['uid'] = \Drupal::currentUser()->id();
    }
  }


  /**
   * The arguments of this event.
   *
   * @var PastEventArgumentInterface[]
   */
  protected $arguments;

  /**
   * The event ID's of this event's children.
   *
   * @var int[]
   */
  protected $child_events = array();

  /**
   * {@inheritdoc}
   */
  public function addArgument($key, $data, array $options = array()) {
    if (!is_array($this->arguments)) {
      $this->arguments = array();
    }

    // Entities are complex beings, let's use toArray() rather than clone.
    if ($data instanceof EntityInterface) {
      $options['type'] = 'entity:' . $data->getEntityTypeId();
      $data = $data->toArray();
    }
    // If it is an object, clone it to avoid changing the original and log it
    // at the current state. Except when it can't, like e.g. exceptions.
    elseif (is_object($data) && !($data instanceof Exception)) {
      $data = clone $data;
    }
    // Special support for exceptions, convert them to something that can be
    // stored.
    elseif (isset($data) && $data instanceof Exception) {
      $data = $this->decodeException($data);
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

    $this->arguments[$key] = new PastEventArgument(NULL, $key, $data, $options);
    return $this->arguments[$key];
  }

  /**
   * {@inheritdoc}
   */
  public function addArgumentArray($key_prefix, array $data, array $options = array()) {
    $arguments = array();
    foreach ($data as $key => $value) {
      $arguments[$key] = $this->addArgument($key_prefix . ':' . $key, $value, $options);
    }
    return $arguments;
  }

  /**
   * Loads and caches all arguments for this event.
   */
  protected function loadArguments() {
    if (!is_array($this->arguments)) {
      $this->arguments = array();
      $result = db_select('past_event_argument', 'a')
        ->fields('a')
        ->condition('event_id', $this->id())
        ->execute();
      while ($row = $result->fetchAssoc()) {
        $this->arguments[$row['name']] = new PastEventArgument($row['argument_id'], $row['name'], NULL, array(
          'type' => $row['type'],
          'raw' => $row['raw'],
        ));
      }
    }
  }

  /**
   * Returns a specific argument based on the key.
   *
   * return PastEventArgument
   *   The past event argument.
   */
  public function getArgument($key) {
    $this->loadArguments();
    return isset($this->arguments[$key]) ? $this->arguments[$key] : NULL;
  }

  /**
   * Returns all arguments of this event.
   *
   * return PastEventArgument[]
   *   The past event arguments.
   */
  public function getArguments() {
    $this->loadArguments();
    return $this->arguments;
  }

  /**
   * {@inheritdoc}
   */
  public function addException(Exception $exception, array $options = array()) {
    $this->addArgument('exception', $exception, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function getMachineName() {
    return $this->get('machine_name')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getModule() {
    return $this->get('module')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSeverity() {
    return $this->get('severity')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getSessionId() {
    return $this->get('session_id')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferer() {
    return $this->get('referer')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation() {
    return $this->get('location')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getMessage() {
    return $this->get('message')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getTimestamp() {
    return $this->get('timestamp')->value;
  }

  /**
   * {@inheritdoc}
   */
  public function getUid() {
    return $this->get('uid')->target_id;
  }

  /**
   * {@inheritdoc}
   */
  public function setParentEventId($event_id) {
    $this->parent_event_id = $event_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSeverity($severity) {
    $this->set('severity', $severity);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSessionId($session_id) {
    $this->set('session_id', $session_id);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setReferer($referer) {
    $this->set('referer', $this->shortenString($referer));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocation($location) {
    $this->set('location', $this->shortenString($location));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($message) {
    $this->set('message', $this->shortenString($message));
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimestamp($timestamp) {
    $this->set('timestamp', $timestamp);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMachineName($machine_name) {
    $this->set('machine_name', $machine_name);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setModule($module) {
    $this->set('module', $module);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUid($uid) {
    $this->set('uid', $uid);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function addChildEvent($event_id) {
    $this->child_events[] = $event_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function getChildEvents() {
    return $this->child_events;
  }

  /**
   * {@inheritdoc}
   */
  public function defaultUri() {
    return array(
      'path' => 'admin/reports/past/' . $this->event_id,
      'options' => array(),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function label() {
    if (!empty($this->defaultLabel)) {
      return $this->defaultLabel;
    }

    $this->defaultLabel = strip_tags($this->getMessage());

    if (empty($this->defaultLabel)) {
      $this->defaultLabel = t('Event #@id', array('@id' => $this->event_id));
    }

    return $this->defaultLabel;
  }

  /**
   * {@inheritdoc}
   */
  public function delete() {
    parent::delete();
    if (is_array($this->arguments)) {
      foreach ($this->arguments as $argument) {
        // Delete the data first, then the argument.
        db_delete('past_event_data')
          ->condition('argument_id', $argument->argument_id)
          ->execute();
        db_delete('past_event_argument')
          ->condition('argument_id', $argument->argument_id)
          ->execute();
      }
    }
  }

  /**
   * Returns the actor links as a ctools dropbutton.
   *
   * @param int $truncate
   *   (optional) Truncate the session ID in case no user exists to the given
   *   length. FALSE to disable, defaults to 20·
   * @param string $uri
   *   (optional) The uri to be used for the trace links, defaults to the
   *   extended view.
   *
   * @return sting
   *   The rendered links.
   */
  public function getActorDropbutton($truncate = 20, $uri = 'admin/reports/past/extended') {
    $links = array();
    $account = user_load((int) $this->getUid());
    $sid = $this->getSessionId();
    // If we have a user, display a dropbutton with link to the user profile and
    // a trace link and optionally a trace by session link.
    if ($account && $account->uid > 0) {
      $links[] = array(
        'title' => format_username($account),
        'href' => drupal_get_path_alias('user/' . $account->uid),
        'attributes' => array('title' => t('View profile')),
      );
      $links[] = array(
        'title' => t('Trace: !user', array('!user' => format_username($account))),
        'href' => $uri,
        'query' => array('uid' => $account->name),
      );
      if (!empty($sid)) {
        $links[] = array(
          'title' => t('Trace session: @session', array(
            '@session' => truncate_utf8($sid, 10, FALSE, TRUE))),
          'href' => $uri,
          'query' => array('session_id' => $sid),
          'attributes' => array('title' => check_plain($sid)),
        );
      }
      module_load_include('inc', 'views', 'includes/admin');
      views_ui_add_admin_css();
      return theme('links__ctools_dropbutton', array('links' => $links));
    }

    // If we only have a session ID, display that.
    if ($sid) {
      $title = t('Session: @session', array(
        '@session' => $truncate ? truncate_utf8($sid, $truncate, FALSE, TRUE) : $sid));
      return l($title, $uri, array('query' => array('session_id' => $sid)));
    }

    return t('Unknown');
  }

  /**
   * Formats an argument in HTML markup.
   *
   * @param string $name
   *   Name of the argument.
   * @param PastEventArgumentInterface $argument
   *   Argument instance.
   *
   * @return string
   *   A HTML div describing the argument and its data.
   */
  public function formatArgument($name, PastEventArgumentInterface $argument) {
    $back = '';
    $data = $argument->getData();
    if (is_array($data) || is_object($data)) {
      foreach ($data as $k => $v) {
        $back .= '<div style="padding-left:10px;">[<strong>' . String::checkPlain($k) . '</strong>] (<em>' . gettype($v) . '</em>): ' . $this->parseObject($v) . '</div>';
      }
    }
    else {
      $back = nl2br(String::checkPlain($data));
    }
    $back = '<div><strong>' . String::checkPlain($name) . '</strong> (<em>' . gettype($data) . '</em>): ' . $back . '</div>';
    return $back;
  }

  /**
   * Formats an object in HTML markup.
   *
   * @param object $obj
   *   The value to be formatted. Any type is accepted.
   * @param int $recursive
   *   (optional) Recursion counter to avoid long HTML for deep structures.
   *   Should be unset for any calls from outside the function itself.
   *
   * @return string
   *   A HTML div describing the value.
   */
  protected function parseObject($obj, $recursive = 0) {
    $max_recursion = \Drupal::config('past.settings')->get('max_recursion');
    if ($recursive > $max_recursion) {
      return t('<em>Too many nested objects ( @recursion )</em>', array('@recursion' => $max_recursion));
    }
    if (is_scalar($obj) || is_null($obj)) {
      return is_string($obj) ? nl2br(trim(String::checkPlain($obj))) : $obj;
    }

    $back = '';
    $css = 'style="padding-left:' . ($recursive + 10) . 'px;"';
    foreach ($obj as $k => $v) {
      $back .= '<div ' . $css . ' >[<strong>' . String::checkPlain($k) . '</strong>] (<em>' . gettype($v) . '</em>): ' . $this->parseObject($v, $recursive + 1) . '</div>';
    }
    return $back;
  }

  /**
   * Converts an exception into an array that can be easily stored.
   *
   * Previous/Originating exceptions are supported and put in the previous key,
   * recursively with up to 3 previous exceptions.
   *
   * @param Exception $exception
   *   The exception to decode.
   * @param int $level
   *   (optional) The nesting level, only used internally.
   *
   * @return array
   *   An array containing the decoded exception including the backtrace.
   */
  protected function decodeException(Exception $exception, $level = 0) {
    $data = Error::decodeException($exception);
    $data['backtrace'] = $exception->getTraceAsString();

    // If we're not deeper than 3 levels in this method, the exception has a
    // getPrevious() method (only exists on PHP >= 5.3) and there is a previous
    // exception, add it to the decoded data.
    if ($level < 3 && method_exists($exception, 'getPrevious') && $exception->getPrevious()) {
      $data['previous'] = $this->decodeException($exception->getPrevious(), ++$level);
    }
    return $data;
  }

  /**
   * Shortens a string to its first 255 chars.
   *
   * If longer than 255 chars, the last char is replaced with an ellipsis (…).
   *
   * @param string $string
   *   The string to be shortened.
   * @param int $max_length
   *   (optional) The maximal desired length. Defaults to 255.
   *
   * @return string
   *   The shortened string.
   */
  protected function shortenString($string, $max_length = 255) {
    if (strlen($string) > $max_length) {
      $string = substr($string, 0, $max_length - 1) . '…';
    }
    return $string;
  }
}
