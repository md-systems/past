<?php

/**
 * @file
 * Contains the entity classes for Past DB.
 */

namespace Drupal\past_db\Plugin\Core\Entity;

use \Drupal\past\Entity\PastEventInterface;
use \Drupal\Core\Entity\Entity;
use \Exception;

/**
 * Defines the past event entity.
 *
 * @EntityType(
 *   id = "past_event",
 *   label = @Translation("Past event"),
 *   bundle_label = @Translation("Type"),
 *   module = "past_db",
 *   controllers = {
 *     "storage" = "Drupal\past\PastEventStorageController",
 *     "render" = "Drupal\past\PastEventRenderController",
 *     "access" = "Drupal\past\PastEventAccessController",
 *   },
 *   base_table = "past_event",
 *   uri_callback = "past_event_entity_uri",
 *   fieldable = TRUE,
 *   entity_keys = {
 *     "id" = "event_id",
 *     "bundle" = "type",
 *     "label" = "event_id",
 *     "uuid" = "uuid"
 *   },
 *   bundle_keys = {
 *     "bundle" = "type"
 *   },
 *   links = {
 *     "canonical" = "/admin/reports/past/{past_event}",
 *   },
 *   menu_base_path = "admin/reports/past/%past_event",
 *   route_base_path = "admin/config/development/past-types/{bundle}",
 *   permission_granularity = "entity_type"
 * )
 */
class PastEvent extends Entity implements PastEventInterface {
  /**
   * The event UUID.
   *
   * @var string
   */
  public $uuid;

  /**
   * The identifier of the event.
   *
   * @var int
   */
  public $event_id;

  /**
   * The module that logged this event.
   *
   * @var string
   */
  public $module;

  /**
   * The machine name of this event.
   *
   * @var string
   */
  public $machine_name;

  /**
   * The event type/bundle.
   *
   * @var string
   */
  public $type;

  /**
   * The subject/message of the event to be logged.
   *
   * @var string
   */
  public $message;

  /**
   * The severity of the event.
   *
   * @var int
   */
  public $severity;

  /**
   * The session ID of the user who triggered the event.
   *
   * @var string
   */
  public $session_id;

  /**
   * The HTTP referrer of the request which triggered the event.
   *
   * @var string
   */
  public $referer;

  /**
   * The URI of the request which triggered the event.
   *
   * @var string
   */
  public $location;

  /**
   * A timestamp of when the event occurred.
   *
   * @var int
   */
  public $timestamp;

  /**
   * The ID of the parent event.
   *
   * @var int
   */
  public $parent_event_id;

  /**
   * The {users}.uid of the user who triggered the event.
   *
   * @var int
   */
  public $uid;

  /**
   * The arguments of this event.
   *
   * @var PastEventArgument[]
   */
  protected $arguments;

  /**
   * The event ID's of this event's children.
   *
   * @var int[]
   */
  protected $child_events = array();

