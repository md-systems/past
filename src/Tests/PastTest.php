<?php

/**
 * @file
 * Contains tests for the Past modules.
 */

namespace Drupal\past\Tests;

use Drupal\simpletest\WebTestBase;
use Drupal\past\Entity\PastEventInterface;
use Drupal\past\Entity\PastEventArgumentInterface;
use Drupal\past\Entity\PastEventDataInterface;

class PastTest extends WebTestBase {

  protected $profile = 'testing';

  /**
   * The Past configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * {@inheritdoc}
   */
  static function getInfo() {
    return array(
      'name' => 'Past API tests',
      'description' => 'Generic API tests using the database backend',
      'group' => 'Past',
    );
  }

  /**
   * {@inheritdoc}
   */
  function setUp() {
    parent::setUp(array('past', 'past_db', 'past_testhidden'));
    $this->config = \Drupal::config('past.settings');
  }

  /**
   * Tests the functional Past interface.
   */
  function testSave() {
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

    $user = $this->drupalCreateUser();
    past_event_save('past', 'test_user', 'Object argument', array('user' => $user));
    $event = $this->getLastEventByMachinename('test_user');
    $this->assertEqual($user, $event->getArgument('user')->getData());
    $this->assertEqual('stdClass', $event->getArgument('user')->getType());

    $exception = new Exception('An exception', 500);
    past_event_save('past', 'test_exception', 'An exception', array('exception' => $exception));
    $event = $this->getLastEventByMachinename('test_exception');
    $expected = _drupal_decode_exception($exception) + array('backtrace' => $exception->getTraceAsString());
    $this->assertEqual($expected, $event->getArgument('exception')->getData());
    // @todo: We still need to know that this was an exception.
    $this->assertEqual('array', $event->getArgument('exception')->getType());

    // Created an exception with 4 nested previous exceptions, the 4th will be
    // ignored.
    $ignored_exception = new Exception ('This exception will be ignored', 90);
    $previous_previous_previous_exception = new Exception ('Previous previous previous exception', 99, $ignored_exception);
    $previous_previous_exception = new Exception ('Previous previous exception', 100, $previous_previous_previous_exception);
    $previous_exception = new Exception('Previous exception', 500, $previous_previous_exception);
    $exception = new Exception('An exception', 500, $previous_exception);
    past_event_save('past', 'test_exception', 'An exception', array('exception' => $exception));
    $event = $this->getLastEventByMachinename('test_exception');

    // Build up the expected data, each previous exception is logged one level deeper.
    $expected = _drupal_decode_exception($exception) + array('backtrace' => $exception->getTraceAsString());
    $expected['previous'] =_drupal_decode_exception($previous_exception) + array('backtrace' => $previous_exception->getTraceAsString());
    $expected['previous']['previous'] =_drupal_decode_exception($previous_previous_exception) + array('backtrace' => $previous_previous_exception->getTraceAsString());
    $expected['previous']['previous']['previous'] =_drupal_decode_exception($previous_previous_previous_exception) + array('backtrace' => $previous_previous_previous_exception->getTraceAsString());
    $this->assertEqual($expected, $event->getArgument('exception')->getData());

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
    global $user;

    // First enable watchdog logging.
    $config->set('log_watchdog', 1);
    $machine_name = 'test_watchdog';

    // Simpletest does not cleanly mock these _SERVER variables.
    $_SERVER['REQUEST_URI'] = 'mock-request-uri';
    $_SERVER['HTTP_REFERER'] = 'mock-referer';

    $msg = 'something';
    watchdog($machine_name, $msg, NULL, WATCHDOG_INFO, NULL);
    $event = $this->getLastEventByMachinename($machine_name);
    $this->assertEqual('watchdog', $event->getModule());
    $this->assertEqual($msg, $event->getMessage());
    $this->assertEqual(WATCHDOG_INFO, $event->getSeverity());
    $this->assertEqual(1, count($event->getArguments()));
    $this->assertNotNull($event->getArgument('watchdog_args'));
    $this->assertTrue(strpos($event->getLocation(), 'mock-request-uri')>0,
      'Contains mock-request-uri.');
    $this->assertTrue(strpos($event->getReferer(), 'mock-referer')===0,
      'Contains mock-referer.');

    // Note that here we do not create a test user but use the user that has
    // triggered the test as this is the user captured in the watchdog().
    $this->assertEqual($user->uid, $event->getUid());

    // Note that here we do not create a test user but use the user that has
    // triggered the test as this is the user captured in the watchdog().
    $this->assertEqual($user->uid, $event->getUid());

    $msg = 'something new';
    $nice_url = 'http://www.md-systems.ch';
    watchdog($machine_name, $msg, NULL, WATCHDOG_NOTICE, $nice_url);
    $event = $this->getLastEventByMachinename($machine_name);
    $this->assertEqual('watchdog', $event->getModule());
    $this->assertEqual($msg, $event->getMessage());
    $this->assertEqual(WATCHDOG_NOTICE, $event->getSeverity());
    // A notice generates a backtrace and there's an additional link
    // argument, so there are three arguments.
    $this->assertEqual(3, count($event->getArguments()));
    $this->assertNotNull($event->getArgument('watchdog_args'));
    $this->assertNotNull($event->getArgument('link'));
    $this->assertEqual($nice_url, $event->getArgument('link')->getData());

    // Now we disable watchdog logging.
    $config->set('log_watchdog', 0);
    watchdog($machine_name, 'something Past will not see', NULL, WATCHDOG_INFO, NULL);
    $event = $this->getLastEventByMachinename($machine_name);
    // And still the previous message should be found.
    $this->assertEqual($msg, $event->getMessage());
  }

