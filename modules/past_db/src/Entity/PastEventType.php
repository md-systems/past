<?php

namespace Drupal\past_db\Entity;

use Drupal\Core\Config\Entity\ConfigEntityBase;

/**
 * Use a separate class for past_event types so we can specify some defaults
 * modules may alter.
 *
 * @ConfigEntityType(
 *   id = "past_event_type",
 *   label = @Translation("Past event type"),
 *   bundle_label = @Translation("Type"),
 *   entity_keys = {
 *     "id" = "type",
 *     "label" = "label",
 *   },
 *   handlers = {
 *     "list_builder" = "Drupal\past_db\EventTypeListBuilder",
 *   },
 *   bundle_of = "past_event",
 * )
 */
class PastEventType extends ConfigEntityBase {
  public $type;
  public $label;
  public $weight = 0;

  /**
   * {@inheritdoc}
   */
  public function id() {
    return $this->type;
  }

  /**
   * Returns whether the past_event type is locked.
   *
   * A locked event type may not be deleted or renamed.
   *
   * PastEvent types provided in code are automatically treated as locked, as
   * well as any fixed past_event type.
   */
  public function isLocked() {
    return isset($this->status) && empty($this->is_new) && (($this->status & ENTITY_IN_CODE) || ($this->status & ENTITY_FIXED));
  }
}
