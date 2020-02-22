<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/540co/keboola/php-utils/src/Keboola/Utils/Exception.php';
require __DIR__ . '/540co/keboola/php-utils/src/Keboola/Utils/Exception/JsonDecodeException.php';
require __DIR__ . '/540co/keboola/php-utils/src/Keboola/Utils/Exception/NoDataFoundException.php';
require __DIR__ . '/540co/keboola/php-utils/src/Keboola/Utils/arrayToObject.php';
require __DIR__ . '/540co/keboola/php-utils/src/Keboola/Utils/buildUrl.php';
require __DIR__ . '/540co/keboola/php-utils/src/Keboola/Utils/camelize.php';
require __DIR__ . '/540co/keboola/php-utils/src/Keboola/Utils/flattenArray.php';
require __DIR__ . '/540co/keboola/php-utils/src/Keboola/Utils/formatDateTime.php';
require __DIR__ . '/540co/keboola/php-utils/src/Keboola/Utils/getDataFromPath.php';
require __DIR__ . '/540co/keboola/php-utils/src/Keboola/Utils/httpBuildUrl.php';
require __DIR__ . '/540co/keboola/php-utils/src/Keboola/Utils/isEmptyObject.php';
require __DIR__ . '/540co/keboola/php-utils/src/Keboola/Utils/isValidDateTimeString.php';
require __DIR__ . '/540co/keboola/php-utils/src/Keboola/Utils/jsonDecode.php';
require __DIR__ . '/540co/keboola/php-utils/src/Keboola/Utils/objectToArray.php';
require __DIR__ . '/540co/keboola/php-utils/src/Keboola/Utils/replaceDates.php';
require __DIR__ . '/540co/keboola/php-utils/src/Keboola/Utils/replaceDatesInArray.php';
require __DIR__ . '/540co/keboola/php-utils/src/Keboola/Utils/sanitizeUtf8.php';
require __DIR__ . '/540co/keboola/json-parser/src/Keboola/Json/Exception/JsonParserException.php';
require __DIR__ . '/540co/keboola/json-parser/src/Keboola/Json/Exception/NoDataException.php';
require __DIR__ . '/540co/keboola/json-parser/src/Keboola/Json/Analyzer.php';
require __DIR__ . '/540co/keboola/json-parser/src/Keboola/Json/Cache.php';
require __DIR__ . '/540co/keboola/json-parser/src/Keboola/Json/CsvRow.php';
require __DIR__ . '/540co/keboola/json-parser/src/Keboola/Json/Parser.php';
require __DIR__ . '/540co/keboola/json-parser/src/Keboola/Json/Struct.php';
require __DIR__ . '/540co/keboola/csv/src/Keboola/Csv/CsvFile.php';
require __DIR__ . '/540co/keboola/csv/src/Keboola/Csv/Exception.php';
require __DIR__ . '/540co/keboola/csv/src/Keboola/Csv/InvalidArgumentException.php';
require __DIR__ . '/540co/keboola/php-csvtable/src/Keboola/CsvTable/Table.php';
require __DIR__ . '/540co/keboola/php-temp/src/Keboola/Temp/Temp.php';
require __DIR__ . '/540co/xml-tools/src/Xmltools.php';
require __DIR__ . '/540co/CsvOutput.php';
use Keboola\Json\Parser;


$GLOBALS['jbookArrayPaths'] = array();
$GLOBALS['jbookArrayPaths']['MasterJustificationBook'] = array();
$GLOBALS['jbookArrayPaths']['JustificationBook'] = array();
$GLOBALS['jbookArrayPaths']['FilesAnalyzed'] = array();

date_default_timezone_set ('GMT');
ini_set('memory_limit', -1);

$longOptions = [
    'step:',
    'jbook-list:',
    'rglob-pattern:',
    'resource-type:'
];

$options = getopt('', $longOptions);

echo "\n\n";
switch ($options['step']) {
    case '0-download-jbooks':
        echo "=========================================================\n";
        echo "[0-download-jbooks]\n";
        echo "=========================================================\n";
        if (!isset($options['jbook-list'])) {
            echo "ERROR: jbook-list must be provided => example: --jbook-list 2021_jbook_list.json\n\n";
            die;
        }
        downloadJbooks($options['jbook-list']);
    break;

    case '1-copy-jbook-xml-to-single-folder':
        echo "=========================================================\n";
        echo "[1-copy-jbook-xml-to-single-folder]\n";
        echo "=========================================================\n";
        copyJbookXmlToSingleFolder();
    break;

    case '2-determine-jbook-array-paths':
        echo "=========================================================\n";
        echo "[2-determine-jbook-array-paths]\n";
        echo "=========================================================\n";
        if (isset($options['rglob-pattern'])) {
            determineJbookArrayPaths($options['rglob-pattern']);
        } else {
            determineJbookArrayPaths('*.xml');
        }
    break;

    case '3-convert-xml-to-json':
        echo "=========================================================\n";
        echo "[3-convert-xml-to-json]\n";
        echo "=========================================================\n";
        if (isset($options['rglob-pattern'])) {
            convertXmlToJson($options['rglob-pattern']);
        } else {
            convertXmlToJson('*.xml');
        }

    break;

    case '4-process-json-docs':
        echo "=========================================================\n";
        echo "[4-process-json-docs]\n";
        echo "=========================================================\n";
        if (isset($options['rglob-pattern'])) {
            processJsonDocs($options['rglob-pattern']);
        } else {
            processJsonDocs('*.json');
        }
    break;

    case '5-json-to-csv':
        echo "=========================================================\n";
        echo "[5-json-to-csv]\n";
        echo "=========================================================\n";
        if (
          $options['resource-type'] == 'procurement-lineitems' ||
          $options['resource-type'] == 'rdte-programelements')
        {
          jsonToCsv($options['resource-type']);
        }

    break;

    case '6-profile-csv':
        echo "=========================================================\n";
        echo "[6-profile-csv]\n";
        echo "=========================================================\n";
        if (
          $options['resource-type'] == 'procurement-lineitems' ||
          $options['resource-type'] == 'rdte-programelements')
        {
          profileCsv($options['resource-type']);
        }

    break;

    case '6-csv-to-zip':
        echo "=========================================================\n";
        echo "[6-csv-to-zip]\n";
        echo "=========================================================\n";
        if (
          $options['resource-type'] == 'procurement-lineitems' ||
          $options['resource-type'] == 'rdte-programelements')
        {
          csvToZip($options['resource-type']);
        }

    break;

}

