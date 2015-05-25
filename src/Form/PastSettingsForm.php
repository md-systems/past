<?php

/**
 * @file
 * Contains \Drupal\past\Form\PastSettingsForm.
 */

namespace Drupal\past\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\StringTranslation\TranslationWrapper;

/**
 * Displays the pants settings form.
 */
class PastSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'past_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['past.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('past.settings');
    $date_formatter = \Drupal::service('date.formatter');

    // Options for events_expire
    $expire_options = array(86400, 604800, 604800 * 4);

    $form['events_expire'] = array(
      '#type'  => 'select',
      '#title' => t('Log expiration interval'),
      '#description' => t('Specify the time period to be used expiring past events.'),
      '#default_value' => $config->get('events_expire'),
      '#options' => array_map(array($date_formatter, 'formatInterval'), array_combine($expire_options, $expire_options)),
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
      '#options' => RfcLogLevel::getLevels(),
      '#title' => t('Watchdog severity levels from writing backtraces'),
      '#description' => t('A backtrace is logged for all severities that are checked.'),
      '#states' => array('visible' => array('input[name="log_watchdog"]' => array('checked' => TRUE))),
    );

    $form['log_cache_tags'] = array(
      '#type' => 'checkbox',
      '#title' => t('Log cache tag invalidations'),
      '#default_value' => $config->get('log_cache_tags'),
      '#description' => t('When enabled, Past will log cache tag invalidations including a backtrace. This should only be used to debug invalidations and can be slow when a lot of cache tag invalidations happen.'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $included_severity_levels = array();
    foreach ($form_state->getValue('backtrace_include') as $level => $enabled) {
      if ($enabled) {
        $included_severity_levels[] = $level;
      }
    }
    $this->config('past.settings')
      ->set('events_expire', $form_state->getValue('events_expire'))
      ->set('exception_handling', $form_state->getValue('exception_handling'))
      ->set('log_watchdog', $form_state->getValue('log_watchdog'))
      ->set('log_cache_tags', $form_state->getValue('log_cache_tags'))
      ->set('backtrace_include', $included_severity_levels)
      ->set('shutdown_handling', $form_state->getValue('shutdown_handling'))
      ->set('log_session_id', $form_state->getValue('log_session_id'))
      ->save();

    parent::SubmitForm($form, $form_state);
  }
}
