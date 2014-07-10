<?php

/**
 * @file
 * Contains \Drupal\past\Form\PastSettingsForm.
 */

namespace Drupal\past\Form;

use Drupal\Core\Controller\ControllerInterface;
use Drupal\system\SystemConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Displays the pants settings form.
 */
class PastSettingsForm extends SystemConfigFormBase {

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
    $config = config('past.settings');

    $form['past_events_expire'] = array(
      '#type'  => 'select',
      '#title' => t('Log expiration interval'),
      '#description' => t('Specify the time period to be used expiring past events.'),
      '#default_value' => $config->get('past_events_expire'),
      '#options' => drupal_map_assoc(array(86400, 604800, 604800 * 4), 'format_interval'),
      '#empty_option' => '- None -',
    );
    $form['past_shutdown_handling'] = array(
      '#type' => 'checkbox',
      '#title' => t('Register PHP shutdown error handler'),
      '#default_value' => variable_get('past_shutdown_handling', 1),
      '#description' => t('When enabled, Past will register a shutdown handler that logs previously uncaught PHP errors.'),
    );
    $form['past_exception_handling'] = array(
      '#type' => 'checkbox',
      '#title' => t('Register PHP exception handler'),
      '#default_value' => $config->get('past_exception_handling'),
      '#description' => t('When enabled, Past will log every exception via its PHP exception handler.'),
    );
    $form['past_log_session_id'] = array(
      '#type' => 'checkbox',
      '#title' => t('Log the session id'),
      '#default_value' => variable_get('past_log_session_id', 1),
      '#description' => t("When enabled, Past will log the user's session id and entries can be traced by session id."),
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
      '#default_value' => $config->get('past_log_watchdog'),
      '#description' => t('When enabled, Past will take watchdog log entries. <em>To avoid redundancy, you can turn off the database logging module.</em>'),
    );

    $severities = array();
    foreach (watchdog_severity_levels() as $key => $value) {
      $severities['WATCHDOG_SEVERITY_' . $key] = $value;
    }

    $form['past_watchdog']['past_backtrace_include'] = array(
      '#type' => 'checkboxes',
      '#default_value' => variable_get('past_backtrace_include', _past_watchdog_severity_defaults()),
      '#options' => $severities,
      '#title' => t('Watchdog severity levels from writing backtraces'),
      '#description' => t('A backtrace is logged for all severities that are checked.'),
      '#states' => array('visible' => array('input[name="past_log_watchdog"]' => array('checked' => TRUE))),
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
    config('past.settings')
        ->set('past_events_expire', $form_state['values']['past_events_expire'])
        ->set('past_exception_handling', $form_state['values']['past_exception_handling'])
        ->set('past_log_watchdog', $form_state['values']['past_log_watchdog'])
        ->set('past_backtrace_exclude', $form_state['values']['past_backtrace_exclude'])
        ->save();

    parent::SubmitForm($form, $form_state);
  }
}