function profileCsv($resourceType) {

  $sourcePaths = [];
  $sourcePaths['procurement-lineitems'] = './4-csv-procurement-lineitems';
  $sourcePaths['rdte-programelements'] = './4-csv-rdte-programelements';

  $schemaDetails = json_decode(file_get_contents($sourcePaths[$resourceType]."/".$resourceType.".json"), TRUE);

  $budgetYears = [];
  echo "Determining total list of budget years\n";
  foreach ($schemaDetails as $k=>$v) {
    echo ".";
    $fileName = $v['filename'];
    $rows = json_decode(file_get_contents($sourcePaths[$resourceType]."/".$fileName."-j.json"), TRUE);


    foreach ($rows as $rk=>$rv) {
      $budgetYears[] = $rv['@BUDGET_YEAR'];
    }

    $budgetYears = array_values(array_unique($budgetYears));
    sort($budgetYears);

  }
  echo "\n";

  echo "Init profile object\n";
  $profile = [];
  $profileYearsInit = [];
  foreach ($budgetYears as $v) {
    $profileYearsInit[$v] = false;
  }

  foreach ($schemaDetails as $k=>$v) {
    foreach ($v['columns'] as $kc=>$vc) {
      $profile[$k][$vc] = $profileYearsInit;
    }
  }


  echo "Analyzing columns per file\n";
  foreach ($schemaDetails as $k=>$v) {
    $tableName = $k;
    $fileName = $v['filename'];
    echo "\n".$fileName."=>\n";
    $rows = json_decode(file_get_contents($sourcePaths[$resourceType]."/".$fileName."-j.json"), TRUE);

    foreach ($rows as $rk=>$rv) {
      echo "[".$rk."]";
      $budgetYear = $rv['@BUDGET_YEAR'];

      foreach ($v['columns'] as $ck=>$cv) {
        if (strlen($rv[$cv]) > 0) {
          $profile[$tableName][$cv][$budgetYear] = true;
        }
      }

    }

  }

  echo "Write profle\n";
  $csvRows = [];
  foreach ($profile as $tableName=>$columns) {
    foreach ($columns as $column=>$years) {
      $csvRow = [];
      $csvRow['tableName'] = $tableName;
      $csvRow['columnName'] = $column;
      foreach ($years as $year=>$yearFlag) {
        $csvRow[$year] = ($yearFlag) ? 'X' : '';
      }
      $csvRows[] = $csvRow;
    }
  }

  $profileFile = fopen($sourcePaths[$resourceType]."/profile.csv", "w");

  fwrite($profileFile, implode(',',array_keys($csvRows[0])));
  fwrite($profileFile,"\n");

  foreach ($csvRows as $k=>$v) {
    fwrite($profileFile, implode(',',$v));
    fwrite($profileFile,"\n");
  }

  fclose($profileFile);












  //var_dump($schemaDetails);


}

function loadCsv($file) {
    $csvrows = array();

    $header = null;
    $handle = fopen($file, "r");

    while(($row = fgetcsv($handle)) !== false){

        if ($header === null) {
            $header = $row;
            continue;
        }

        for($i = 0; $i<count($row); $i++){
            $newRow[$header[$i]] = $row[$i];
        }

        $csvrows[] = $newRow;
    }

    return $csvrows;
}




function csvToZip($resourceType) {
  $targetPaths = [];
  $targetPaths['procurement-lineitems'] = './4-csv-procurement-lineitems';
  $targetPaths['rdte-programelements'] = './4-csv-rdte-programelements';

  $fileList = rglob($targetPaths[$resourceType].'/*.csv');
  sort($fileList);
  $fileCount = count($fileList);

  $rows = [];

  foreach ($fileList as $fileIdx=>$filePath) {
      echo "[".($fileIdx+1)."/".$fileCount."]\n";
      echo "< Compressing $filePath>\n";

      $zipFilename = substr($filePath,0,-4).'.zip';
      $zipCmd = 'zip '.$zipFilename.' '.$filePath;
      $rmCmd = 'rm '.$filePath;

      echo "EXEC: ".$zipCmd."\n";
      exec($zipCmd);
      echo "EXEC: ".$rmCmd."\n";
      exec($rmCmd);

  }

  $fileList = rglob($targetPaths[$resourceType].'/*.json');
  sort($fileList);
  $fileCount = count($fileList);

  $rows = [];

  foreach ($fileList as $fileIdx=>$filePath) {
      echo "[".($fileIdx+1)."/".$fileCount."]\n";
      echo "< Compressing $filePath>\n";

      $zipFilename = substr($filePath,0,-5).'.zip';
      $zipCmd = 'zip '.$zipFilename.' '.$filePath;
      $rmCmd = 'rm '.$filePath;

      echo "EXEC: ".$zipCmd."\n";
      exec($zipCmd);
      echo "EXEC: ".$rmCmd."\n";
      exec($rmCmd);

  }

}

function generateCsvDocs() {


    $sourcePaths = [];
    $sourcePaths['procurement-lineitems'] = './4-csv-procurement-lineitems';
    $sourcePaths['rdte-programelements'] = './4-csv-rdte-programelements';

}

