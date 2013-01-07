<?php

function past_db_views_default_views() {
  $view = new view;
  $view->name = 'past_event_log';
  $view->description = '';
  $view->tag = 'default';
  $view->base_table = 'past_event';
  $view->human_name = 'Past event log';
  $view->core = 7;
  $view->api_version = '3.0';
  $view->disabled = FALSE; /* Edit this to true to make a default view disabled initially */

  /* Display: Master */
  $handler = $view->new_display('default', 'Master', 'default');
  $handler->display->display_options['title'] = 'Past event log';
  $handler->display->display_options['access']['type'] = 'perm';
  $handler->display->display_options['access']['perm'] = 'administer past';
  $handler->display->display_options['cache']['type'] = 'none';
  $handler->display->display_options['query']['type'] = 'views_query';
  $handler->display->display_options['query']['options']['query_comment'] = FALSE;
  $handler->display->display_options['exposed_form']['type'] = 'basic';
  $handler->display->display_options['pager']['type'] = 'full';
  $handler->display->display_options['pager']['options']['items_per_page'] = '50';
  $handler->display->display_options['style_plugin'] = 'table';
  $handler->display->display_options['style_options']['columns'] = array(
    'event_id' => 'event_id',
    'severity' => 'severity',
    'timestamp' => 'timestamp',
    'module' => 'module',
    'message' => 'message',
  );
  $handler->display->display_options['style_options']['default'] = '-1';
  $handler->display->display_options['style_options']['info'] = array(
    'event_id' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => 'views-align-center',
      'separator' => '',
      'empty_column' => 0,
    ),
    'severity' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => 'views-align-center',
      'separator' => '',
      'empty_column' => 0,
    ),
    'timestamp' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'module' => array(
      'sortable' => 1,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
    'message' => array(
      'sortable' => 0,
      'default_sort_order' => 'asc',
      'align' => '',
      'separator' => '',
      'empty_column' => 0,
    ),
  );
  $handler->display->display_options['style_options']['override'] = 1;
  $handler->display->display_options['style_options']['sticky'] = 0;
  $handler->display->display_options['style_options']['empty_table'] = 0;
  /* Field: Past Event: Past event ID */
  $handler->display->display_options['fields']['event_id']['id'] = 'event_id';
  $handler->display->display_options['fields']['event_id']['table'] = 'past_event';
  $handler->display->display_options['fields']['event_id']['field'] = 'event_id';
  $handler->display->display_options['fields']['event_id']['label'] = 'ID';
  $handler->display->display_options['fields']['event_id']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['event_id']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['event_id']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['event_id']['alter']['external'] = 0;
  $handler->display->display_options['fields']['event_id']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['event_id']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['event_id']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['event_id']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['event_id']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['event_id']['alter']['more_link'] = 0;
  $handler->display->display_options['fields']['event_id']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['event_id']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['event_id']['alter']['html'] = 0;
  $handler->display->display_options['fields']['event_id']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['event_id']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['event_id']['hide_empty'] = 0;
  $handler->display->display_options['fields']['event_id']['empty_zero'] = 0;
  $handler->display->display_options['fields']['event_id']['hide_alter_empty'] = 1;
  /* Field: Past Event: Severity */
  $handler->display->display_options['fields']['severity']['id'] = 'severity';
  $handler->display->display_options['fields']['severity']['table'] = 'past_event';
  $handler->display->display_options['fields']['severity']['field'] = 'severity';
  $handler->display->display_options['fields']['severity']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['severity']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['severity']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['severity']['alter']['external'] = 0;
  $handler->display->display_options['fields']['severity']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['severity']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['severity']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['severity']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['severity']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['severity']['alter']['more_link'] = 0;
  $handler->display->display_options['fields']['severity']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['severity']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['severity']['alter']['html'] = 0;
  $handler->display->display_options['fields']['severity']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['severity']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['severity']['empty'] = 'no past logs found with selected severity';
  $handler->display->display_options['fields']['severity']['hide_empty'] = 0;
  $handler->display->display_options['fields']['severity']['empty_zero'] = 0;
  $handler->display->display_options['fields']['severity']['hide_alter_empty'] = 1;
  $handler->display->display_options['fields']['severity']['machine_name'] = 0;
  /* Field: Past Event: Module */
  $handler->display->display_options['fields']['module']['id'] = 'module';
  $handler->display->display_options['fields']['module']['table'] = 'past_event';
  $handler->display->display_options['fields']['module']['field'] = 'module';
  $handler->display->display_options['fields']['module']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['module']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['module']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['module']['alter']['external'] = 0;
  $handler->display->display_options['fields']['module']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['module']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['module']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['module']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['module']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['module']['alter']['more_link'] = 0;
  $handler->display->display_options['fields']['module']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['module']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['module']['alter']['html'] = 0;
  $handler->display->display_options['fields']['module']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['module']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['module']['hide_empty'] = 0;
  $handler->display->display_options['fields']['module']['empty_zero'] = 0;
  $handler->display->display_options['fields']['module']['hide_alter_empty'] = 1;
  /* Field: Past Event: Machine_name */
  $handler->display->display_options['fields']['machine_name']['id'] = 'machine_name';
  $handler->display->display_options['fields']['machine_name']['table'] = 'past_event';
  $handler->display->display_options['fields']['machine_name']['field'] = 'machine_name';
  $handler->display->display_options['fields']['machine_name']['label'] = 'Machine name';
  $handler->display->display_options['fields']['machine_name']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['machine_name']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['machine_name']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['machine_name']['alter']['external'] = 0;
  $handler->display->display_options['fields']['machine_name']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['machine_name']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['machine_name']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['machine_name']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['machine_name']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['machine_name']['alter']['more_link'] = 0;
  $handler->display->display_options['fields']['machine_name']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['machine_name']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['machine_name']['alter']['html'] = 0;
  $handler->display->display_options['fields']['machine_name']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['machine_name']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['machine_name']['hide_empty'] = 0;
  $handler->display->display_options['fields']['machine_name']['empty_zero'] = 0;
  $handler->display->display_options['fields']['machine_name']['hide_alter_empty'] = 1;
  /* Field: Past Event: Message */
  $handler->display->display_options['fields']['message']['id'] = 'message';
  $handler->display->display_options['fields']['message']['table'] = 'past_event';
  $handler->display->display_options['fields']['message']['field'] = 'message';
  $handler->display->display_options['fields']['message']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['message']['alter']['make_link'] = 1;
  $handler->display->display_options['fields']['message']['alter']['path'] = 'admin/reports/past/[event_id]';
  $handler->display->display_options['fields']['message']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['message']['alter']['external'] = 0;
  $handler->display->display_options['fields']['message']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['message']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['message']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['message']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['message']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['message']['alter']['more_link'] = 0;
  $handler->display->display_options['fields']['message']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['message']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['message']['alter']['html'] = 0;
  $handler->display->display_options['fields']['message']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['message']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['message']['hide_empty'] = 0;
  $handler->display->display_options['fields']['message']['empty_zero'] = 0;
  $handler->display->display_options['fields']['message']['hide_alter_empty'] = 1;
  /* Field: Past Event: Timestamp */
  $handler->display->display_options['fields']['timestamp']['id'] = 'timestamp';
  $handler->display->display_options['fields']['timestamp']['table'] = 'past_event';
  $handler->display->display_options['fields']['timestamp']['field'] = 'timestamp';
  $handler->display->display_options['fields']['timestamp']['alter']['alter_text'] = 0;
  $handler->display->display_options['fields']['timestamp']['alter']['make_link'] = 0;
  $handler->display->display_options['fields']['timestamp']['alter']['absolute'] = 0;
  $handler->display->display_options['fields']['timestamp']['alter']['external'] = 0;
  $handler->display->display_options['fields']['timestamp']['alter']['replace_spaces'] = 0;
  $handler->display->display_options['fields']['timestamp']['alter']['trim_whitespace'] = 0;
  $handler->display->display_options['fields']['timestamp']['alter']['nl2br'] = 0;
  $handler->display->display_options['fields']['timestamp']['alter']['word_boundary'] = 1;
  $handler->display->display_options['fields']['timestamp']['alter']['ellipsis'] = 1;
  $handler->display->display_options['fields']['timestamp']['alter']['more_link'] = 0;
  $handler->display->display_options['fields']['timestamp']['alter']['strip_tags'] = 0;
  $handler->display->display_options['fields']['timestamp']['alter']['trim'] = 0;
  $handler->display->display_options['fields']['timestamp']['alter']['html'] = 0;
  $handler->display->display_options['fields']['timestamp']['element_label_colon'] = 1;
  $handler->display->display_options['fields']['timestamp']['element_default_classes'] = 1;
  $handler->display->display_options['fields']['timestamp']['hide_empty'] = 0;
  $handler->display->display_options['fields']['timestamp']['empty_zero'] = 0;
  $handler->display->display_options['fields']['timestamp']['hide_alter_empty'] = 1;
  $handler->display->display_options['fields']['timestamp']['date_format'] = 'short';
  /* Sort criterion: Past Event: Timestamp */
  $handler->display->display_options['sorts']['timestamp']['id'] = 'timestamp';
  $handler->display->display_options['sorts']['timestamp']['table'] = 'past_event';
  $handler->display->display_options['sorts']['timestamp']['field'] = 'timestamp';
  $handler->display->display_options['sorts']['timestamp']['order'] = 'DESC';
  /* Contextual filter: Past Event: Past event ID */
  $handler->display->display_options['arguments']['event_id']['id'] = 'event_id';
  $handler->display->display_options['arguments']['event_id']['table'] = 'past_event';
  $handler->display->display_options['arguments']['event_id']['field'] = 'event_id';
  $handler->display->display_options['arguments']['event_id']['default_argument_type'] = 'fixed';
  $handler->display->display_options['arguments']['event_id']['summary']['format'] = 'default_summary';
  /* Filter criterion: Past Event: Module */
  $handler->display->display_options['filters']['module']['id'] = 'module';
  $handler->display->display_options['filters']['module']['table'] = 'past_event';
  $handler->display->display_options['filters']['module']['field'] = 'module';
  $handler->display->display_options['filters']['module']['group'] = 1;
  $handler->display->display_options['filters']['module']['exposed'] = TRUE;
  $handler->display->display_options['filters']['module']['expose']['operator_id'] = 'module_op';
  $handler->display->display_options['filters']['module']['expose']['label'] = 'Module';
  $handler->display->display_options['filters']['module']['expose']['operator'] = 'module_op';
  $handler->display->display_options['filters']['module']['expose']['identifier'] = 'module';
  $handler->display->display_options['filters']['module']['expose']['required'] = 0;
  $handler->display->display_options['filters']['module']['expose']['multiple'] = FALSE;
  /* Filter criterion: Past Event: Severity */
  $handler->display->display_options['filters']['severity']['id'] = 'severity';
  $handler->display->display_options['filters']['severity']['table'] = 'past_event';
  $handler->display->display_options['filters']['severity']['field'] = 'severity';
  $handler->display->display_options['filters']['severity']['group'] = 1;
  $handler->display->display_options['filters']['severity']['exposed'] = TRUE;
  $handler->display->display_options['filters']['severity']['expose']['operator_id'] = 'severity_op';
  $handler->display->display_options['filters']['severity']['expose']['label'] = 'Severity';
  $handler->display->display_options['filters']['severity']['expose']['operator'] = 'severity_op';
  $handler->display->display_options['filters']['severity']['expose']['identifier'] = 'severity';
  $handler->display->display_options['filters']['severity']['expose']['remember'] = 1;
  $handler->display->display_options['filters']['severity']['expose']['multiple'] = 1;
  $handler->display->display_options['filters']['severity']['expose']['reduce'] = 0;
  /* Filter criterion: Past Event: Message */
  $handler->display->display_options['filters']['message']['id'] = 'message';
  $handler->display->display_options['filters']['message']['table'] = 'past_event';
  $handler->display->display_options['filters']['message']['field'] = 'message';
  $handler->display->display_options['filters']['message']['operator'] = 'contains';
  $handler->display->display_options['filters']['message']['group'] = 1;
  $handler->display->display_options['filters']['message']['exposed'] = TRUE;
  $handler->display->display_options['filters']['message']['expose']['operator_id'] = 'message_op';
  $handler->display->display_options['filters']['message']['expose']['label'] = 'Message';
  $handler->display->display_options['filters']['message']['expose']['operator'] = 'message_op';
  $handler->display->display_options['filters']['message']['expose']['identifier'] = 'message';
  $handler->display->display_options['filters']['message']['expose']['required'] = 0;
  $handler->display->display_options['filters']['message']['expose']['remember'] = 1;
  $handler->display->display_options['filters']['message']['expose']['multiple'] = FALSE;

  /* Display: Page: Main view */
  $handler = $view->new_display('page', 'Page: Main view', 'past_logs_table');
  $handler->display->display_options['display_description'] = 'Shows a list with all past logentries';
  $handler->display->display_options['defaults']['arguments'] = FALSE;
  $handler->display->display_options['path'] = 'admin/reports/past';
  $handler->display->display_options['menu']['type'] = 'normal';
  $handler->display->display_options['menu']['title'] = 'Past event log';
  $handler->display->display_options['menu']['description'] = 'Past event log messages';
  $handler->display->display_options['menu']['weight'] = '0';
  $handler->display->display_options['menu']['name'] = 'management';
  $handler->display->display_options['menu']['context'] = 0;

  return array($view->name => $view);
}
