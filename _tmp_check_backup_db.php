<?php
$path = 'C:/Users/meetm/Downloads/pcm-new-20251225T164154Z-1-001/pcm-new/database/database.sqlite';
if (!file_exists($path)) { echo "not_found\n"; exit(1);} 
$db = new SQLite3($path);
$tables = ['users','projects','data','rabs','rab_items'];
foreach ($tables as $t) {
  $q = $db->querySingle("SELECT COUNT(*) FROM $t");
  echo $t . '=' . $q . PHP_EOL;
}
