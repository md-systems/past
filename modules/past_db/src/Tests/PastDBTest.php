<?php

/**
 * @file
 * Contains \Drupal\past_db\Tests\PastDBTest.
 */

namespace Drupal\past_db\Tests;
use Drupal\Core\Session\AccountInterface;
use Drupal\field\Entity\FieldConfig;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\past_db\Entity\PastEvent;

/**
 * Tests for database backend of the Past module.
 *
 * @group past
 */
class PastDBTest extends PastDBTestBase {

  public static $modules = array(
    'views',
    'past',
    'past_db',
    'field_ui',
    'entity_reference',
  );

  /**
   * A user with the 'view past reports' permission.
   *
   * @var AccountInterface
   */
  protected $viewUser;

  /**
   * Creates an administrator user and sample events.
   */
  public function setUp() {
    parent::setUp();
    $admin = $this->drupalCreateUser(array(
      'administer past',
      'administer past_event display',
      'administer past_event fields',
    ));
    $this->drupalLogin($admin);
    $this->viewUser = $this->drupalCreateUser(array(
      'view past reports',
    ));
    $this->createEvents();
  }

  /**
   * Tests event bundles.
   *
   * @todo Move to kernel test
   */
  public function testEventBundles() {
    $event_type = past_event_type_create('test_event', 'Test event');
    $event_type->save();

    $event_type = past_event_get_types('test_event');
    $this->assertEqual($event_type->label, 'Test event');
    $this->assertEqual($event_type->id, 'test_event');

    $event = past_event_create('past', 'test_event', 'test message');
    $event->type = 'test_event';
    $event->save();

    $events = $this->loadEvents();
    /** @var PastEvent $event */
    $event = array_pop($events);

    $this->assertEqual($event->bundle(), 'test_event');
  }

  /**
   * Tests event extra fields display.
   */
  public function testEventExtraFields() {
    // Check for default bundle.
    $this->drupalGet('admin/config/development/past-types');
    $this->assertText('Default', 'Default bundle was found.');

    // Check for extra fields display on default bundle.
    $this->drupalGet('admin/config/development/past-types/manage/past_event/display');
    $this->assertText(t('Message'));
    $this->assertText(t('Module'));
    $this->assertText(t('Machine name'));
    $this->assertText(t('Event time'));
    $this->assertText(t('User'));
    $this->assertText(t('Arguments'));

    // Add new bundle.
    $edit = array(
      'label' => 'Test bundle',
      'id' => 'test_bundle',
    );
    $this->drupalPostForm('admin/config/development/past-types/add', $edit, t('Save'));
    $this->assertText(t('Machine name: @name', array('@name' => $edit['id'])), 'Created bundle was found.');

    // Check for extra fields display on newly created bundle.
    $this->drupalGet('admin/config/development/past-types/manage/' . $edit['id'] . '/display');
    $this->assertText(t('Message'));
    $this->assertText(t('Module'));
    $this->assertText(t('Machine name'));
    $this->assertText(t('Event time'));
    $this->assertText(t('User'));
    $this->assertText(t('Arguments'));

    // Create event of newly created type.
    $values = array(
      'bundle' => $edit['id'],
      'message' => 'testmessage',
      'module' => 'testmodule',
      'machine_name' => 'testmachinename',
    );
    /* @var PastEvent $event */
    $event = entity_create('past_event', $values);
    $event->save();

    $this->drupalLogin($this->viewUser);
    $this->drupalGet('admin/reports/past/' . $event->id());
    $this->assertText($values['message']);
    $this->assertText($values['module']);
    $this->assertText($values['machine_name']);
  }

  /**
   * Test fieldability.
   */
  public function testFieldability() {
    // Add new bundle.
    $bundle = 'test_bundle';
    $edit = array(
      'label' => 'Test bundle',
      'id' => $bundle,
    );
    $this->drupalPostForm('admin/config/development/past-types/add', $edit, t('Save'));

    // Create an entity reference field on the bundle.
    $field_instance = $this->addField($bundle);
    // Check if the field shows up in field config of the bundle.
    $this->drupalGet('admin/config/development/past-types/manage/' . $bundle . '/fields');
    $this->assertText($field_instance->label());
    $this->assertText($field_instance->getName());
    $this->assertText(t('Entity reference'));

    // Create an event that we can reference to.
    $referenced_event_message = 'Referenced Event Test message';
    $referenced_event = past_event_create('past_db', 'test_referenced_event', $referenced_event_message);
    $referenced_event->save();

    // Create an event of the bundle.
    $values = array(
      'message' => 'testmessage',
      'module' => 'testmodule',
      'machine_name' => 'testmachinename',
      'type' => $bundle,
      $field_instance->getName() => $referenced_event->id(),
    );
    /* @var PastEvent $event */
    $event = entity_create('past_event', $values);
    $event->save();

    // Check whether the bundle was saved correct.
    $event = entity_load('past_event', $event->id());
    $this->assertEqual($event->type->target_id, $bundle, 'Created event uses test bundle.');

    $this->drupalLogin($this->viewUser);
    // Check if the created fields shows up on the event display.
    $this->drupalGet('admin/reports/past/' . $event->id());
    // Check field label display.
    $this->assertText($field_instance->label());
    // Check field value display.
    $this->assertText($referenced_event_message);
  }