  /**
   * The maximum recursion depth to prevent infinite recursion.
   *
   * @var int
   */
  protected $max_recursion;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $values = array(), $entity_type = NULL) {
    parent::__construct($values, $entity_type);
    $this->max_recursion = variable_get('past_max_recursion', 10);
  }

  /**
   * Implements Drupal\Core\Entity\EntityInterface::id().
   */
  public function id() {
    return $this->get('event_id')->value;
  }

  /**
   * Overrides \Drupal\Core\Entity\EntityNG::init().
   */
  protected function init() {
    parent::init();
    unset($this->uuid);
    unset($this->event_id);
    unset($this->module);
    unset($this->machine_name);
    unset($this->type);
    unset($this->message);
    unset($this->severity);
    unset($this->timestamp);
    unset($this->parent_event_id);
  }

  /**
   * {@inheritdoc}
   */
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

    $this->arguments[$key] = entity_create('past_event_argument', array('name' => $key, 'original_data' => $data) + $options);
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
      foreach (entity_load_multiple_by_properties('past_event_argument', array('event_id' => $this->event_id)) as $argument) {
        $this->arguments[$argument->name] = $argument;
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
    return $this->session_id;
  }

  /**
   * {@inheritdoc}
   */
  public function getReferer() {
    return $this->referer;
  }

  /**
   * {@inheritdoc}
   */
  public function getLocation() {
    return $this->location;
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
    return $this->get('uid')->value;
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
    $this->severity = $severity;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setSessionId($session_id) {
    $this->session_id = $session_id;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setReferer($referer) {
    $this->referer = $this->shortenString($referer);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setLocation($location) {
    $this->location = $this->shortenString($location);
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMessage($message) {
    $this->message = $message;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setTimestamp($timestamp) {
    $this->timestamp = $timestamp;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setMachineName($machine_name) {
    $this->machine_name = $machine_name;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setModule($module) {
    $this->module = $module;
    return $this;
  }

  /**
   * {@inheritdoc}
   */
  public function setUid($uid) {
    $this->uid = $uid;
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
  public function defaultLabel() {
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
  public function buildContent($view_mode = 'full', $langcode = NULL) {
    $content = array();

    // global information about the event
    $content['message'] = array(
      '#type' => 'item',
      '#title' => t('Message'),
      '#markup' => check_plain($this->getMessage()),
    );
    $content['module'] = array(
      '#type' => 'item',
      '#title' => t('Module'),
      '#markup' => check_plain($this->getModule()),
    );
    $content['machine_name'] = array(
      '#type' => 'item',
      '#title' => t('Machine name'),
      '#markup' => check_plain($this->getMachineName()),
    );
    $content['timestamp'] = array(
      '#type' => 'item',
      '#title' => t('Date'),
      '#markup' => format_date($this->getTimestamp(), 'long'),
    );
    $content['actor'] = array(
      '#type' => 'item',
      '#title' => t('Actor'),
      '#markup' => $this->getActorDropbutton(FALSE),
    );
    $content['referer'] = array(
      '#type' => 'item',
      '#title' => t('Referrer'),
      '#markup' => l($this->getReferer(), $this->getReferer()),
    );
    $content['location'] = array(
      '#type' => 'item',
      '#title' => t('Location'),
      '#markup' => l($this->getLocation(), $this->getLocation()),
    );

    // Show all arguments in a vertical_tab.
    $content['arguments'] = array(
      '#type' => 'vertical_tabs',
      '#tree' => TRUE,
      '#weight' => 99,
    );

    foreach ($this->getArguments() as $key => $argument) {
      $content['arguments']['fieldset_' . $key] = array(
        '#type' => 'fieldset',
        '#title' => ucfirst($key),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
        '#group' => 'arguments',
        '#tree' => TRUE,
        '#weight' => -2,
      );
      $content['arguments']['fieldset_' . $key]['argument_' . $key] = array(
        '#type' => 'item',
        '#markup' => $this->formatArgument($key, $argument),
      );
    }

    return entity_get_controller($this->entityType)->buildContent($this, $view_mode, $langcode, $content);
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
   * @param PastEventArgument $argument
   *   Argument instance.
   *
   * @return string
   *   A HTML div describing the argument and its data.
   */
  protected function formatArgument($name, $argument) {
    $back = '';
    $data = $argument->getData();
    if (is_array($data) || is_object($data)) {
      foreach ($data as $k => $v) {
        $back .= '<div style="padding-left:10px;">[<strong>' . check_plain($k) . '</strong>] (<em>' . gettype($v) . '</em>): ' . $this->parseObject($v) . '</div>';
      }
    }
    else {
      $back = nl2br(check_plain($data));
    }
    $back = '<div><strong>' . check_plain($name) . '</strong> (<em>' . gettype($data) . '</em>): ' . $back . '</div>';
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
    if ($recursive > $this->max_recursion) {
      return t('<em>Too many nested objects ( @recursion )</em>', array('@recursion' => $this->max_recursion));
    }
    if (is_scalar($obj) || is_null($obj)) {
      return is_string($obj) ? nl2br(trim(check_plain($obj))) : $obj;
    }

    $back = '';
    $css = 'style="padding-left:' . ($recursive + 10) . 'px;"';
    foreach ($obj as $k => $v) {
      $back .= '<div ' . $css . ' >[<strong>' . check_plain($k) . '</strong>] (<em>' . gettype($v) . '</em>): ' . $this->parseObject($v, $recursive + 1) . '</div>';
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
    $data = _drupal_decode_exception($exception);
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
   * Shortens a string to it's first 255 chars.
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