#!/usr/bin/env /Applications/MAMP/bin/php/php5.3.20/bin/php
<?php

require_once './includes/bootstrap.inc';
drupal_bootstrap(DRUPAL_BOOTSTRAP_FULL);

function rewrite_quote($str) {
  return str_replace(' ', '\ ', preg_quote($str, '/'));
}

ini_set('auto_detect_line_endings', true);

$opts = getopt("");

$ignore_hosts = true;

if (true) {
  db_query("truncate redirects");
  $lines = file('301 Redirect 6-25.txt');
  $i = 0;
  foreach ($lines as $line) {
    $parsed =  str_getcsv(trim($line), "\t", '"');
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

    $response_code = 301;

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
    print "\n";
    print 'RewriteCond %{HTTP_HOST} ^'. rewrite_quote($old_host) ."$ [NC]\n";
    $last_host = $old_host;
    $last_query = FALSE;
  }

  if ($old_query !== $last_query) {
    if ($old_query) {
      print "\n";
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
    print 'RewriteRule ^'. rewrite_quote($old_path, '/') .'$ /'. $new_path .' [R'. $response_code .',L]'. "\n";
  }
  else {
    print 'RewriteRule ^'. rewrite_quote($old_path, '/') .'$ http://'. $new_host .'/'. $new_path .' [R'. $response_code .',L]'. "\n";
  }
}
