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
  $form['past_watchdog'] = array(
    '#type' => 'fieldset',
    '#title' => t('Watchdog logging'),
    '#collapsible' => TRUE,
    '#collapsed' => FALSE,
  );
  $form['past_watchdog']['past_log_watchdog'] = array(
    '#type' => 'checkbox',
    '#title' => t('Log watchdog to past event log'),
    '#default_value' => variable_get('past_log_watchdog', 0),
    '#description' => t('When enabled, Past will take watchdog log entries. <em>To avoid redundancy, you can turn off the database logging module.</em>'),
  );

  $severities = array();
  foreach (watchdog_severity_levels() as $key => $value) {
    $severities['WATCHDOG_SEVERITY_' . $key] = $value;
  }

  $form['past_watchdog']['past_backtrace_exclude'] = array(
    '#type' => 'checkboxes',
    '#default_value' => variable_get('past_backtrace_exclude', drupal_map_assoc(array('WATCHDOG_SEVERITY_' . WATCHDOG_DEBUG, 'WATCHDOG_SEVERITY_' . WATCHDOG_INFO))),
    '#options' => $severities,
    '#title' => t('Exclude watchdog severity levels from writing backtraces'),
    '#description' => t('A backtrace is logged for all severities that are not checked.'),
    '#states' => array('visible' => array('input[name="past_log_watchdog"]' => array('checked' => TRUE))),
  );

  return system_settings_form($form);
}
