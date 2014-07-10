<?php

namespace Drupal\past_db\Entity;

use Drupal\Core\Entity\Entity;

/**
 * Use a separate class for past_event types so we can specify some defaults
 * modules may alter.
 */
class PastEventType extends Entity {
  public $type;
  public $label;
  public $weight = 0;

  public function __construct($values = array()) {
    parent::__construct($values, 'past_event_type');
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
