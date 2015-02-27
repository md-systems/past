<?php

/**
 * @file
 * Contains tests for the Past modules.
 */

namespace Drupal\past\Tests;

use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\Utility\Error;
use Drupal\simpletest\KernelTestBase;
use Drupal\past\PastEventInterface;

/**
 * Generic API tests using the database backend.
 *
 * @group past
 */
class PastKernelTest extends KernelTestBase {

  protected $profile = 'testing';

  /**
   * Modules required to run the tests.
   *
   * @var string[]
   */
  public static $modules = array(
    'past',
    'past_db',
    'system',
    'user',
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->installEntitySchema('past_event');
    $this->installEntitySchema('user');
    $this->installConfig(array('past', 'past_db'));
    $this->installSchema('past_db', array('past_event_argument', 'past_event_data'));
    $this->installSchema('system', 'sequences');
  }

  /**
   * Tests the functional Past interface.
   */
  public function testSave() {
    past_event_save('past', 'test', 'A test log entry');
    $event = $this->getLastEventByMachinename('test');
    $this->assertEqual('past', $event->getModule());
    $this->assertEqual('test', $event->getMachineName());
    $this->assertEqual('A test log entry', $event->getMessage());
    $this->assertEqual(session_id(), $event->getSessionId());
    $this->assertEqual(REQUEST_TIME, $event->getTimestamp());
    $this->assertEqual(PAST_SEVERITY_INFO, $event->getSeverity());
    $this->assertEqual(array(), $event->getArguments());

    past_event_save('past', 'test1', 'Another test log entry');
    $event = $this->getLastEventByMachinename('test1');
    $this->assertEqual('Another test log entry', $event->getMessage());

    $test_string = $this->randomString();
    past_event_save('past', 'test_argument', 'A test log entry with arguments', array('test' => $test_string, 'test2' => 5));
    $event = $this->getLastEventByMachinename('test_argument');
    $this->assertEqual(2, count($event->getArguments()));
    $this->assertEqual('string', $event->getArgument('test')->getType());
    $this->assertEqual($test_string, $event->getArgument('test')->getData());
    $this->assertEqual(5, $event->getArgument('test2')->getData());
    $this->assertEqual('test', $event->getArgument('test')->getKey());
    $this->assertEqual('string', $event->getArgument('test')->getType());
    $this->assertEqual('integer', $event->getArgument('test2')->getType());

    $this->assertNull($event->getArgument('does_not_exist'));

    $array_argument = array(
      'key1' => $this->randomString(),
      'key2' => $this->randomString(),
    );
    past_event_save('past', 'test_array', 'Array argument', array('array' => $array_argument));
    $event = $this->getLastEventByMachinename('test_array');
    $this->assertEqual(1, count($event->getArguments()));
    $this->assertEqual($array_argument, $event->getArgument('array')->getData());
    $this->assertEqual('array', $event->getArgument('array')->getType());

    $user = $this->createUser();
    past_event_save('past', 'test_user', 'Object argument', array('user' => $user));
    $event = $this->getLastEventByMachinename('test_user');
    $this->assertEqual($user->toArray(), $event->getArgument('user')->getData(),
      'The user entity argument is preserved by saving and loading.');
    $this->assertEqual('entity:user', $event->getArgument('user')->getType());

    $exception = new \Exception('An exception', 500);
    past_event_save('past', 'test_exception', 'An exception', array('exception' => $exception));
    $event = $this->getLastEventByMachinename('test_exception');
    $expected = array('backtrace' => $exception->getTraceAsString()) + Error::decodeException($exception);
    $this->assertEqual($expected, $event->getArgument('exception')->getData(),
      'The exception argument is preserved by saving and loading.');
    // @todo: We still need to know that this was an exception.
    $this->assertEqual('array', $event->getArgument('exception')->getType());

    // Created an exception with 4 nested previous exceptions, the 4th will be
    // ignored.
    $ignored_exception = new \Exception('This exception will be ignored', 90);
    $previous_previous_previous_exception = new \Exception('Previous previous previous exception', 99, $ignored_exception);
    $previous_previous_exception = new \Exception('Previous previous exception', 100, $previous_previous_previous_exception);
    $previous_exception = new \Exception('Previous exception', 500, $previous_previous_exception);
    $exception = new \Exception('An exception', 500, $previous_exception);
    past_event_save('past', 'test_exception', 'An exception', array('exception' => $exception));
    $event = $this->getLastEventByMachinename('test_exception');

    // Build up the expected data, each previous exception is logged one level
    // deeper.
    $expected = array('backtrace' => $exception->getTraceAsString()) + Error::decodeException($exception);
    $expected['previous'] = array('backtrace' => $previous_exception->getTraceAsString()) + Error::decodeException($previous_exception);
    $expected['previous']['previous'] = array('backtrace' => $previous_previous_exception->getTraceAsString()) + Error::decodeException($previous_previous_exception);
    $expected['previous']['previous']['previous'] = array('backtrace' => $previous_previous_previous_exception->getTraceAsString()) + Error::decodeException($previous_previous_previous_exception);
    $this->assertEqual($expected, $event->getArgument('exception')->getData(),
      'The nested exception argument is preserved by saving and loading.');

    past_event_save('past', 'test_timestamp', 'Event with a timestamp', array(), array('timestamp' => REQUEST_TIME - 1));
    $event = $this->getLastEventByMachinename('test_timestamp');
    $this->assertEqual(REQUEST_TIME - 1, $event->getTimestamp());
  }