function findParentInfo($tables, $jsonParentIdInfo, $lastTableParentFound, $lastParentRowIdx) {
    $parent = [];

    // first look in last parent since most likely there

    if ($lastTableParentFound !== null) {

      echo "look in last table => [".$lastTableParentFound."] starting at [rowIdx = ".$lastParentRowIdx."]";

      $rowIdx = ($lastParentRowIdx-1);
      for ($rowIdx; $rowIdx < count($tables[$lastTableParentFound]); $rowIdx++) {

        foreach ($tables[$lastTableParentFound][$rowIdx] as $rowField => $rowFieldVal) {
            if ($rowFieldVal == $jsonParentIdInfo['JSON_parentId'] && $rowField !== 'JSON_parentId') {
                if (strpos(str_replace(".","_",$jsonParentIdInfo['childTableName']),$rowField) !== FALSE) {
                    $parent[] = array(
                        'parentTableName' => $lastTableParentFound,
                        'parentColumnName' => $rowField,
                        'parentRowId' => $tables[$lastTableParentFound][$rowIdx]['@ROWID'],
                        'parentRowIdx' => $rowIdx
                    );
                    echo "[found at row num = ".$rowIdx." (out of ".count($tables[$lastTableParentFound])." rows)]\n";
                    echo "\n";
                    return $parent[0];

                }
            }
        }

      }

      /*
      foreach ($tables[$lastTableParentFound] as $rowIdx => $row) {

            foreach ($row as $rowField => $rowFieldVal) {
                if ($rowFieldVal == $jsonParentIdInfo['JSON_parentId'] && $rowField !== 'JSON_parentId') {
                    if (strpos(str_replace(".","_",$jsonParentIdInfo['childTableName']),$rowField) !== FALSE) {
                        $parent[] = array(
                            'parentTableName' => $lastTableParentFound,
                            'parentColumnName' => $rowField,
                            'parentRowId' => $row['@ROWID'],
                            'parentRowIdx' => $rowIdx
                        );
                        echo "[found at row num = ".$rowIdx." (out of ".count($tables[$lastTableParentFound])." rows)]\n";
                        echo "\n";
                        return $parent[0];

                    }
                }
          }
      }
      */


    }


    echo "\n\n*****didn't find - starting from beginning => [".$lastTableParentFound."]";

    foreach ($tables as $tableName => $tableRows) {
      echo "[".$tableName."]";
      foreach ($tableRows as $rowIdx => $row) {

            foreach ($row as $rowField => $rowFieldVal) {
                if ($rowFieldVal == $jsonParentIdInfo['JSON_parentId'] && $rowField !== 'JSON_parentId') {
                    if (strpos(str_replace(".","_",$jsonParentIdInfo['childTableName']),$rowField) !== FALSE) {
                        $parent[] = array(
                            'parentTableName' => $tableName,
                            'parentColumnName' => $rowField,
                            'parentRowId' => $row['@ROWID'],
                            'parentRowIdx' => $rowIdx
                        );
                        echo "[found at row num = ".$rowIdx." (out of ".count($tables[$tableName])." rows)]\n";
                        echo "\n";
                        return $parent[0];
                    }
                }
            }
        }

    }

    if (count($parent) > 1) {
        echo "ERROR - PARENT COUNT > 1\n";
        die;
    }
    echo "\n";
    return $parent[0];

}

