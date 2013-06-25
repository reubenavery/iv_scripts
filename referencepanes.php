<?php
/*
 * Upaditing all channel landing page reference panes which are missing view arguments.
 */

require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

function __get_view($name) {
  static $views;
  if (!isset($views[$name])) {
    $views[$name] = views_get_view($name);
  }
  return $views[$name];
}

ctools_include('export');

$channels = array(
  'parenting' => array(
    'nid' => 340724,
    'tid' => 30925,
  ),
  'health' => array(
    'nid' => 340722,
    'tid' => 20439,
  ),
  'entertainment' => array(
    'nid' => 340720,
    'tid' => 12712,
  ),
  'beauty' => array(
    'nid' => 340723,
    'tid' => 31351,
  ),
  'food' => array(
    'nid' => 340708,
    'tid' => 13530,
  ),
  'home' => array(
    'nid' => 340718,
    'tid' => 38626,
  ),
  'love' => array(
    'nid' => 340709,
    'tid' => '30925,20439',
  ),
);

foreach ($channels as $channel => $item) {
  $nid = $item['nid'];
  $tid = $item['tid'];
  $node = node_load($nid);
  $panelizer = panelizer_load_node_panelizer($node);
  $panel = panels_load_display($panelizer->did);
  $names = array();
  foreach ($panel->content as $pane) {
    if ($pane->type == 'reference') {
      $names[] = $pane->subtype;
    }
  }
  $panes = ctools_export_load_object('reference_pane', 'names', $names);
  $all_panes = array_merge((array)$all_panes, $panes);

  foreach ($panes as $id => $pane) {
  //  kpr($pane); die();
    if (!is_array($pane->settings)) {
      continue;
    }
    $split = explode('_', $id);
    $settings = $pane->settings;
    $counts[$channel]++;

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
      print '<strong style="color:blue">SKIPPED (no arguments): '. $pane->name .' --- '. $settings['view_name'] .'</strong><br>';
    }

    $pane->settings = $settings;
    ctools_export_crud_save('reference_pane', $pane);

  }

}


// Rebuild and export the template_simplification_feature to contain all the above detected reference panes.
function export_feature($stub, $dependencies, $module_name = NULL, $directory = NULL) {
  $directory = isset($directory) ? $directory : 'sites/all/modules/' . $module_name;
  if (!is_dir($directory)) {
    mkdir($directory);
  }

  drupal_flush_all_caches();
  module_load_include('inc', 'features', 'features.export');

  $export = features_populate($stub, $dependencies, $module_name);

  if (!feature_load($module_name)) {
    $export['name'] = $module_name;
  }

  $files = features_export_render($export, $module_name, TRUE);

  foreach ($files as $extension => $file_contents) {
    if (!in_array($extension, array('module', 'info'))) {
      $extension .= '.inc';
    }
    file_put_contents("{$directory}/{$module_name}.$extension", $file_contents);
  }

}

$feature = feature_load('template_simplification_feature', TRUE);

$feature->info['features']['reference_pane'] = array();
foreach ($all_panes as $id => $pane) {
  $feature->info['features']['reference_pane'][] = $id;
}

export_feature($feature->info['features'], $feature->info['dependencies'], $feature->name, dirname($feature->filename));