<?php
/**
 * @file
 * Contains \Drupal\past\Entity\PastEventArgumentNull.
 */

namespace Drupal\past\Entity;
use Drupal\past\PastEventArgumentInterface;

/**
 * Null implementation that is used as a fallback or when logging is disabled.
 */
class PastEventArgumentNull implements PastEventArgumentInterface {

  /**
   * {@inheritdoc}
   */
  public function getData() {
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getKey() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getRaw() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getType() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function setRaw($data, $json_encode = TRUE) {

  }

  /**
   * {@inheritdoc}
   */
  public function getOriginalData() {
    return NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function ensureType() {

  }
}