function jsonToCsv($resourceType) {

    $sourcePaths = [];
    $sourcePaths['rdte-programelements'] = './3-json-rdte-programelements';
    $sourcePaths['procurement-lineitems'] = './3-json-procurement-lineitems';

    $targetPaths = [];
    $targetPaths['procurement-lineitems'] = './4-csv-procurement-lineitems';
    $targetPaths['rdte-programelements'] = './4-csv-rdte-programelements';

    $targetPath = $targetPaths[$resourceType];
    $sourcePath = $sourcePaths[$resourceType];
    $sourceIdx = $resourceType;

    echo "<Removing / creating target folders\n";
    echo "<$targetPath>\n";
    exec('rm -Rf '.$targetPath);
    mkdir($targetPath);

    echo "<Reading $sourceIdx into an array list>\n";
    sleep(1);

    $fileList = rglob($sourcePath.'/*.json');
    sort($fileList);
    $fileCount = count($fileList);

    $rows = [];

    foreach ($fileList as $fileIdx=>$filePath) {
        echo "[".($fileIdx+1)."/".$fileCount."]\n";
        echo "<$filePath>\n";
        $record = json_decode(file_get_contents($filePath));
        $rows[] = $record;
    }

    echo "<record count = ".count($rows)." >\n";

    $tables = Csv::createTables($rows);

    // Create map of JSON_parentId values
    echo "<create map of json parent id values>\n";
    $JSON_parentId_List = [];
    foreach ($tables as $tableName => $tableRows) {
        foreach ($tableRows as $rowIdx => $row) {
            if (isset($row['JSON_parentId'])) {
                $JSON_parentId_List[] = array(
                    'JSON_parentId' => $row['JSON_parentId'],
                    'childTableName' => $tableName,
                    'childRowId' => $row['@ROWID'],
                    'childRowIdx' => $rowIdx
                );
            }
        }
    }

    // Lookup parent(s)
    foreach ($JSON_parentId_List as $k=>$v) {
        echo "findParentInfo($k / ".count($JSON_parentId_List).")\n";
        if ($k > 0) {
          $JSON_parentId_List[$k] = array_merge($v,
          findParentInfo(
            $tables,
            $v,
            $JSON_parentId_List[($k-1)]['parentTableName'],
            $JSON_parentId_List[($k-1)]['parentRowIdx']
          ));
        } else {
          $JSON_parentId_List[$k] = array_merge($v, findParentInfo($tables, $v, null, null));
        }

    }

    file_put_contents($targetPaths[$sourceIdx]."/JSON_parentId_List.json", json_encode($JSON_parentId_List, JSON_PRETTY_PRINT));

    // Transform $tables
    $parentToChildColumnNames = [];

    foreach ($JSON_parentId_List as $k=>$v) {

        // Add @PARENTROWID to child row
        $tables[$v['childTableName']][$v['childRowIdx']]['@PARENTROWID'] = $v['parentRowId'];

        // Add @PARENT to child row
        $tables[$v['childTableName']][$v['childRowIdx']]['@PARENT'] = $v['parentTableName'];

        // Change name of JSON_parentID to '_CHILDID'
        $tables[$v['childTableName']][$v['childRowIdx']]['_@CHILDID'] =
          $tables[$v['childTableName']][$v['childRowIdx']]['JSON_parentId'];

        //unset($tables[$v['childTableName']][$v['childRowIdx']]['JSON_parentId']);

        // Remove [parentColumnName] from parent row
        // Change name of `parentColumnName` to `_parentColumnName`
        $tables[$v['parentTableName']][$v['parentRowIdx']]['_@'.$v['childTableName']] =
          $tables[$v['parentTableName']][$v['parentRowIdx']][$v['parentColumnName']];

          if (!(isset($parentToChildColumnNames[$v['parentTableName']]))) {
            $parentToChildColumnNames[$v['parentTableName']] =[];
          }
          $parentToChildColumnNames[$v['parentTableName']][] = '_@'.$v['childTableName'];
          $parentToChildColumnNames[$v['parentTableName']] = array_values(array_unique($parentToChildColumnNames[$v['parentTableName']]));

        //unset($tables[$v['parentTableName']][$v['parentRowIdx']][$v['parentColumnName']]);

    }
    echo "\n";

    foreach ($parentToChildColumnNames as $tableName=>$parentColumnNames) {
      foreach ($tables[$tableName] as $rowIdx=>$rowFieldVals) {
        foreach ($parentColumnNames as $k=>$parentColumnName) {
          if (!(isset($rowFieldVals[$parentColumnName]))) {
            $tables[$tableName][$rowIdx][$parentColumnName] = '';
            echo "[$tableName][$rowIdx][$parentColumnName]+";
          }
        }
      }
    }

    foreach ($JSON_parentId_List as $k=>$v) {
      unset($tables[$v['childTableName']][$v['childRowIdx']]['JSON_parentId']);
      unset($tables[$v['parentTableName']][$v['parentRowIdx']][$v['parentColumnName']]);
    }

    // Ensure [parentColumnName] is removed from ALL rows regardless of if it had a parent
    // to ensure consistent columns
    $listOfAllParentColumns = [];
    foreach ($JSON_parentId_List as $k=>$v) {
      if (!isset($listOfAllParentColumns[$v['parentTableName']])) {
        $listOfAllParentColumns[$v['parentTableName']] = [];
      }
      $listOfAllParentColumns[$v['parentTableName']][] = $v['parentColumnName'];
      $listOfAllParentColumns[$v['parentTableName']] = array_values(array_unique($listOfAllParentColumns[$v['parentTableName']]));
    }

    foreach ($listOfAllParentColumns as $tableName=>$parentColumnNames) {
      foreach ($tables[$tableName] as $rowIdx=>$rowFieldVals) {
        foreach ($parentColumnNames as $k=>$parentColumnName) {
          if (isset($rowFieldVals[$parentColumnName])) {
            unset($tables[$tableName][$rowIdx][$parentColumnName]);
          }
        }
      }
    }


    // Build parent / child relationship map
    $parentChildMap = [];
    $childParentMap = [];
    foreach ($JSON_parentId_List as $k=>$v) {
        if (!(isset($parentChildMap[$v['parentTableName']]))) {
            $parentChildMap[$v['parentTableName']] = [];
        }
        $parentChildMap[$v['parentTableName']][] = $v['childTableName'];
        $parentChildMap[$v['parentTableName']] = array_values(array_unique($parentChildMap[$v['parentTableName']]));

        if (!(isset($childParentMap[$v['childTableName']]))) {
            $childParentMap[$v['childTableName']] = [];
        }
        $childParentMap[$v['childTableName']][] = $v['parentTableName'];
        $childParentMap[$v['childTableName']] = array_values(array_unique($childParentMap[$v['childTableName']]));
    }



    // Generate schema details prior to writing to disk
    $schemaDetails = [];
    foreach ($tables as $tableName => $tableRows) {
        $schemaDetails[$tableName] = [];

        if (strlen($tableName) > 240) {
            $schemaDetails[$tableName]['filename'] = "~".substr($tableName,-240).".csv";
        } else {
            $schemaDetails[$tableName]['filename'] = $tableName.".csv";
        }

        $schemaDetails[$tableName]['count'] = count($tableRows);
        $schemaDetails[$tableName]['columns'] = array_keys($tableRows[0]);
        if ($childParentMap[$tableName] == null) {
            $schemaDetails[$tableName]['parentTables'] = [];
        } else {
            $schemaDetails[$tableName]['parentTables'] = $childParentMap[$tableName];
        }

        if ($parentChildMap[$tableName] == null) {
            $schemaDetails[$tableName]['childTables'] = [];
        } else {
            $schemaDetails[$tableName]['childTables'] = $parentChildMap[$tableName];
        }

    }

    // Write CSV files / summary to disk
    foreach ($tables as $tableName=>$tableRows) {
        $csvSummaryFilename = $sourceIdx."-summary.json";
        echo "<Writing CSV details summary - ".$csvSummaryFilename.">";
        file_put_contents($targetPaths[$sourceIdx]."/".$csvSummaryFilename, json_encode($schemaDetails, JSON_PRETTY_PRINT));
        echo "<Writing CSV - ".$schemaDetails[$tableName]['filename']." - ".$tableName.">\n";
        Csv::writeCsv($targetPaths[$sourceIdx]."/".$schemaDetails[$tableName]['filename'], $tableRows);
    }

    // Generate docs
    $readme = [];
    $readme[] = '_Generated on '.date(DateTime::ISO8601).'_';
    $readme[] = '';

    foreach ($schemaDetails as $k=>$v) {
        $readme[] = '';
        $readme[] = '##'.$k;
        $readme[] = '```';
        $readme[] = 'filename => '.$v['filename'];
        $readme[] = '# of rows => '.$v['count'];
        $readme[] = '# of rows => '.count($v['columns']);
        $readme[] = '```';

        $readme[] = '| Column |';
        $readme[] = '|--------|';
        foreach ($v['columns'] as $col) {
            $readme[] = '| '.str_replace("_","\_",$col).' |';
        }

    }
    echo "<Writing README for $sourceIdx>\n";
    file_put_contents($targetPaths[$sourceIdx].'/README.md',implode($readme,"\n"));

    $digraph = [];
    $digraph[] = "digraph {";
    $digraph[] = "  graph [pad=\"0.5\", nodesep=\"0.5\", ranksep=\"2\"];\n";
    $digraph[] = "  node [shape=plain]\n";
    $digraph[] = "  rankdir=LR;\n";
    $digraph[] = "";
    $digraph[] = "";

    foreach ($schemaDetails as $tableName=>$v) {
        $dotTable = "\"".$tableName."\" [label=<\n";
        $dotTable .= "  <table border=\"0\" cellborder=\"1\" cellspacing=\"0\">\n";
        $dotTable .= "  <tr><td port=\"0\"><b><i>".$tableName."</i></b></td></tr>\n";

        foreach ($v['columns'] as $ck=>$cv) {
            $dotTable .= "  <tr><td port=\"".$cv."\">".$cv."</td></tr>\n";
        }
        $dotTable .= "  </table>>];\n\n";

        $digraph[] = $dotTable;
        $digraph[] = "";
    }

    $digraph[] = "";
    $digraph[] = "";

    foreach ($schemaDetails as $tableName=>$v) {
        foreach ($v['childTables'] as $ctk=>$ctv) {
            $digraph[] = "\"".$tableName."\":0 -> \"".$ctv."\":0";
        }
    }

    $digraph[] = "}";

    echo "<Writing SchemaDetails.json>\n";
    file_put_contents($targetPaths[$sourceIdx].'/'.$sourceIdx.'.json', json_encode($schemaDetails, JSON_PRETTY_PRINT));

    echo "<Writing ERD for $sourceIdx>\n";
    file_put_contents($targetPaths[$sourceIdx].'/'.$sourceIdx.'.dot',implode($digraph,"\n"));
    exec('dot -Tpng '.$targetPaths[$sourceIdx].'/'.$sourceIdx.'.dot -o '.$targetPaths[$sourceIdx].'/'.$sourceIdx.'.png');
    exec('dot -Tpdf '.$targetPaths[$sourceIdx].'/'.$sourceIdx.'.dot -o '.$targetPaths[$sourceIdx].'/'.$sourceIdx.'.pdf');

    echo "\n\n[done]\n";
    die;

}

