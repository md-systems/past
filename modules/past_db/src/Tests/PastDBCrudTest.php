<?php
/**
 * @file
 * Contains \Drupal\past_db\Tests\PastDBCrudTest.
 */

namespace Drupal\past_db\Tests;

use Drupal\Core\Entity\Entity;
use Drupal\field\Entity\FieldInstanceConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\past_db\Entity\PastEvent;
use Drupal\past_db\Entity\PastEventType;
use Drupal\simpletest\KernelTestBase;

/**
 * Tests saving and loading past events and event types.
 *
 * @group past
 */
class PastDBCrudTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var string[]
   */
  public static $modules = array(
    'past_db',
    'user',
    'field',
  );

  /**
   * {@inheritdoc}
   */
  public function setUp() {
    parent::setUp();

    $this->installEntitySchema('past_event');
    $this->installSchema('past_db', array('past_event_argument', 'past_event_data'));
    $this->installEntitySchema('user');
  }

  /**
   * Tests saving and loading event type.
   */
  public function testEventType() {
    // Minimal event type.
    PastEventType::create(array(
      'id' => 'minimal',
    ))->save();
    $event_type = PastEventType::load('minimal');
    $this->assertNull($event_type->label());

    // Full event type.
    PastEventType::create(array(
      'id' => 'full',
      'label' => 'Full event type',
      'weight' => 5,
    ))->save();
    $event_type = PastEventType::load('full');
    $this->assertEqual($event_type->label(), 'Full event type');
    $this->assertEqual($event_type->weight, 5);
  }

  /**
   * Tests saving an event.
   */
  public function testEvent() {
    // Minimal event - test default values.
    $created = PastEvent::create();
    $created->save();
    /** @var PastEvent $loaded */
    $loaded = PastEvent::load($created->id());
    $this->assertNull($loaded->getModule());
    $this->assertNull($loaded->getMachineName());
    $this->assertEqual($loaded->bundle(), 'past_event');
    $this->assertNull($loaded->getSessionId());
    $this->assertNull($loaded->getReferer());
    $this->assertNull($loaded->getLocation());
    $this->assertNull($loaded->getMessage());
    $this->assertEqual($loaded->getSeverity(), PAST_SEVERITY_INFO);
    $this->assertNotNull($loaded->getTimestamp());
    $this->assertEqual($loaded->getUid(), 0);

    // Full event - test defined values.
    $values = array(
      'module' => $this->randomMachineName(),
      'machine_name' => $this->randomMachineName(),
      'session_id' => $this->randomMachineName(),
      'referer' => 'http://foo.example.com',
      'location' => 'http://bar.example.com',
      'message' => $this->randomString(),
      'severity' => PAST_SEVERITY_ERROR,
      'timestamp' => 1337,
      'uid' => 2,
      // @todo Can we set current user in a KernelTest?
    );
    $created = PastEvent::create($values);
    $created->save();
    $loaded = PastEvent::load($created->id());
    $this->assertEqual($loaded->getModule(), $values['module']);
    $this->assertEqual($loaded->getMachineName(), $values['machine_name']);
    $this->assertEqual($loaded->bundle(), 'past_event');
    $this->assertEqual($loaded->getSessionId(), $values['session_id']);
    $this->assertEqual($loaded->getReferer(), $values['referer']);
    $this->assertEqual($loaded->getLocation(), $values['location']);
    $this->assertEqual($loaded->getMessage(), $values['message']);
    $this->assertEqual($loaded->getSeverity(), $values['severity']);
    $this->assertEqual($loaded->getTimestamp(), $values['timestamp']);
    $this->assertEqual($loaded->getUid(), $values['uid']);
  }

  /**
   * Tests saving an event with an event type.
   */
  public function testUseEventType() {
    // Create a type.
    $type = PastEventType::create(array(
      'id' => $this->randomMachineName(),
    ));
    $type->save();

    // Create an event of the type.
    $created = PastEvent::create(array(
      'type' => $type->id(),
    ));
    $created->save();

    // Assert the bundle property is set.
    $loaded = PastEvent::load($created->id());
    $this->assertEqual($loaded->bundle(), $type->id());
  }

  /**
   * Tests fieldability of event types.
   */
  public function testFieldability() {
    // Create a type.
    $type = PastEventType::create(array(
      'id' => $this->randomMachineName(),
    ));
    $type->save();

    // Attach a field to the type.
    $field_name = 'field_test';
    $field_storage = FieldStorageConfig::create(array(
      'name' => $field_name,
      'type' => 'string',
      'entity_type' => 'past_event',
    ));
    $field_storage->save();
    $field_instance = FieldInstanceConfig::create(array(
      'field_name' => $field_name,
      'entity_type' => 'past_event',
      'bundle' => $type->id(),
    ));
    $field_instance->save();

    // Create an event using the field.
    $field_value = $this->randomString();
    $created = PastEvent::create(array(
      'type' => $type->id(),
      $field_name => $field_value,
    ));
    $created->save();

    // Assert the field value is retrieved.
    $loaded = PastEvent::load($created->id());
    $this->assertEqual($loaded->get($field_name)->value, $field_value);
  }

  /**
   * Tests saving and loading an event with an argument.
   */
  public function testArgument() {
    // Scalar arguments.
    $this->assertArgumentPersists($this->randomString(), 'string');
    $this->assertArgumentPersists(rand(), 'int');
    $this->assertArgumentPersists(TRUE, 'bool');
    $this->assertArgumentPersists(3.14, 'float');

    // Array as argument.
    $this->assertArgumentPersists(array($this->randomMachineName() => $this->randomString()), 'array');

    // Object as argument.
    $this->assertArgumentPersists($this->randomObject(), 'object');

    // @todo Recursive array as argument.

    // @todo Recursive object as argument.

    // Entity as argument.
    $this->assertArgumentPersists(PastEvent::create(), 'entity');
  }

  /**
   * Asserts that an argument is equal before saving and after loading.
   *
   * @param mixed $data
   *   The data to save for the argument.
   * @param string $name
   *   The key for the argument.
   */
  protected function assertArgumentPersists($data, $name) {
    /** @var PastEvent $created */
    $created = PastEvent::create();
    $created->addArgument($name, $data);
    $created->save();

    /** @var PastEvent $loaded */
    $loaded = PastEvent::load($created->id());

    // Assert argument and data were loaded.
    if (!$this->assertNotNull($loaded->getArgument($name), "The loaded $name argument is not null")
      || !$this->assertNotNull($loaded->getArgument($name)->getData(), "The loaded $name argument data is not null")) {
      return;
    }

    $loaded_data = $loaded->getArgument($name)->getData();
    if (!$this->assertEqual(gettype($loaded_data), gettype($data))) {
      return;
    }

    // Use toArray() on entities to avoid recursion.
    if ($data instanceof Entity) {
      $data = $data->toArray();
      $loaded_data = $loaded_data->toArray();
    }

    // Assert and maybe debug.
    if (!is_array($data)) {
      if (!$this->assertEqual($loaded_data, $data, "The $name argument is correctly saved and retrieved")) {
        debug($data, "Original data");
        debug($loaded_data, "Loaded data");
      };
    }
    else {
      foreach (array_keys($data) as $key) {
        if (!$this->assertEqual($loaded_data[$key], $data[$key], "The $name argument's $key item is correctly saved and retrieved")) {
          debug($data[$key], "Original data $key");
          debug($loaded_data[$key], "Loaded data $key");
        };
      }
    }
  }

}
