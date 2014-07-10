<?php

/**
 * @file
 * Contains \Drupal\taxonomy\TermAccessController.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Entity\EntityAccessController;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Defines an access controller for the taxonomy term entity.
 *
 * @see \Drupal\past_db\Entity\PastEvent
 */
class PastEventAccessController extends EntityAccessController {

  /**
   * {@inheritdoc}
   */
  protected function checkAccess(EntityInterface $entity, $operation, $langcode, AccountInterface $account) {
    return user_access('administer past', $account);
  }

}