function downloadJbooks($jbookList) {
    $targetPath = '0-jbook-pdf';
    echo "<Reading $jbookList>\n";
    $toc = json_decode(file_get_contents($targetPath.'/'.$jbookList));
    if ($toc === null) {
        echo "ERROR: Error reading jbook list file\n\n";
        die;
    }

    $currentDirectory = getcwd();

    $tocFileSegments = explode('_',$jbookList);
    $targetPathYear = $currentDirectory.'/'.$targetPath.'/'.$tocFileSegments[0];

    echo "<Removing folder in prep for download: $targetPathYear>\n";
    sleep(2);
    $rmCmd = 'rm -Rf '.$targetPathYear;
    exec($rmCmd);

    echo "<Preparing to download files>\n";
    sleep(2);

    foreach ($toc as $type=>$year_list) {
        foreach ($year_list as $year=>$pdf_list) {
            foreach ($pdf_list as $folder_name=>$pdf_url) {

            $folder = $targetPathYear."/".$type."/".$year."/".$folder_name;

            echo "=========================================================\n";
            echo "<Creating folder $folder>\n";
            mkdir($folder,0755,TRUE);

            if (strlen($pdf_url) == 0) {
              echo "<SKIPPING: $pdf_url => $folder>\n";

              continue;
            }

            echo "<DOWNLOAD: $pdf_url => $folder>\n";
            $pathinfo = pathinfo($pdf_url);

            $download_successful = FALSE;
            while ($download_successful == FALSE) {
                $c_cmd = 'curl --url "'.$pdf_url.'" --output "'.$folder.'/'.$pathinfo['basename'].'"';
                echo $c_cmd."\n";
                exec($c_cmd);

                if (filesize($folder.'/'.$pathinfo['basename']) > 100) {
                    $download_successful = TRUE;
                    echo "<OK (".filesize($folder.'/'.$pathinfo['basename']).")>\n";
                } else {
                    $download_successful = FALSE;
                    echo "<ERROR (".filesize($folder.'/'.$pathinfo['basename']).")>\n";
                    echo "*** WILL TRY AGAIN IN 10 seconds ***\n";
                    sleep(10);
                }

            }

            // extract attachments from PDF
            chdir($folder);

            //foreach (glob('*.[pP][dD][fF]') as $filename) {
            foreach (glob('*') as $filename) {
                echo "<Extracting attachments from $folder/$filename>\n";
                exec('qpdf --decrypt "'.$filename.'" "d_'.$filename.'"');
                exec('rm "'.$filename.'"');
                exec('mv "d_'.$filename.'" "'.$filename.'"');
                exec('pdftk "'.$filename.'" unpack_files');
            }

            // find .zzz files within the $folder and unzip files to folder with same name as filename (with _unzipped as suffix)
            foreach (glob('*.[zZ][zZ][zZ]') as $filename) {
                echo "<Unzipping $filename>\n";
                exec('unzip "'.$filename.'" -d "./'.$filename.'_unzipped"');
            }

            chdir($targetPathYear);

            echo "=========================================================\n";

            }
        }
    }

    echo "\n\n[done]\n";
    die;

}

function saveRecords($jbookRecordId, $recordType, $meta, $rows, $targetPath) {
    echo "<Saving (".count($rows).") ".$year." - ".$recordType.">\n";

    foreach ($rows as $recordIdx=>$record) {
        $recordToSave = [];
        $idFields = [];
        $idFields[] = $meta['budget_year'];
        $idFields[] = $meta['service_agency_name'];
        $idFields[] = $meta['budget_cycle'];
        $idFields[] = $meta['appropriation_code'];
        $idFields[] = $jbookRecordId;
        $idFields[] = $recordIdx;
        $id = str_replace(' ', '', preg_replace("/[^a-z0-9\_\-\.]/i",'',implode('-',$idFields)));
        $recordToSave['id'] = $id;
        $recordToSave['meta'] = $meta;
        $recordToSave['record'] = $record;

        echo $recordToSave['id']."\n";
        file_put_contents($targetPath."/".$recordToSave['id'].".json", json_encode($recordToSave, JSON_PRETTY_PRINT));
    }
    echo "[done]";
    echo "\n";

}

function processJbookDocObj($jbookType, $filename, $fileRecordId, $jbookGrpIdx, $jbookInfoIdx, $jbookDocObj, $recordType, $meta, $rows, $targetPath) {

    echo "<Process jbook doc object - $recordType>\n";
    $meta['budget_year'] = $jbookDocObj['JustificationBook']['BudgetYear']['val'];
    $meta['budget_cycle'] = $jbookDocObj['JustificationBook']['BudgetCycle']['val'];
    $meta['submission_date'] = $jbookDocObj['JustificationBook']['SubmissionDate']['val'];
    $meta['service_agency_name'] = $jbookDocObj['JustificationBook']['ServiceAgencyName']['val'];
    $meta['appropriation_code'] = $jbookDocObj['JustificationBook']['AppropriationCode']['val'];
    $meta['appropriation_name'] = $jbookDocObj['JustificationBook']['AppropriationName']['val'];

    switch ($recordType) {
        case 'procurement-lineitems':
            echo "<record count = ".count($rows).">\n";
        break;

        case 'rdte-programelements':
            echo "<record count = ".count($rows).">\n";
        break;
    }

    if ($jbookType == 'masterjbook') {
        $jbookRecordId = "MJBOOK"."-".$jbookGrpIdx."-".$jbookInfoIdx."-".$fileRecordId;
    } else {
        $jbookRecordId = "JBOOK"."-".$fileRecordId;
    }

    // ensure no special chars in $id that might affect writing to disk
    $jbookRecordId = preg_replace("/[^a-z0-9\_\-\.]/i",'',$jbookRecordId);

    saveRecords($jbookRecordId, $recordType, $meta, $rows, $targetPath);

}

function isAssoc(array $arr) {
    if (array() === $arr) return false;
    return array_keys($arr) !== range(0, count($arr) - 1);
}

