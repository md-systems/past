<?php

/**
 * @file
 * Definition of past_db_handler_field_trace_user.
 */

/**
 * Field handler to show user and add trace link. Also called Actor.
 *
 * @ingroup views_field_handlers
 */
class past_db_handler_field_trace_user extends views_handler_field_entity {

  /**
   * Preload the user so that render hits the static cache.
   */
  function pre_render(&$values) {
    parent::pre_render($values);

    $uids = array();
    foreach ($values as $value) {
      $uid = $this->get_value($value, 'uid');
      if (is_numeric($uid) && $uid > 0) {
        $uids[] = $uid;
      }
    }
    if ($uids) {
      user_load_multiple($uids);
    }
  }

  /**
   * {@inheritdoc}
   */
  function render($values) {
    $event = $this->get_value(NULL);
    return $event->getActorDropbutton(20, request_path());
  }
}
