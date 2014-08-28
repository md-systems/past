<?php
/**
 * @file
 * Definition of Drupal\past_db\PastEventViewBuilder.
 */

namespace Drupal\past_db;

use Drupal\Core\Entity\EntityViewBuilder;
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
      $build[$id]['message'] = array(
        '#type' => 'item',
        '#title' => t('Message'),
        '#markup' => $entity->getMessage(),
      );
      $build[$id]['module'] = array(
        '#type' => 'item',
        '#title' => t('Module'),
        '#markup' => $entity->getModule(),
      );
      $build[$id]['machine_name'] = array(
        '#type' => 'item',
        '#title' => t('Machine name'),
        '#markup' => $entity->getMachineName(),
      );
      $build[$id]['timestamp'] = array(
        '#type' => 'item',
        '#title' => t('Date'),
        '#markup' => format_date($entity->getTimestamp(), 'long'),
      );
//      $content['actor'] = array(
//        '#type' => 'item',
//        '#title' => t('Actor'),
//        '#markup' => $this->getActorDropbutton(FALSE),
//      );
      $build[$id]['referer'] = array(
        '#type' => 'item',
        '#title' => t('Referer'),
        '#markup' => l($entity->getReferer(), $entity->getReferer()),
      );
      $build[$id]['location'] = array(
        '#type' => 'item',
        '#title' => t('Location'),
        '#markup' => l($entity->getLocation(), $entity->getLocation()),
      );

      // Show all arguments in a vertical_tab.
      $build[$id]['arguments'] = array(
        // @todo vertical_tabs element can currently not have parent, uncomment when https://www.drupal.org/node/1016912 is fixed.
        //'#type' => 'vertical_tabs',
        '#tree' => TRUE,
        '#weight' => 99,
      );

      foreach ($entity->getArguments() as $key => $argument) {
        $build[$id]['arguments']['fieldset_' . $key] = array(
          '#type' => 'fieldset',
          '#title' => ucfirst($key),
          '#collapsible' => TRUE,
          '#collapsed' => FALSE,
          '#group' => 'arguments',
          '#tree' => TRUE,
          '#weight' => -2,
        );
        $build[$id]['arguments']['fieldset_' . $key]['argument_' . $key] = array(
          '#type' => 'item',
          '#markup' => $entity->formatArgument($key, $argument),
        );
      }

    }
  }

}