function processJsonDocs($rglobPattern='*.json') {
    $sourcePath = './2-jbook-json';

    $targetPaths = [];
    $targetPaths['procurement-lineitems'] = './3-json-procurement-lineitems';
    $targetPaths['rdte-programelements'] = './3-json-rdte-programelements';

    echo "<Removing / creating target folders>\n";
    foreach ($targetPaths as $targetPath) {
        echo "<$targetPath>\n";
        exec('rm -Rf '.$targetPath);
        mkdir($targetPath);
    }

    sleep(2);

    $fileList = rglob($sourcePath.'/'.$rglobPattern);
    sort($fileList);
    $fileCount = count($fileList);

    foreach ($fileList as $fileIdx=>$filePath) {
        // Get file year
        $filePathSegments = explode('-',str_replace($sourcePath."/","",$filePath));
        $fileYear = $filePathSegments[0];

        echo "----------------------\n";
        echo "[".($fileIdx+1)."/".$fileCount."]\n";
        echo "FILE = ".$filePath."\n";
        echo "----------------------\n";

        echo "<Reading JSON into memory>\n";
        $jbookDoc = '';
        $jbookDoc = json_decode(file_get_contents($filePath), TRUE);

        $meta = [];
        $meta['filename'] = $jbookDoc['@filename'];
        $meta['doctype'] = $jbookDoc['@doctype'];

        $recordType = '';

        // procurement-lineitems in JBOOKS
        if (isset($jbookDoc['JustificationBook']['LineItemList']['LineItem'])) {
           $recordType = 'procurement-lineitems';
           processJbookDocObj(
               'jbook',
               $jbookDoc['@filename'],
               $jbookDoc['@recordid'],
               null,
               null,
               $jbookDoc,
               $recordType,
               $meta,
               $jbookDoc['JustificationBook']['LineItemList']['LineItem'],
               $targetPaths[$recordType]
           );
        }

        // rdte-programelements in JBOOKS (2013-2016)
        if (isset($jbookDoc['JustificationBook']['R2ExhibitList']['R2Exhibit'])) {
            $recordType = 'rdte-programelements';

            // Transform to get rid of outer ProgramElement object
            $peList = [];
            foreach ($jbookDoc['JustificationBook']['R2ExhibitList']['R2Exhibit'] as $pek=>$pev) {
              $peList[$pek] = array_merge(
                array('@monetaryUnit' => $pev['@monetaryUnit']),
                $pev['ProgramElement']
              );
            }

            processJbookDocObj(
                'jbook',
                $jbookDoc['@filename'],
                $jbookDoc['@recordid'],
                null,
                null,
                $jbookDoc,
                $recordType,
                $meta,
                $peList,
                $targetPaths[$recordType]
            );

        }

        // rdte-programelements in JBOOKS (2017-current)
        if (isset($jbookDoc['JustificationBook']['ProgramElementList']['ProgramElement'])) {
            $recordType = 'rdte-programelements';
            processJbookDocObj(
                'jbook',
                $jbookDoc['@filename'],
                $jbookDoc['@recordid'],
                null,
                null,
                $jbookDoc,
                $recordType,
                $meta,
                $jbookDoc['JustificationBook']['ProgramElementList']['ProgramElement'],
                $targetPaths[$recordType]
            );

        }



        // MASTER JBOOKS
        if (isset($jbookDoc['MasterJustificationBook']['JustificationBookGroupList']['JustificationBookGroup'])) {
            foreach ($jbookDoc['MasterJustificationBook']['JustificationBookGroupList']['JustificationBookGroup'] as $jbookGrpIdx=>$jbookGrp) {
                echo "<MASTER JBOOK GROUP: $jbookGrpIdx>\n";

                if (isset($jbookGrp['JustificationBookInfoList']['JustificationBookInfo'])) {
                    foreach ($jbookGrp['JustificationBookInfoList']['JustificationBookInfo'] as $jbookIdx=>$jbook) {

                        // procurement-lineitems in MASTER JBOOKS
                        if (isset($jbook['JustificationBook']['LineItemList']['LineItem'])) {
                            $recordType = 'procurement-lineitems';
                            processJbookDocObj(
                                'masterjbook',
                                $jbook['@filename'],
                                $jbookDoc['@recordid'],
                                $jbookGrpIdx,
                                $jbookIdx,
                                $jbook,
                                $recordType,
                                $meta,
                                $jbook['JustificationBook']['LineItemList']['LineItem'],
                                $targetPaths[$recordType]
                            );
                        }

                        // rdte-programelements in MASTER JBOOKS (2013-2016)
                        if (isset($jbook['JustificationBook']['R2ExhibitList']['R2Exhibit'])) {
                            $recordType = 'rdte-programelements';

                            // Transform to get rid of outer ProgramElement object
                            $peList = [];
                            foreach ($jbookDoc['JustificationBook']['R2ExhibitList']['R2Exhibit'] as $pek=>$pev) {
                              $peList[$pek] = array_merge(
                                array('@monetaryUnit' => $pev['@monetaryUnit']),
                                $pev['ProgramElement']
                              );
                            }

                            processJbookDocObj(
                                'masterjbook',
                                $jbook['@filename'],
                                $jbookDoc['@recordid'],
                                $jbookGrpIdx,
                                $jbookIdx,
                                $jbook,
                                $recordType,
                                $meta,
                                $peList,
                                $targetPaths[$recordType]
                            );
                        }

                        // rdte-programelements in MASTER JBOOKS (2017-current)
                        if (isset($jbook['JustificationBook']['ProgramElementList']['ProgramElement'])) {
                            $recordType = 'rdte-programelements';
                            processJbookDocObj(
                                'masterjbook',
                                $jbook['@filename'],
                                $jbookDoc['@recordid'],
                                $jbookGrpIdx,
                                $jbookIdx,
                                $jbook,
                                $recordType,
                                $meta,
                                $jbook['JustificationBook']['ProgramElementList']['ProgramElement'],
                                $targetPaths[$recordType]
                            );
                        }

                    }
                }

            }
        }

        if ($recordType === '') {
            echo "<ERROR: No records found in jbook>\n";
            echo "\n\n".json_encode($jbookDoc)."\n\n";
            echo "<ERROR: No records found in jbook>\n";
            die;
        }


    }



    echo "\n\n[done]\n";
    die;

}

function copyJbookXmlToSingleFolder() {
    $sourcePath = './0-jbook-pdf';
    $targetPath = './1-jbook-xml';

    if (!file_exists($targetPath)) {
        echo "Folder does not exist - creating.\n";
        mkdir($targetPath);
    }

    $fileList = rglob($sourcePath.'/*ook*.xml');
    sort($fileList);
    $fileCount = count($fileList);

    foreach ($fileList as $fileIdx=>$filePath) {
        $fileSize = filesize($filePath);
        $targetFilename = implode('-',explode('/',str_replace($sourcePath.'/','',$filePath)));
        echo "[".($fileIdx+1)."/".$fileCount."]\n";
        echo $targetFilename." (".$fileSize." bytes)\n";
        copy($filePath,$targetPath.'/'.$targetFilename);
    }

    echo "\n\n[done]\n";
    die;

}

