<?php

define('FILE_HEADING_SIZE', 6);

$dataFile = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'data', 'county-unemployment-data.txt']);
$data = fopen($dataFile, 'r');

for ($i = 0; $i < FILE_HEADING_SIZE; $i++) {
  // seek to the data
  fgets($data);
}