  /**
   * Tests the Past OO interface.
   */
  public function testObjectOrientedInterface() {
    $event = past_event_create('past', 'test_raw', 'Message with arguments');
    $array_argument = array('data' => array('sub' => 'value'), 'something' => 'else');
    $argument = $event->addArgument('first', $array_argument);
    $argument->setRaw(array('data' => array('sub' => 'value')));
    $event->addArgument('second', 'simple');
    $event->save();

    $event = $this->getLastEventByMachinename('test_raw');
    $this->assertEqual($array_argument, $event->getArgument('first')->getData());
    $this->assertEqual('simple', $event->getArgument('second')->getData());

    // Test the exclude filter.
    $event = past_event_create('past', 'test_exclude', 'Exclude filter');
    $event->addArgument('array', $array_argument, array('exclude' => array('something')));
    $event->save();
    $excluded_array = $array_argument;
    unset($excluded_array['something']);

    $event = $this->getLastEventByMachinename('test_exclude');
    $this->assertEqual(1, count($event->getArguments()));
    $this->assertEqual($excluded_array, $event->getArgument('array')->getData());
  }

  /**
   * Tests if the watchdog replacement works as expected.
   */
  public function testWatchdogReplacement() {
    $user = \Drupal::currentUser();
    $logger = \Drupal::logger('test_watchdog');

    // First enable watchdog logging.
    $this->config('past.settings')
      ->set('log_watchdog', 1)
      ->save();
    $machine_name = 'test_watchdog';

    // Simpletest does not cleanly mock these _SERVER variables.
    // @todo Update or remove if necessary.
    $_SERVER['REQUEST_URI'] = 'mock-request-uri';
    $_SERVER['HTTP_REFERER'] = 'mock-referer';

    $msg = 'something';
    $logger->info($msg);
    $event = $this->getLastEventByMachinename($machine_name);
    $this->assertNotNull($event, 'Watchdog call caused an event.');
    $this->assertEqual('watchdog', $event->getModule());
    $this->assertEqual($msg, $event->getMessage());
    $this->assertEqual(RfcLogLevel::INFO, $event->getSeverity());
    $this->assertEqual(1, count($event->getArguments()));
    $this->assertNotNull($event->getArgument('watchdog_args'));
    $this->assertEqual($event->getLocation(), 'http://localhost/');
    $this->assertTrue(strpos($event->getReferer(), 'mock-referer')===0,
      'Contains mock-referer.');

    // Note that here we do not create a test user but use the user that has
    // triggered the test as this is the user captured in the $logger->info().
    $this->assertEqual($user->id(), $event->getUid());

    $msg = 'something new';
    $nice_url = 'http://www.md-systems.ch';
    $logger->notice($msg, array('link' => $nice_url));
    $event = $this->getLastEventByMachinename($machine_name);
    $this->assertEqual('watchdog', $event->getModule());
    $this->assertEqual($msg, $event->getMessage());
    $this->assertEqual(RfcLogLevel::NOTICE, $event->getSeverity());
    // A notice generates a backtrace and there's an additional link
    // argument, so there are three arguments.
    $this->assertEqual(3, count($event->getArguments()));
    $this->assertNotNull($event->getArgument('watchdog_args'));
    $this->assertNotNull($event->getArgument('link'));
    $this->assertEqual($nice_url, $event->getArgument('link')->getData());

    // Now we disable watchdog logging.
    $this->config('past.settings')
      ->set('log_watchdog', 0)
      ->save();
    $logger->info('something Past will not see');
    $event = $this->getLastEventByMachinename($machine_name);
    // And still the previous message should be found.
    $this->assertEqual($msg, $event->getMessage());
  }