function saveJsonArrayPaths($arrayConfigOutput) {

  echo "<Sorting jbookArrayPaths[MasterJustificationBook]>\n";
  foreach ($GLOBALS['jbookArrayPaths']['MasterJustificationBook'] as $year=>$paths) {
    sort($GLOBALS['jbookArrayPaths']['MasterJustificationBook'][$year]);
  }
  echo "<Sorting jbookArrayPaths[JustificationBook]>\n";
  foreach ($GLOBALS['jbookArrayPaths']['JustificationBook'] as $year=>$paths) {
    sort($GLOBALS['jbookArrayPaths']['JustificationBook'][$year]);
  }
  echo "<Sorting jbookArrayPaths[FilesAnalyzed]>\n";
  foreach ($GLOBALS['jbookArrayPaths']['JustificationBook'] as $year=>$paths) {
    sort($GLOBALS['jbookArrayPaths']['JustificationBook'][$year]);
  }

  echo "<Saving ".$arrayConfigOutput.">\n";
  file_put_contents($arrayConfigOutput, json_encode($GLOBALS['jbookArrayPaths'], JSON_PRETTY_PRINT));
}

function determineJbookArrayPaths($rglobPattern='*.xml') {
    $sourcePath = './1-jbook-xml';
    $arrayConfigOutput = "./jbookArrays.json";
    $jbookArrays = [];

    echo "<load current $arrayConfigOutput to append to (if exists)>\n";
    if (file_exists($arrayConfigOutput)) {
        $GLOBALS['jbookArrayPaths'] = json_decode(file_get_contents($arrayConfigOutput),TRUE);
    } else {
        file_put_contents($arrayConfigOutput, json_encode($GLOBALS['jbookArrayPaths'], JSON_PRETTY_PRINT));
    }


    $fileList = rglob($sourcePath.'/'.$rglobPattern);
    sort($fileList);
    $fileCount = count($fileList);

    foreach ($fileList as $fileIdx=>$filePath) {

        // Get file year
        $filePathSegments = explode('-',str_replace($sourcePath."/","",$filePath));
        $fileYear = $filePathSegments[0];

        // Get file type
        if (strpos($filePath,'MasterJustificationBook') !== false) {
            $jbookType = 'MasterJustificationBook';
        } else {
            $jbookType = 'JustificationBook';
        }

        // Get filename
        $fileName = str_replace($sourcePath.'/','',$filePath);

        echo "----------------------\n";
        echo "[".($fileIdx+1)."/".$fileCount."]\n";
        echo "FILEPATH = ".$filePath."\n";
        echo "FILENAME = ".$fileName."\n";
        echo "YEAR = ".$fileYear."\n";
        echo "JBOOK_TYPE = ".$jbookType."\n";
        echo "----------------------\n";

        // If global file year hasn't been set before, set it.
        if (!isset($GLOBALS['jbookArrayPaths'][$jbookType][$fileYear])) {
            $GLOBALS['jbookArrayPaths'][$jbookType][$fileYear] = [];
        }

        // If we have already processed this file - simple read from FileAnalyzed list of paths and ensure
        // they are already included.

        if (isset($GLOBALS['jbookArrayPaths']['FilesAnalyzed'][$fileName])) {
            if (count($GLOBALS['jbookArrayPaths']['FilesAnalyzed'][$fileName]) > 0) {
                foreach ($GLOBALS['jbookArrayPaths']['FilesAnalyzed'][$fileName] as $k=>$path) {
                    $GLOBALS['jbookArrayPaths'][$jbookType][$fileYear][] = $path;
                    $GLOBALS['jbookArrayPaths'][$jbookType][$fileYear] = array_values(array_unique($GLOBALS['jbookArrayPaths'][$jbookType][$fileYear]));
                };
                echo "<Skipping - already have paths for this file>\n";
                saveJsonArrayPaths($arrayConfigOutput);
                //file_put_contents($arrayConfigOutput,json_encode($GLOBALS['jbookArrayPaths'], JSON_PRETTY_PRINT));
                continue;
            }
        }

        // If we didn't continue from last setp - file needs to
        $GLOBALS['FilesAnalyzed'][$fileName] = [];

        echo "<Loading XML>\n";
        $xml = simplexml_load_file($filePath);

        echo "<Converting XML to JSON>\n";
        $json = XmlTools::xmlToArray($xml,null,array('removeNamespace'=>true));

        echo "<Determining paths that are arrays>\n";
        getXMLPaths($fileYear, $jbookType, $fileName, $json);

        echo "----------------------\n";

        echo "<Total MasterJustificationBook Unique Path Summary Per Year>\n";
        foreach ($GLOBALS['jbookArrayPaths']['MasterJustificationBook'] as $kv=>$vv) {
         echo "[".$kv."=>".count($vv)."]";
        }
        echo "\n";

        echo "<Total JustificationBook Unique Path Summary Per Year>\n";
        foreach ($GLOBALS['jbookArrayPaths']['JustificationBook'] as $kv=>$vv) {
         echo "[".$kv."=>".count($vv)."]";
        }
        echo "\n";
        echo "<Updating $arrayConfigOutput>\n";
        saveJsonArrayPaths($arrayConfigOutput);
        //file_put_contents($arrayConfigOutput,json_encode($GLOBALS['jbookArrayPaths'], JSON_PRETTY_PRINT));

        echo "=========================================================\n";

    }

    echo "----------------------\n";

    echo "\n[done]\n\n";



}

