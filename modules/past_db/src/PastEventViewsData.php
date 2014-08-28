<?php
/**
 * @file
 * Contains \Drupal\past_db\PastEventViewsData.
 */

namespace Drupal\past_db;

use Drupal\views\EntityViewsData;

/**
 * Provides PastEvent-related integration information for Views.
 */
class PastEventViewsData extends EntityViewsData {
  /**
   * {@inheritdoc}
   */
  public function getViewsData() {
    $data = parent::getViewsData();

    // @todo Copied from past_db.controller.inc, not tested.
    $data['past_event']['argument_data'] = array(
      'title' => t('Argument data'),
      'help' => t('Display or filter by a specific argument data'),
      'real field' => 'value',
      'field' => array(
        'handler' => 'past_db_handler_field_event_argument_data',
      ),
      'filter' => array(
        'handler' => 'past_db_handler_filter_event_argument_value',
      ),
    );
    $data['past_event']['trace_user'] = array(
      'title' => t('Trace user'),
      'help' => t('Add links to sort the view by user / session id.'),
      'real field' => 'uid',
      'field' => array(
        'handler' => 'past_db_handler_field_trace_user',
      ),
    );

    return $data;
  }

}
