<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/540co/xml-tools/src/Xmltools.php';
//use FiveFortyCo\Xmlpipeline\Xmltools;

ini_set('memory_limit', -1);

function rglob($pattern, $flags = 0) {
  $files = glob($pattern, $flags);

  foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
      $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
  }
  return $files;
}

$longOptions = [
  'search:'
];

$options = getopt('', $longOptions);

$searchPattern = $options['search'];

$files = rglob($searchPattern);

$GLOBALS['paths'] = array();

function getPaths($json, $path="") {

  if (is_array($json)) {
    foreach ($json as $key=>$val) {

          if (!is_numeric($key)) {
            if (is_array($val)) {
              getPaths($val, ltrim($path.".".$key,"."));
            } else {
              getPaths($val, $path);
            }
          } else {
            $GLOBALS['paths'][] = $path;
            getPaths($val, $path);
          }

    }

  }

}

var_dump($files);

foreach ($files as $fileNum=>$file) {

  echo "Loading $file\n";
  $xml = simplexml_load_file($file);

  $json = XmlTools::xmlToArray($xml,null,array('removeNamespace'=>true));
  getPaths($json);

}

echo json_encode(array_values(array_unique($GLOBALS['paths'])));

?>