  /*
  * Tests the session id behavior.
  */
  function testSessionIdBehavior() {
    global $user;

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

    $config->set('log_session_id', 0);
    past_event_save('past', 'test1', 'Another test log entry');
    $event = $this->getLastEventByMachinename('test1');
    $this->assertEqual('', $event->getSessionId());

    $event = past_event_create('past', 'test2', 'And Another test log entry');
    $event->setSessionId('trace me');
    $event->save();
    $event = $this->getLastEventByMachinename('test2');
    $this->assertEqual('trace me', $event->getSessionId());

    $config->set('log_session_id', 1);
    $event = past_event_create('past', 'test3', 'And Yet Another test log entry');
    $event->setSessionId('trace me too');
    $event->save();
    $event = $this->getLastEventByMachinename('test3');
    $this->assertEqual('trace me too', $event->getSessionId());
  }

  /*
  * Tests the disabled exception handler.
  */
  public function testExceptionHandler() {
    // Create user to test logged uid.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    // Let's produce an exception, the exception handler is disabled by default.
    $this->drupalGet('past_trigger_error/Exception');
    $this->assertText(t('The website encountered an unexpected error. Please try again later.'));
    $this->assertText('Exception: This is an exception.');

    // No exception should have been logged.
    $event = $this->getLastEventByMachinename('unhandled_exception');
    $this->assertNull($event);

    // Let's produce an exception, the exception handler is enabled by default.
    $this->drupalGet('past_trigger_error/Exception');
    $this->assertText(t('The website encountered an unexpected error. Please try again later.'));
    $this->assertText('Exception: This is an exception.');

    // Now we have an log event, assert it.
    $event = $this->getLastEventByMachinename('unhandled_exception');
    $this->assertEqual('past', $event->getModule());
    $this->assertEqual('unhandled_exception', $event->getMachineName());
    $this->assertEqual(PAST_SEVERITY_ERROR, $event->getSeverity());
    $this->assertEqual(1, count($event->getArguments()));
    $data = $event->getArgument('exception')->getData();
    $this->assertTrue(array_key_exists('backtrace', $data));
    $this->assertEqual($account->uid, $event->getUid());

    // Disable exception handling and re-throw the exception.
    $config->set('exception_handling', 0);
    $this->drupalGet('past_trigger_error/Exception');
    $this->assertText(t('The website encountered an unexpected error. Please try again later.'));
    $this->assertText('Exception: This is an exception.');

    // No new exception should have been logged.
    $event_2 = $this->getLastEventByMachinename('unhandled_exception');
    $this->assertEqual($event->id(), $event_2->id(), 'No new event was logged');
  }

  /**
   * Tests the shutdown function.
   */
  public function testShutdownFunction() {
    // Create user to test logged uid.
    $account = $this->drupalCreateUser();
    $this->drupalLogin($account);

    // Let's trigger an error, the error handler is disabled by default.
    $this->drupalGet('past_trigger_error/E_ERROR');

    // Now we have a log event, assert it.
    $event = $this->getLastEventByMachinename('fatal_error');

    $this->assertEqual('past', $event->getModule());
    $this->assertEqual('fatal_error', $event->getMachineName());
    $this->assertEqual(PAST_SEVERITY_CRITICAL, $event->getSeverity());
    $this->assertEqual(1, count($event->getArguments()));
    $this->assertEqual('Cannot use object of type stdClass as array', $event->getMessage());
    $data = $event->getArgument('error')->getData();
    $this->assertEqual($data['type'], E_ERROR);
    $this->assertEqual($account->uid, $event->getUid());
  }

