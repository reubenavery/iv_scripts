#!/usr/bin/env /Applications/MAMP/bin/php/php5.3.20/bin/php
<?php

// Hardcoded arguments for now:

$filename = '7-3-13 Batch.xlsx';
$ignore_hosts = true;
$skip_first = true;

// /args

if (!file_exists($filename)) {
  die("\n". 'Could not open '. $filename ."\n");
}

$ext = pathinfo($filename, PATHINFO_EXTENSION);

require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

function rewrite_quote($str) {
  $str = str_replace(' ', '\ ', preg_quote($str, '/'));
  $str = preg_replace('/(?:%20|\+|\ )/', '(?:%20|\+|\ )', $str);
  return $str;
}

ini_set('auto_detect_line_endings', true);

if ($filename) {
  db_query("truncate redirects");

  if ($ext == 'xlsx') {
    require_once 'simplexlsx.class.php';
    $xlsx = new SimpleXLSX($filename);
    $lines = $xlsx->rows();
  }
  else {
    $lines = file($filename);
  }

  $i = 0;
  foreach ($lines as $line) {
    if ($skip_first) {
      $skip_first = false;
      continue;
    }

    switch ($ext) {
      case 'xlsx':
        $parsed = $line;
        break;
      default:
        // default file format is csv
        $parsed =  str_getcsv(trim($line), "\t", '"');
    }

    $i++;

    $url = parse_url($parsed[0]);

    $old_host = $url['host'];
    $old_path = trim($url['path'], '/');
    $old_query = $url['query'];
    $old_fragment = $url['fragment'];

    $url = parse_url($parsed[1]);

    $new_host = $url['host'];
    $new_path = trim($url['path'], '/');
    $new_query = $url['query'];
    $new_fragment = $url['fragment'];

    $response_code = (is_numeric($parsed[2])) ? $parsed[2] : 301;

    $data = array(
      $old_host,
      $old_path,
      $old_query,
      $old_fragment,
      $new_host,
      $new_path,
      $new_query,
      $new_fragment,
      $response_code,
    );

    db_query("INSERT {redirects} (old_host, old_path, old_query, old_fragment, new_host, new_path, new_query, new_fragment, response_code) VALUES ('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', %d)", $data);
  }
}

$query = db_query("SELECT DISTINCT old_host, old_path, old_query, new_host, new_path, new_query, response_code FROM {redirects} ORDER BY old_host, old_query, old_path");
$last_host = FALSE;
$last_query = FALSE;

print "RewriteEngine On\n";

while ($row = db_fetch_array($query)) {
  extract($row);

  if ($old_host !== $last_host && !$ignore_hosts) {
//    print "\n";
    print 'RewriteCond %{HTTP_HOST} ^'. rewrite_quote($old_host) ."$ [NC]\n";
    $last_host = $old_host;
    $last_query = FALSE;
  }

  if ($old_query !== $last_query) {
    if ($old_query) {
//      print "\n";
      if (!$ignore_hosts) { print 'RewriteCond %{HTTP_HOST} ^'. rewrite_quote($old_host) ."$ [NC]\n"; }
      print 'RewriteCond %{QUERY_STRING} ^'. rewrite_quote($old_query, '/') ."$\n";
    }
    $last_query = $old_query;
  }

//  $old_path = urlencode(urldecode($old_path));
//  $old_path = ($old_path == '') ? '(.*)' : '^'. rewrite_quote($old_path) .'(.*)';
  $new_path = str_replace(' ', '+', $new_path);
  $response_code = ($response_code) ? '='. $response_code : '';

  if ($ignore_hosts) {
    print 'RewriteRule ^\/??'. rewrite_quote($old_path, '/') .'$ /'. $new_path .' [R'. $response_code .',L]'. "\n";
  }
  else {
    print 'RewriteRule ^\/??'. rewrite_quote($old_path, '/') .'$ http://'. $new_host .'/'. $new_path .' [R'. $response_code .',L]'. "\n";
  }
}