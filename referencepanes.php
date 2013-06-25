<?php
/*
 * Upaditing all channel landing page reference panes which are missing view arguments.
 */

require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

//kpr(views_get_view('cp_vertical_link_list'));

function __get_view($name) {
  static $views;
  if (!isset($views[$name])) {
    $views[$name] = views_get_view($name);
  }
  return $views[$name];
}

ctools_include('export');
$panes = ctools_export_load_object('reference_pane');

foreach ($panes as $id => $pane) {
//  kpr($pane); die();
  if (!is_array($pane->settings)) {
    continue;
  }
  $split = explode('_', $id);
  $settings = $pane->settings;
  $counts[$split[0]]++;

  switch ($split[0]) {
    case 'parenting':
      $tid = 30925;
      break;
    case 'health':
      $tid = 20439;
      break;
    case 'entertainment':
      $tid = 12712;
      break;
    case 'beauty':
      $tid = 31351;
      break;
    case 'food':
      $tid = 13530;
      break;
    case 'home':
      $tid = 38626;
      break;
    case 'love':
//      $tid = 43349;
      $tid = '30925,20439';
      break;
    default:
      $tid = 0;
  }

  if (!$tid) { continue; }

  list($view_name,$view_display) = explode(':', $settings['view_display']);
  if (empty($view_name) || empty($view_display)) { kpr($pane); die('FFFUU'); }
  $counts2[$view_name]++;
  $view = __get_view($view_name);

  if (isset($view->display[$view_display]->display_options['argument_input']['tid']) || isset($view->display['default']->display_options['arguments']['tid'])) {
    if (empty($settings['view_args']) || ($settings['view_args'] != $tid && $split[0] == 'love')) {
      $settings['view_args'] = $tid;
      print '<strong style="color:red">FIXED: '. $pane->name .' fixed</strong><br>';
    }
    else {
      print '<span style="color:green">SKIPPED: '. $pane->name .' already fixed<br></span>';
    }
  }
  else {
    kpr(array($view->display['default']->display_options, $view->display[$view_display]->display_options, $pane->settings));
    print '<strong style="color:blue">SKIPPED: '. $pane->name .' --- '. $settings['view_name'] .'</strong><br>';
  }

  $pane->settings = $settings;
  ctools_export_crud_save('reference_pane', $pane);
}

kpr(array($counts, $counts2));