function convertXmlToJson($rglobPattern='*.xml') {
    $sourcePath = './1-jbook-xml';
    $targetPath = './2-jbook-json';
    $jsonArrayFile = "./jbookArrays.json";

    if (!file_exists($targetPath)) {
        echo "Folder does not exist - creating.\n";
        mkdir($targetPath);
    }

    echo "<Loading and merging all jbookArrayPaths into a single list>\n";
    $jbookArrayPaths = json_decode(file_get_contents($jsonArrayFile), TRUE);
    $jbookArrayPathsLastUpdated = filemtime($jsonArrayFile);

    $allJbookArrayPaths = [];

    echo '|MasterJustificationBook|';

    foreach ($jbookArrayPaths['MasterJustificationBook'] as $year=>$arrayPathList) {
      echo $year."|";
      $allJbookArrayPaths = array_merge($allJbookArrayPaths,$arrayPathList);
      $allJbookArrayPaths = array_values(array_unique($allJbookArrayPaths));
    }
    echo "\n";

    echo '|JustificationBook|';

    foreach ($jbookArrayPaths['JustificationBook'] as $year=>$arrayPathList) {
      echo $year."|";
      $allJbookArrayPaths = array_merge($allJbookArrayPaths,$arrayPathList);
      $allJbookArrayPaths = array_values(array_unique($allJbookArrayPaths));
    }
    echo "\n";

    $rootPaths = [
      "JustificationBook.LineItemList.LineItem.",
      "JustificationBook.R2ExhibitList.R2Exhibit.",
      "JustificationBook.ProgramElementList.ProgramElement.",
      "MasterJustificationBook.JustificationBookGroupList.JustificationBookGroup.JustificationBookInfoList.JustificationBookInfo.JustificationBook.LineItemList.LineItem.",
      "MasterJustificationBook.JustificationBookGroupList.JustificationBookGroup.JustificationBookInfoList.JustificationBookInfo.JustificationBook.R2ExhibitList.R2Exhibit.",
      "MasterJustificationBook.JustificationBookGroupList.JustificationBookGroup.JustificationBookInfoList.JustificationBookInfo.JustificationBook.ProgramElementList.ProgramElement."
    ];

    $recordArrayPaths = [];
    $otherArrayPaths = [];

    foreach ($allJbookArrayPaths as $k=>$v) {

      $recordPath = null;

      foreach ($rootPaths as $kf=>$vf) {
        if (substr($v,0,strlen($vf)) == $vf) {
          $recordPath = str_replace($vf,"",$v);
        }
      }

      if (strlen($recordPath) > 0) {
        $recordArrayPaths[] = $recordPath ;
        $recordArrayPaths = array_values(array_unique($recordArrayPaths));
        sort($recordArrayPaths);
      } else {
        $otherArrayPaths[] = $v;
        $otherArrayPaths = array_values(array_unique($otherArrayPaths));
        sort($otherArrayPaths);
      }

    }

    $overloadedPathArray = [];
    foreach ($recordArrayPaths as $k=>$v) {
      foreach ($rootPaths as $kk=>$vv) {
        $overloadedPathArray[] = $vv."".$v;
        $overloadedPathArray = array_values(array_unique($overloadedPathArray));
        sort($overloadedPathArray);
      }
    }

    $overloadedPathArray = array_merge($overloadedPathArray,$otherArrayPaths);

    echo "<arrayPaths count = ".count($overloadedPathArray).">\n";
    echo "<last updated = ".date('c', $jbookArrayPathsLastUpdated).">\n";
    echo "=========================================================\n";

    sleep(2);

    $fileList = rglob($sourcePath.'/'.$rglobPattern);
    sort($fileList);
    $fileCount = count($fileList);

    foreach ($fileList as $fileIdx=>$filePath) {

        $fileName = str_replace($sourcePath."/","",$filePath);
        $filePathSegments = explode('-',str_replace($sourcePath."/","",$filePath));
        $fileSize = filesize($filePath);

        $fileYear = $filePathSegments[0];

        if (strpos($filePath,'MasterJustificationBook') !== false) {
            $jbookType = 'MasterJustificationBook';
        } else {
            $jbookType = 'JustificationBook';
        }

        $docType = $filePathSegments[0].'-'.$filePathSegments[1].'-'.strtoupper($jbookType);

        $recordId = hash_file('sha256',$filePath);

        $targetFileName = substr($fileName, 0, -3)."json";
        $target = $targetPath."/".$targetFileName;

        echo "----------------------\n";
        echo "[".($fileIdx+1)."/".$fileCount."]\n";
        echo "FILEPATH = ".$filePath."\n";
        echo "FILESIZE = ".$fileSize."\n";
        echo "YEAR = ".$fileYear."\n";
        echo "JBOOK_TYPE = ".$jbookType."\n";
        echo "@recordid = ".$recordId."\n";
        echo "@doctype = ".$docType."\n";
        echo "@filename = ".$fileName."\n";
        echo "----------------------\n";

        // check if file exists and timestamp is "greater than" jbookArrays.json
        if (file_exists($target)) {
          $jsonCreateTimestamp = filemtime($target);
          echo "<NOTICE: XML->JSON file exists - created ".date('c', $jsonCreateTimestamp).">\n";

          if ($jsonCreateTimestamp > $jbookArrayPathsLastUpdated) {
            echo "<SKIPPING XML->JSON: xml has already been converted since last jbookArrayPaths update>\n";
            continue;
          }

          if ($jsonCreateTimestamp <= $jbookArrayPathsLastUpdated) {
            echo "<OVERWRITE: conversion will be done - and file will be overwritten - since it appears to have been created previous to last array analysis>\n";
          }
        }



        $xml = simplexml_load_file($filePath);

        $jsonDoc = Xmltools::XmlToArray(
            $xml,
            $jbookType,
            array('alwaysArray'=>$overloadedPathArray, 'removeNamespace'=>true)
        );

        $doc = [];
        $doc['@recordid'] = $recordId;
        $doc['@doctype'] = $docType;
        $doc['@filename'] = $fileName;
        $doc = array_merge($doc,$jsonDoc);

        echo "<Writing ".$target.">\n";
        file_put_contents($target,json_encode($doc, JSON_PRETTY_PRINT));

    }

    echo "\n\n[done]\n";
    die;
}

function getXMLPaths($fileYear, $jbookType, $fileName, $json, $path="") {

    if (is_array($json)) {
      foreach ($json as $key=>$val) {

            if (!is_numeric($key)) {
              if (is_array($val)) {
                getXMLPaths($fileYear, $jbookType, $fileName, $val, ltrim($path.".".$key,"."));
              } else {
                getXMLPaths($fileYear, $jbookType, $fileName, $val, $path);
              }
            } else {
              // Append to list of paths per jbook types (jbook or master jbook)
              $GLOBALS['jbookArrayPaths'][$jbookType][$fileYear][] = $path;
              $GLOBALS['jbookArrayPaths'][$jbookType][$fileYear] = array_values(array_unique($GLOBALS['jbookArrayPaths'][$jbookType][$fileYear]));

              // Append to list of paths per file to minimize need to re-eval in future
              $GLOBALS['jbookArrayPaths']['FilesAnalyzed'][$fileName][] = $path;
              $GLOBALS['jbookArrayPaths']['FilesAnalyzed'][$fileName] = array_values(array_unique($GLOBALS['jbookArrayPaths']['FilesAnalyzed'][$fileName]));

              getXMLPaths($fileYear, $jbookType, $fileName, $val, $path);
            }

      }

    }

}

function rglob($pattern, $flags = 0) {
    $files = glob($pattern, $flags);

    foreach (glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT) as $dir) {
        $files = array_merge($files, rglob($dir.'/'.basename($pattern), $flags));
    }
    return $files;
}
