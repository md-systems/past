<?php

/**
 * @file
 * Contains \Drupal\past\Form\PastSettingsForm.
 */

namespace Drupal\past\Form;

use Drupal\Core\Form\ConfigFormBase;

/**
 * Displays the pants settings form.
 */
class PastSettingsForm extends ConfigFormBase {

  /**
   * Implements \Drupal\Core\Form\FormInterface::getFormID().
   */
  public function getFormID() {
    return 'past_settings';
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface::buildForm().
   */
  public function buildForm(array $form, array &$form_state) {
    $config = \Drupal::config('past.settings');

    // Options for events_expire
    $expire_options = array(86400, 604800, 604800 * 4);

    $form['events_expire'] = array(
      '#type'  => 'select',
      '#title' => t('Log expiration interval'),
      '#description' => t('Specify the time period to be used expiring past events.'),
      '#default_value' => $config->get('events_expire'),
      '#options' => array_map('format_interval', array_combine($expire_options, $expire_options)),
      '#empty_option' => '- None -',
    );
    $form['shutdown_handling'] = array(
      '#type' => 'checkbox',
      '#title' => t('Register PHP shutdown error handler'),
      '#default_value' => $config->get('shutdown_handling'),
      '#description' => t('When enabled, Past will register a shutdown handler that logs previously uncaught PHP errors.'),
    );
    $form['exception_handling'] = array(
      '#type' => 'checkbox',
      '#title' => t('Register PHP exception handler'),
      '#default_value' => $config->get('exception_handling'),
      '#description' => t('When enabled, Past will log every exception via its PHP exception handler.'),
    );
    $form['log_session_id'] = array(
      '#type' => 'checkbox',
      '#title' => t('Log the session id'),
      '#default_value' => $config->get('log_session_id'),
      '#description' => t("When enabled, Past will log the user's session id and entries can be traced by session id."),
    );
    $form['watchdog'] = array(
      '#type' => 'fieldset',
      '#title' => t('Watchdog logging'),
      '#collapsible' => TRUE,
      '#collapsed' => FALSE,
    );
    $form['watchdog']['log_watchdog'] = array(
      '#type' => 'checkbox',
      '#title' => t('Log watchdog to past event log'),
      '#default_value' => $config->get('log_watchdog'),
      '#description' => t('When enabled, Past will take watchdog log entries. <em>To avoid redundancy, you can turn off the database logging module.</em>'),
    );

    $form['watchdog']['backtrace_include'] = array(
      '#type' => 'checkboxes',
      '#default_value' => $config->get('backtrace_include'),
      '#options' => watchdog_severity_levels(),
      '#title' => t('Watchdog severity levels from writing backtraces'),
      '#description' => t('A backtrace is logged for all severities that are checked.'),
      '#states' => array('visible' => array('input[name="log_watchdog"]' => array('checked' => TRUE))),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface:validateForm()
   */
  public function validateForm(array &$form, array &$form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * Implements \Drupal\Core\Form\FormInterface:submitForm()
   *
   * @see book_remove_button_submit()
   */
  public function submitForm(array &$form, array &$form_state) {
    $included_severity_levels = array();
    foreach ($form_state['values']['backtrace_include'] as $level => $enabled) {
      if ($enabled) {
        $included_severity_levels[] = $level;
      }
    }
    \Drupal::config('past.settings')
      ->set('events_expire', $form_state['values']['events_expire'])
      ->set('exception_handling', $form_state['values']['exception_handling'])
      ->set('log_watchdog', $form_state['values']['log_watchdog'])
      ->set('backtrace_include', $included_severity_levels)
      ->set('shutdown_handling', $form_state['values']['shutdown_handling'])
      ->set('log_session_id', $form_state['values']['log_session_id'])
      ->save();

    parent::SubmitForm($form, $form_state);
  }
}
