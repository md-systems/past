<?php

/**
 * @file
 * Contains tests for the Past modules.
 */

namespace Drupal\past\Tests;

use Drupal\Core\Utility\Error;
use Drupal\simpletest\WebTestBase;
use Drupal\past\Entity\PastEventInterface;
use Drupal\past\Entity\PastEventArgumentInterface;
use Drupal\past\Entity\PastEventDataInterface;

/**
 * Tests for the Past logger.
 */
class PastWebTest extends WebTestBase {

  protected $profile = 'testing';

  /**
   * The Past configuration object.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $config;

  /**
   * Modules required to run the tests.
   *
   * @var string[]
   */
  public static $modules = array(
    'past',
    'past_db',
    'past_testhidden',
  );

  /**
   * {@inheritdoc}
   */
  public static function getInfo() {
    return array(
      'name' => 'Past API web tests',
      'description' => 'Generic API tests using the database backend',
      'group' => 'Past',
    );
  }

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();
    $this->config = \Drupal::config('past.settings');
  }

  public function testSave() {
    $user = $this->drupalCreateUser();
    past_event_save('past', 'test_user', 'Object argument', array('user' => $user));
    $event = $this->getLastEventByMachinename('test_user');
    $this->assertEqual($user, $event->getArgument('user')->getData());
    $this->assertEqual('stdClass', $event->getArgument('user')->getType());

    $exception = new \Exception('An exception', 500);
    past_event_save('past', 'test_exception', 'An exception', array('exception' => $exception));
    $event = $this->getLastEventByMachinename('test_exception');
    $expected = Error::decodeException($exception) + array('backtrace' => $exception->getTraceAsString());
    $this->assertEqual($expected, $event->getArgument('exception')->getData());
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
    $expected = Error::decodeException($exception) + array('backtrace' => $exception->getTraceAsString());
    $expected['previous'] = Error::decodeException($previous_exception) + array('backtrace' => $previous_exception->getTraceAsString());
    $expected['previous']['previous'] = Error::decodeException($previous_previous_exception) + array('backtrace' => $previous_previous_exception->getTraceAsString());
    $expected['previous']['previous']['previous'] = Error::decodeException($previous_previous_previous_exception) + array('backtrace' => $previous_previous_previous_exception->getTraceAsString());
    $this->assertEqual($expected, $event->getArgument('exception')->getData());

    past_event_save('past', 'test_timestamp', 'Event with a timestamp', array(), array('timestamp' => REQUEST_TIME - 1));
    $event = $this->getLastEventByMachinename('test_timestamp');
    $this->assertEqual(REQUEST_TIME - 1, $event->getTimestamp());
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
    $this->config->set('exception_handling', 0);
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
    $this->config->set('log_watchdog', 1);

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
      return entity_load('past_event', $event_id);
    }
  }

}
