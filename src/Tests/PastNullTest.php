<?php
/**
 * @file
 * Contains \Drupal\past\Tests\PastNullTest.
 */

namespace Drupal\past\Tests;
use Drupal\past\Entity\PastEventNull;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests the Past null implementation.
 *
 * @group past
 */
class PastNullTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = array('past');

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp(array('past'));
  }

  /**
   * Tests that past_event_create() returns PastEventUll when misconfigured.
   */
  public function testMissingBackend() {
    $event = past_event_create('past', 'test', 'A test log entry');
    $this->assertTrue($event instanceof PastEventNull);

    // Make sure that fluent calls are supported.
    $event->setParentEventId('')
    ->setSeverity(-1)
    ->setSessionId('')
    ->setMessage('')
    ->setTimestamp(-1)
    ->setMachineName('')
    ->setUid(-1);

    $array_argument = array('data' => array('sub' => 'value'), 'something' => 'else');
    $event->addArgument('first', $array_argument);
    $event->addArgument('second', 'simple');

    $event->addArgument('third', 'chaining')
    ->getKey();
  }
}
