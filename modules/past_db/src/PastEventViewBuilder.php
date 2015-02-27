<?php
/**
 * @file
 * Definition of Drupal\past_db\PastEventViewBuilder.
 */

namespace Drupal\past_db;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityViewBuilder;
use Drupal\Core\Url;
use Drupal\past_db\Entity\PastEvent;

/**
 * Render controller for taxonomy terms.
 */
class PastEventViewBuilder extends EntityViewBuilder {
  /**
   * {@inheritdoc}
   */
  public function buildComponents(array &$build, array $entities, array $displays, $view_mode, $langcode = NULL) {
    parent::buildComponents($build, $entities, $displays, $view_mode, $langcode);

    foreach ($entities as $id => $entity) {
      /** @var PastEvent $entity */

      // Global information about the event.
//      $content['actor'] = array(
//        '#type' => 'item',
//        '#title' => t('Actor'),
//        '#markup' => $this->getActorDropbutton(FALSE),
//      );
      // Output URLs as links.
      if ($entity->getReferer()) {
        $build[$id]['referer'][0] = array(
          '#markup' => \Drupal::l($entity->getReferer(), Url::fromUri($entity->getReferer())),
        );
      }
      if ($entity->getLocation()) {
        $build[$id]['location'][0] = array(
          '#markup' => \Drupal::l($entity->getLocation(), Url::fromUri($entity->getLocation())),
        );
      }

      // @todo Display as vertical_tabs if that is enabled outside forms.
      foreach ($entity->getArguments() as $key => $argument) {
        $build[$id]['fieldset_' . $key] = array(
          '#type' => 'details',
          '#title' => ucfirst($key),
          '#open' => TRUE,
          '#tree' => TRUE,
          '#weight' => 10,
        );
        $build[$id]['fieldset_' . $key]['argument_' . $key] = array(
          '#type' => 'item',
          '#markup' => $entity->formatArgument($key, $argument),
        );
      }

    }
  }

  /**
   * {@inheritdoc}
   */
  protected function getBuildDefaults(EntityInterface $entity, $view_mode, $langcode) {
    $build = parent::getBuildDefaults($entity, $view_mode, $langcode);
    // There is no template, unset it to avoid a watchdog notice.
    unset($build['#theme']);
    return $build;
  }

}
