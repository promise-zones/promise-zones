<?php

define('FILE_HEADING_SIZE', 6);

$dataFile = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'data', 'county-unemployment-data.txt']);
$data = fopen($dataFile, 'r');

for ($i = 0; $i < FILE_HEADING_SIZE; $i++) {
  // seek to the data
  fgets($data);
}

$headers = ['luas_area_code', 'fips_state', 'fips_county', 'county_name', 'period', 'civ_labor_force', 'employed', 'ue_level', 'ue_rate'];
$parsedData = [];
while ($line = fgets($data)) {
  $columns = array_map('trim', explode('|', $line));
  if (count($columns) !== count($headers)) {
    break;
  }
  $countyData = array_combine($headers, $columns);
  foreach (['civ_labor_force', 'employed', 'ue_level'] as $key) {
    $countyData[$key] = (int)str_replace(',', '', $countyData[$key]);
  }
  settype($countyData['ue_rate'], 'float');
  $parsedData[] = $countyData;
}

fwrite(STDOUT, json_encode($parsedData, JSON_PRETTY_PRINT));
