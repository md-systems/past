<?php

/**
 * @file
 * Definition of Drupal\past_db\PastEventRenderController.
 */

namespace Drupal\past_db;

use Drupal\Core\Entity\EntityRenderController;

/**
 * Render controller for taxonomy terms.
 */
class PastEventRenderController extends EntityRenderController {

  /**
   * Overrides Drupal\Core\Entity\EntityRenderController::buildContent().
   */
  public function buildContent(array $entities, array $displays, $view_mode, $langcode = NULL) {
    parent::buildContent($entities, $displays, $view_mode, $langcode);

    foreach ($entities as $entity) {
      $this->buildContentSingle($entity, $view_mode, $langcode);
    }
  }

  protected function buildContentSingle($entity, $view_mode = 'full', $langcode = NULL) {
    $content = array();

    // global information about the event
    $content['message'] = array(
      '#type' => 'item',
      '#title' => t('Message'),
      '#markup' => $entity->getMessage(),
    );
    $content['module'] = array(
      '#type' => 'item',
      '#title' => t('Module'),
      '#markup' => $entity->getModule(),
    );
    $content['machine_name'] = array(
      '#type' => 'item',
      '#title' => t('Machine name'),
      '#markup' => $entity->getMachineName(),
    );
    $content['timestamp'] = array(
      '#type' => 'item',
      '#title' => t('Date'),
      '#markup' => format_date($entity->getTimestamp(), 'long'),
    );

    // show all arguments in a vertical_tab
    $content['arguments'] = array(
      '#type' => 'vertical_tabs',
      '#tree' => TRUE,
      '#weight' => 99,
    );

    foreach ($entity->getArguments() as $key => $argument) {
      $content['arguments']['fieldset_' . $key] = array(
        '#type' => 'fieldset',
        '#title' => ucfirst($key),
        '#collapsible' => TRUE,
        '#collapsed' => FALSE,
        '#group' => 'arguments',
        '#tree' => TRUE,
        '#weight' => -2,
      );
      $content['arguments']['fieldset_' . $key]['argument_' . $key] = array(
        '#type' => 'item',
        '#markup' => $entity->formatArgument($key, $argument),
      );
    }

    $entity->content[] = $content;
  }


}
