<?php
                                                                                                         
//                                   _                                  _                _                  
//  _   _ _ __   ___ _ __ ___  _ __ | | ___  _   _ _ __ ___   ___ _ __ | |_    _ __ __ _| |_ ___            
// | | | | '_ \ / _ \ '_ ` _ \| '_ \| |/ _ \| | | | '_ ` _ \ / _ \ '_ \| __|  | '__/ _` | __/ _ \           
// | |_| | | | |  __/ | | | | | |_) | | (_) | |_| | | | | | |  __/ | | | |_   | | | (_| | ||  __/           
//  \__,_|_| |_|\___|_| |_| |_| .__/|_|\___/ \__, |_| |_| |_|\___|_| |_|\__|  |_|  \__,_|\__\___|           
//                            |_|            |___/                                                          
//
$countyUnemploymentDataFile = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'data', 'county-unemployment-data.txt']);
$data = fopen($countyUnemploymentDataFile, 'r');
for ($i = 0; $i < 6; $i++) {
  // seek to the data
  fgets($data);
}
$headers = ['luas_area_code', 'fips_state', 'fips_county', 'county_name', 'period', 'civ_labor_force', 'employed', 'ue_level', 'ue_rate'];
$areas = [];
while ($line = fgets($data)) {
  $columns = array_map('trim', explode('|', $line));
  if (count($columns) !== count($headers)) {
    break;
  }
  $countyData = array_combine($headers, $columns);
  foreach (['civ_labor_force', 'employed', 'ue_level'] as $key) {
    $countyData[$key] = (int)str_replace(',', '', $countyData[$key]);
  }
  $countyData['ue_rate'] = $countyData['ue_rate'] / 100;
  $countyData['pz'] = false;
  $areas[implode('', [$countyData['fips_state'], $countyData['fips_county']])] = $countyData;
}
fclose($data);
unset($data);

$countyPovertyRateFile = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'data', 'poverty-rates-by-county.csv']);
$data = fopen($countyPovertyRateFile, 'r');

//                            _                      _       
//  _ __   _____   _____ _ __| |_ _   _    _ __ __ _| |_ ___ 
// | '_ \ / _ \ \ / / _ \ '__| __| | | |  | '__/ _` | __/ _ \
// | |_) | (_) \ V /  __/ |  | |_| |_| |  | | | (_| | ||  __/
// | .__/ \___/ \_/ \___|_|   \__|\__, |  |_|  \__,_|\__\___|
// |_|                            |___/                      
// 
$headers = fgetcsv($data);
$i = 0;
while ($line = fgetcsv($data)) {
  if (empty(array_filter($line))) {
    break;
  }
  $county = array_combine($headers, $line);
  $countyId = $county['County ID'];
  if (!array_key_exists($countyId, $areas)) {
    continue;
  }
  $areas[$countyId]['pv_rate'] = $county['All Ages in Poverty Percent'] / 100;
}
fclose($data);
unset($data);

//                          _       _   _
//  _ __   ___  _ __  _   _| | __ _| |_(_) ___  _ __
// | '_ \ / _ \| '_ \| | | | |/ _` | __| |/ _ \| '_ \
// | |_) | (_) | |_) | |_| | | (_| | |_| | (_) | | | |
// | .__/ \___/| .__/ \__,_|_|\__,_|\__|_|\___/|_| |_|
// |_|         |_|
// 
$populationFile = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'data', 'US_Population.csv']);
$data = fopen($populationFile, 'r');
$headers = fgetcsv($data);
$countyPops = [];
while ($line = fgetcsv($data)) {
  $county = array_combine($headers, $line);
  list(,$countyId) = explode('US', $line[0]);
  if (!array_key_exists($countyId, $areas)) {
    continue;
  }
  settype($county['year'], 'int');
  settype($county['population'], 'int');
  $countyPops[$countyId][$county['year']] = $county['population'];
}
fclose($data);
unset($data);
$countyPops = array_map(function ($pops) {
  return $pops[max(array_keys($pops))];
}, $countyPops);
foreach ($countyPops as $countyId => $population) {
  $areas[$countyId]['pop'] = $population;
}

//                            _                                      
//  _ __  _ __ ___  _ __ ___ (_)___  ___    _______  _ __   ___  ___ 
// | '_ \| '__/ _ \| '_ ` _ \| / __|/ _ \  |_  / _ \| '_ \ / _ \/ __|
// | |_) | | | (_) | | | | | | \__ \  __/   / / (_) | | | |  __/\__ \
// | .__/|_|  \___/|_| |_| |_|_|___/\___|  /___\___/|_| |_|\___||___/
// |_|                                                               
$promiseZonesFile = implode(DIRECTORY_SEPARATOR, [__DIR__, '..', 'data', 'promise-zones.json']);
$promiseZones = json_decode(file_get_contents($promiseZonesFile), true);
foreach ($promiseZones as $zone) {
  // just generate a random ID, since $areas is keyed by FIPS id
  $id = bin2hex(openssl_random_pseudo_bytes(8));
  $area = array_intersect_key($zone, array_flip('pv_rate', 'pop', 'ue_rate'));
  $area['area_name'] = $zone['pz_name'];
  $area['pz'] = true;
  $areas[$id] = $area;
}

$areas = array_filter($areas, function ($c) {
  return !empty($c['pv_rate']) && !empty($c['pop']) && !empty($c['ue_rate']);
});

$areas = array_map(function ($c) {
  $c['area_name'] = $c['county_name'];
  return array_intersect_key($c, array_flip(['pv_rate', 'pop', 'ue_rate', 'area_name', 'pz']));
}, $areas);

fwrite(STDOUT, json_encode(array_values($areas), JSON_PRETTY_PRINT));
