<?php

/**
 * Provide settings form.
 */
function past_admin_settings() {
  $form = array();
  $form['past_events_expire'] = array(
    '#type'  => 'select',
    '#title' => t('Set the time period to be used'),
    '#description' => t('Specify the time period to be used expiring past events.'),
    '#default_value' => variable_get('past_events_expire', 0),
    '#options' => drupal_map_assoc(array(86400, 604800, 604800 * 4), 'format_interval'),
    '#empty_option' => '- None -',
  );

  return system_settings_form($form);
}