  /**
   * Tests triggering PHP errors.
   *
   * @todo We leave out E_PARSE as we can't handle it and it would make our code unclean.
   * We do not test E_USER_* cases. They are not PHP errors.
   */
  public function testErrors() {
    // Enable hook_watchdog capture.
    $config->set('log_watchdog', 1);

    $this->drupalGet('past_trigger_error/E_COMPILE_ERROR');
    $event = $this->getLastEventByMachinename('php');
    $this->assertTextContains($event->getMessage(), 'Warning: require_once');

    $this->drupalGet('past_trigger_error/E_COMPILE_WARNING');
    $event = $this->getLastEventByMachinename('php');
    $this->assertTextContains($event->getMessage(), 'Warning: include_once');

    $this->drupalGet('past_trigger_error/E_DEPRECATED');
    $event = $this->getLastEventByMachinename('php');
    $this->assertTextContains($event->getMessage(), 'Deprecated function: Function call_user_method() is deprecated');

    $this->drupalGet('past_trigger_error/E_NOTICE');
    $event = $this->getLastEventByMachinename('php');
    $this->assertTextContains($event->getMessage(), 'Notice: Undefined variable');

    $this->drupalGet('past_trigger_error/E_RECOVERABLE_ERROR');
    $event = $this->getLastEventByMachinename('php');
    $this->assertTextContains($event->getMessage(), 'Recoverable fatal error');

    $this->drupalGet('past_trigger_error/E_WARNING');
    $event = $this->getLastEventByMachinename('php');
    $this->assertTextContains($event->getMessage(), 'Warning: fopen');

    $this->drupalGet('past_trigger_error/E_STRICT');
    $event = $this->getLastEventByMachinename('php');
    $this->assertTextContains($event->getMessage(), 'Strict warning: Non-static method Strict::test() should not be called statically');
    // Make sure that the page is rendered correctly.
    $this->assertText('hello, world');

    // Test E_STRICT errors that are thrown during parsing of a file.
    $this->drupalGet('past_trigger_error/E_STRICT_parse');
    // This scenario can not be logged, so we just make sure that the page is
    // rendered correctly.
    $this->assertText('hello, world');
    $this->assertText('Strict warning: Declaration of');
    $event_2 = $this->getLastEventByMachinename('php');
    $this->assertEqual($event->id(), $event_2->id(), 'No new event was logged');
  }

  /**
   * Asserts if $text contains $chunk.
   *
   * @param string $text
   * @param string $chunk
   */
  function assertTextContains($text, $chunk) {
    $this->assert(strpos($text, $chunk) !== FALSE,
      t('@text contains @chunk.', array(
        '@text' => $text,
        '@chunk' => $chunk,
    )));
  }
  /**
   * Asserts if $text starts with $chunk.
   *
   * @param string $text
   * @param string $chunk
   */
  function assertTextStartsWith($text, $chunk) {
    $this->assert(strpos($text, $chunk) === 0,
      t('@text starts with @chunk.', array(
        '@text' => $text,
        '@chunk' => $chunk
    )));
  }

  /**
   * Test that watchdog logs of type 'php' don't produce notices.
   */
  public function testErrorArray() {
    $config->set('log_watchdog', TRUE);
    watchdog('php', 'This is some test watchdog log of type php', array());
  }

  /**
   * Overrides DrupalWebTestCase::curlHeaderCallback().
   *
   * Does not report errors from the client site as exceptions as we are
   * expecting them and they're required for our test.
   *
   * @param $curlHandler
   *   The cURL handler.
   * @param $header
   *   An header.
   */
  protected function curlHeaderCallback($curlHandler, $header) {
    // Header fields can be extended over multiple lines by preceding each
    // extra line with at least one SP or HT. They should be joined on receive.
    // Details are in RFC2616 section 4.
    if ($header[0] == ' ' || $header[0] == "\t") {
      // Normalize whitespace between chucks.
      $this->headers[] = array_pop($this->headers) . ' ' . trim($header);
    }
    else {
      $this->headers[] = $header;
    }

    // Save cookies.
    if (preg_match('/^Set-Cookie: ([^=]+)=(.+)/', $header, $matches)) {
      $name = $matches[1];
      $parts = array_map('trim', explode(';', $matches[2]));
      $value = array_shift($parts);
      $this->cookies[$name] = array('value' => $value, 'secure' => in_array('secure', $parts));
      if ($name == $this->session_name) {
        if ($value != 'deleted') {
          $this->session_id = $value;
        }
        else {
          $this->session_id = NULL;
        }
      }
    }

    // This is required by cURL.
    return strlen($header);
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
      return entity_load_single('past_event', $event_id);
    }
  }

}