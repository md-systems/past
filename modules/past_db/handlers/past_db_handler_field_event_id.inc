<?php
/**
 * @file
 *
 */
class past_db_handler_field_event_id extends views_handler_field_entity {

  public function render($values) {
    $item = $this->get_value($values);
    $element = array();
    $element['#theme'] = 'links';
    $element['#attributes'] = array('class' => array('inline'));
    $element['#links']['view'] = array(
      'href' => 'admin/reports/past/item/' . $item->event_id,
      'title' => t('View #@id', array('@id' => $item->event_id)),
    );
    return drupal_render($element);
  }
}