  /**
   * Tests the Past event log UI.
   */
  public function testAdminUI() {
    $this->drupalLogin($this->viewUser);
    // Open the event log.
    $this->drupalGet('admin/reports/past');

    // Check for some messages.
    $this->assertText($this->event_desc . 100);
    $this->assertText($this->event_desc . 99);
    $this->assertText($this->event_desc . 98);
    $this->assertText($this->event_desc . 51);

    // Check severities.
    $this->assertText($this->severities[PAST_SEVERITY_DEBUG]);
    $this->assertText($this->severities[PAST_SEVERITY_INFO]);
    $this->assertText($this->severities[PAST_SEVERITY_WARNING]);

    // Test if we have correct classes for severities.
    $class_names = past_db_severity_css_classes_map();
    $i = 0;
    /* @var SimpleXMLElement $row */
    foreach ($this->xpath('//table[contains(@class, @views-table)]/tbody/tr') as $row) {
      // Testing first 10 should be enough.
      if ($i > 9) {
        break;
      }
      $event_id = trim($row->td);
      $event = $this->events[$event_id];
      $class_name = $class_names[$event->getSeverity()];
      $attributes = $row->attributes();
      $this->assertTrue(strpos($attributes['class'], $class_name) !== FALSE);
      $i++;
    }

    // Check machine name.
    $this->assertText($this->machine_name);

    // Check for the exposed filter fields.
    $this->assertFieldByName('module', '');
    $this->assertFieldByName('severity', 'All');
    $this->assertFieldByName('machine_name', '');
    $this->assertFieldByName('message', '');

    // Check paging.
    $this->assertText('next ›');
    $this->assertText('last »');

    // Open the 2nd page.
    $options = array(
      'query' => array(
        'module' => '',
        'message' => '',
        'page' => 1,
      ),
    );
    $this->drupalGet('admin/reports/past', $options);

    // Check for some messages.
    $this->assertText($this->event_desc . 50);
    $this->assertText($this->event_desc . 49);
    $this->assertText($this->event_desc . 1);

    // Check paging.
    $this->assertText('‹ previous');
    $this->assertText('« first');

    // Go to the first detail page.
    $this->drupalGet('admin/reports/past/1');

    $this->assertText($this->machine_name);
    $this->assertText($this->event_desc . 1);
    $this->assertText('Referer');
    $this->assertLink('http://example.com/test-referer');
    $this->assertText('Location');
    $this->assertLink('http://example.com/this-url-gets-heavy-long/testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttest-testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttest-testtesttesttesttesttesttesttesttesttesttesttesttesttesttesttest-testtesttesttes…');
    $this->assertText('arg1');
    $this->assertText('arg2');
    $this->assertText('arg3');
    $this->assertText('First Argument');

    // Check events with a float argument.
    $event = past_event_save('past_db', 'float_test', 'Float test', array('float' => 3.14));
    $this->drupalGet('admin/reports/past/' . $event->id());
    $this->assertText('Float test');
    $this->assertText('3.14');

    $this->drupalLogout();

    // Check permissions for detail page.
    $this->drupalGet('admin/reports/past/1');
    $this->assertText(t('You are not authorized to access this page'));
    // Check permissions for event log.
    $this->drupalGet('admin/reports/past');
    $this->assertText(t('You are not authorized to access this page'));
  }

  /**
   * Creates an entityreference field and adds an instance of it to a bundle.
   *
   * @param string $bundle
   *   The bundle name.
   *
   * @return \Drupal\Core\Field\FieldConfigInterface
   *   The definition of the field instance.
   */
  protected function addField($bundle) {
    $field_storage = FieldStorageConfig::create(array(
      'entity_type' => 'past_event',
      'field_name' => 'field_fieldtest',
      'type' => 'entity_reference',
      'settings' => array(
        'target_type' => 'past_event',
      ),
    ));
    $field_storage->save();
    $field_instance = FieldConfig::create(array(
      'label' => 'test entity reference',
      'field_storage' => $field_storage,
      'bundle' => $bundle,
    ));
    $field_instance->save();
    entity_get_display('past_event', $bundle, 'default')
      ->setComponent('field_fieldtest', array(
        'type' => 'entity_reference_label',
      ))
      ->save();
    return $field_instance;
  }
}
