<?php

/**
 * @file
 */

/**
 *
 */
function gttn_tpps_table_display($state) {

  $accession = $state['saved_values'][GTTN_PAGE_4]['accession'];
  $display = "<div class='gttn_tpps_complete_submission'><table id='gttn_tpps_$accession' style='width:-webkit-fill-available' border='1'><thead>";
  // $display .= "<tr><th>Field</th><th>Value</th>";
  $display .= "</thead><tbody>";

  $flat = gttn_tpps_flatten($state);

  foreach ($flat as $key => $val) {
    if ($key[0] !== '#') {
      $key_str = filter_xss(check_plain($key));
      $val_str = filter_xss(check_plain($val));

      if (preg_match('/File/', $key) and ($file_name = file_load($val)->filename)) {
        // Create links to files.
        $file_url = check_plain(file_create_url(file_load($val)->uri));
        $val_str = "<a href='$file_url' target='blank'>$file_name</a>";
      }

      $display .= "<tr><td>$key_str</td><td>$val_str</td></tr>";
    }
  }

  $display .= "</tbody></table></div><br>";

  return $display;
}