  /**
   * Tests the session id behavior.
   */
  public function testSessionIdBehavior() {
    $user = \Drupal::currentUser();

    // Test a global user object without a session ID.
    $user->sid = NULL;
    past_event_save('past', 'test', 'A test log entry');
    $event = $this->getLastEventByMachinename('test');
    $this->assertEqual(session_id(), $event->getSessionId());

    // Set a session ID on the user object.
    $user->sid = 'session id';
    past_event_save('past', 'test_sid', 'A test log entry');
    $event = $this->getLastEventByMachinename('test_sid');
    $this->assertEqual($user->sid, $event->getSessionId());

    // Set a secure session ID on the user object, this should be used if
    // present.
    $user->ssid = 'securesession id';
    past_event_save('past', 'test_ssid', 'A test log entry');
    $event = $this->getLastEventByMachinename('test_ssid');
    $this->assertEqual($user->ssid, $event->getSessionId());

    $this->config('past.settings')
      ->set('log_session_id', 0)
      ->save();
    past_event_save('past', 'test1', 'Another test log entry');
    $event = $this->getLastEventByMachinename('test1');
    $this->assertEqual('', $event->getSessionId());

    $event = past_event_create('past', 'test2', 'And Another test log entry');
    $event->setSessionId('trace me');
    $event->save();
    $event = $this->getLastEventByMachinename('test2');
    $this->assertEqual('trace me', $event->getSessionId());

    $this->config('past.settings')
      ->set('log_session_id', 1)
      ->save();
    $event = past_event_create('past', 'test3', 'And Yet Another test log entry');
    $event->setSessionId('trace me too');
    $event->save();
    $event = $this->getLastEventByMachinename('test3');
    $this->assertEqual('trace me too', $event->getSessionId());
  }

  /**
   * Test that watchdog logs of type 'php' don't produce notices.
   */
  public function testErrorArray() {
    $this->config('past.settings')
      ->set('log_watchdog', TRUE)
      ->save();
    \Drupal::logger('php')->notice('This is some test watchdog log of type php');
  }

  /**
   * Returns the last event with a given machine name.
   *
   * @param string $machine_name
   *
   * @return PastEventInterface
   */
  public function getLastEventByMachinename($machine_name) {
    $event_id = db_query_range('SELECT event_id FROM {past_event} WHERE machine_name = :machine_name ORDER BY event_id DESC', 0, 1, array(':machine_name' => $machine_name))->fetchField();
    if ($event_id) {
      return \Drupal::entityManager()
        ->getStorage('past_event')
        ->load($event_id);
    }
  }

  /**
   * Create a random user without permissions.
   *
   * @param array $values
   *   (optional) Any options to forward to entity_create().
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   The created user.
   */
  protected function createUser(array $values = array()) {
    $user = entity_create('user', $values + array(
        'name' => $this->randomMachineName(),
        'status' => 1,
      ));
    $user->enforceIsNew()->save();
    return $user;
  }

}
