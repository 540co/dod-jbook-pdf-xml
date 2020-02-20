<?php
// php compareJbookArrays.php [jbookArraysJsonFilename1] [jbookArraysJsonFilename2]

echo "-------------------------------------------------------\n";
$file = [];
$file[$argv[1]] = json_decode(file_get_contents($argv[1]), TRUE);
$file[$argv[2]] = json_decode(file_get_contents($argv[2]), TRUE);

$jtypes = ['MasterJustificationBook','JustificationBook'];
$paths = [];

foreach (array_keys($file) as $filename) {
    $paths[$filename] = [];
    
    foreach ($jtypes as $type) {
        foreach (array_keys($file[$filename][$type]) as $fy) {
            echo "Loading ".$filename." | ".$type." | ".$fy."\n";
            
            if (isset($file[$filename][$type][$fy])) {
                $pathsPerFy = $file[$filename][$type][$fy];
                $paths[$filename] = array_values(array_unique(array_merge($paths[$filename],$pathsPerFy)));
            }
        

        }

    }
}
echo "-------------------------------------------------------\n";
echo $argv[1]." vs ".$argv[2]."\n";
echo "-------------------------------------------------------\n";
$diff = array_diff($paths[$argv[1]],$paths[$argv[2]]);
if (count($diff) == 0) {
    echo "NO DIFFERENCES FOUND.\n";
} else {
    var_dump($diff);
}













