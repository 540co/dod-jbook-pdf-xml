<?php
error_reporting(E_ERROR | E_PARSE);

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
require __DIR__ . '/540co/keboola/csv/src/Keboola/Csv/CsvFile.php';
require __DIR__ . '/540co/keboola/csv/src/Keboola/Csv/Exception.php';
require __DIR__ . '/540co/keboola/csv/src/Keboola/Csv/InvalidArgumentException.php';
require __DIR__ . '/540co/keboola/json-parser/src/Keboola/Json/Exception/JsonParserException.php';
require __DIR__ . '/540co/keboola/json-parser/src/Keboola/Json/Exception/NoDataException.php';
require __DIR__ . '/540co/keboola/json-parser/src/Keboola/Json/Analyzer.php';
require __DIR__ . '/540co/keboola/json-parser/src/Keboola/Json/Cache.php';
require __DIR__ . '/540co/keboola/json-parser/src/Keboola/Json/CsvRow.php';
require __DIR__ . '/540co/keboola/json-parser/src/Keboola/Json/Parser.php';
require __DIR__ . '/540co/keboola/json-parser/src/Keboola/Json/Struct.php';
require __DIR__ . '/540co/keboola/php-csvtable/src/Keboola/CsvTable/Table.php';
require __DIR__ . '/540co/keboola/php-temp/src/Keboola/Temp/Temp.php';
require __DIR__ . '/540co/xml-tools/src/Xmltools.php';
require __DIR__ . '/540co/CsvOutput.php';

$GLOBALS['jbookArrayPaths'] = array();
$GLOBALS['jbookArrayPaths']['MasterJustificationBook'] = array();
$GLOBALS['jbookArrayPaths']['JustificationBook'] = array();
$GLOBALS['jbookArrayPaths']['FilesAnalyzed'] = array();

date_default_timezone_set ('GMT');
ini_set('memory_limit', -1);

$longOptions = [
    'step:',
    'jbook-list:',
    'rglob-pattern:'
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
        jsonToCsv();
    break;
}

function jsonToCsv() {

    $sourcePaths = [];
    $sourcePaths['procurement-lineitems'] = './3-json-procurement-lineitems';
    $sourcePaths['rdte-programelements'] = './3-json-rdte-programelements';

    $targetPaths = [];
    $targetPaths['procurement-lineitems'] = './4-csv-procurement-lineitems';
    $targetPaths['rdte-programelements'] = './4-csv-rdte-programelements';

    echo "<Removing / creating target folders>\n";
    foreach ($targetPaths as $targetPath) {
        echo "<$targetPath>\n";
        exec('rm -Rf '.$targetPath);
        mkdir($targetPath);
    }

    sleep(2);

    
    foreach ($sourcePaths as $sourceIdx=>$sourcePath) {
        
        echo "<Reading $sourceIdx into an array list>\n";
        sleep(1);

        $fileList = rglob($sourcePath.'/*.json');
        sort($fileList);
        $fileCount = count($fileList);

        $records = [];
        foreach ($fileList as $fileIdx=>$filePath) {
            echo "[".($fileIdx+1)."/".$fileCount."]\n";
            echo "<$filePath>\n";
            $records[] = json_decode(file_get_contents($filePath));
        }
        echo "<record count = ".count($records)." >\n";

        echo "<Csv:createTables>\n";
        $tables = Csv::createTables($records);

        echo "<Csvparser:create>\n";
        $parser = Csvparser::create(new \Monolog\Logger('json-parser'));

        echo "<Csvparser:process>\n";
        $parser->process($records);

        echo "<Csvparser:getCsvFiles>\n";
        $csvfiles = $parser->getCsvFiles();

        foreach ($csvfiles as $k=>$v) {
            echo "<Writing: $k>\n";
            
            $csvfile = $v->openFile('r');
            $csvfile->setFlags(\SplFileObject::READ_CSV);
            $attributes = $v->getAttributes();

            file_put_contents($targetPaths[$sourceIdx]."/".substr(str_replace('.','',$attributes['fullDisplayName']),-250).".csv","");
            
            foreach ($csvfile as $rownum=>$rowval) {
                if ($rowval[0] == null) {
                    continue;
                }

                $rowToWrite = "";
                foreach ($rowval as $val) {
                    $rowToWrite .= '"'.str_replace(array("\n", "\t", "\r"), '', $val).'",';
                }
                $rowToWrite .= "\n";

                file_put_contents($targetPaths[$sourceIdx]."/".substr(str_replace('.','',$attributes['fullDisplayName']),-250).".csv", $rowToWrite, FILE_APPEND);
          }

        }

        sleep(2);
        
    }

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
    sleep(20);
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
    
            foreach (glob('*.[pP][dD][fF]') as $filename) {
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

function saveRecords($jbookRecordId, $recordType, $meta, $records, $targetPath) {
    echo "<Saving (".count($records).") ".$year." - ".$recordType.">\n";
    
    foreach ($records as $recordIdx=>$record) {
        $recordToSave = [];
        $idFields = [];
        $idFields[] = $meta['budget_year'];
        $idFields[] = $meta['service_agency_name'];
        $idFields[] = $meta['budget_cycle'];
        $idFields[] = $meta['appropriation_code'];
        $idFields[] = $jbookRecordId;
        $idFields[] = $recordIdx;
        $recordToSave['id'] = str_replace(' ', '', implode('-',$idFields));
        $recordToSave['meta'] = $meta;
        $recordToSave['record'] = $record;
        
        echo $recordToSave['id']."\n";
        file_put_contents($targetPath."/".$recordToSave['id'].".json", json_encode($recordToSave, JSON_PRETTY_PRINT));
    }

}

function processJbookDocObj($jbookType, $filename, $fileRecordId, $jbookGrpIdx, $jbookInfoIdx, $jbookDocObj, $recordType, $meta, $targetPath) {
    
    echo "<Process jbook doc object - $recordType>\n";
    $meta['budget_year'] = $jbookDocObj['JustificationBook']['BudgetYear']['val'];
    $meta['budget_cycle'] = $jbookDocObj['JustificationBook']['BudgetCycle']['val'];
    $meta['submission_date'] = $jbookDocObj['JustificationBook']['SubmissionDate']['val'];
    $meta['service_agency_name'] = $jbookDocObj['JustificationBook']['ServiceAgencyName']['val'];
    $meta['appropriation_code'] = $jbookDocObj['JustificationBook']['AppropriationCode']['val'];
    $meta['appropriation_name'] = $jbookDocObj['JustificationBook']['AppropriationName']['val'];

    switch ($recordType) {
        case 'procurement-lineitems':
            $records = $jbookDocObj['JustificationBook']['LineItemList']['LineItem'];
            echo "<record count = ".count($records).">\n";
        break;

        case 'rdte-programelements':
            $records = $jbookDocObj['JustificationBook']['R2ExhibitList']['R2Exhibit'];
            echo "<record count = ".count($records).">\n";
        break;
    }

    if ($jbookType == 'masterjbook') {
        $id = $jbookGrpIdx."-".$jbookInfoIdx."-".$fileRecordId;
    } else {
        $id = $fileRecordId;
    }

    saveRecords($id, $recordType, $meta, $records, $targetPath);

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
               $targetPaths[$recordType]
           );
        }
       
        // rdte-programelements in JBOOKS
        if (isset($jbookDoc['JustificationBook']['R2ExhibitList']['R2Exhibit'])) {
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
                                $targetPaths[$recordType]
                            );
                        }

                        // rdte-programelements in MASTER JBOOKS
                        if (isset($jbook['JustificationBook']['R2ExhibitList']['R2Exhibit'])) {
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
                                $targetPaths[$recordType]
                            );
                        }

                    }
                }

            }
        }

        if ($recordType === '') {
            echo "<WARNING: No records found in jbook>\n";
            sleep(1);
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
                file_put_contents($arrayConfigOutput,json_encode($GLOBALS['jbookArrayPaths'], JSON_PRETTY_PRINT));
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
        file_put_contents($arrayConfigOutput,json_encode($GLOBALS['jbookArrayPaths'], JSON_PRETTY_PRINT));
   
        echo "=========================================================\n";
       
    }

    echo "----------------------\n";
    
    echo "\n[done]\n\n";
        

    
}

function convertXmlToJson($rglobPattern='*.xml') {
    $sourcePath = './1-jbook-xml';
    $targetPath = './2-jbook-json';

    if (!file_exists($targetPath)) {
        echo "Folder does not exist - creating.\n";
        mkdir($targetPath);
    }

    echo "<Loading and merging all jbookArrayPaths into a single list>\n";
    $jbookArrayPaths = json_decode(file_get_contents('./jbookArrayPaths.json'), TRUE);
    $allJbookArrayPaths = [];
    foreach ($jbookArrayPaths as $jbookType => $years) {
        echo "/".$jbookType."/";
        foreach ($years as $year=>$arrayPathList) {
            echo $year."/";
            $allJbookArrayPaths = array_merge($allJbookArrayPaths,$jbookArrayPaths[$jbookType][$year]);
            $allJbookArrayPaths = array_values(array_unique($allJbookArrayPaths));
        }
        echo "\n";
    }
    echo "<arrayPaths count = ".count($allJbookArrayPaths).">\n";
    echo "=========================================================\n";
    
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

        $xml = simplexml_load_file($filePath);
   
        $jsonDoc = Xmltools::XmlToArray(
            $xml,
            $jbookType,
            array('alwaysArray'=>$allJbookArrayPaths, 'removeNamespace'=>true)
        );

        $doc = [];
        $doc['@recordid'] = $recordId;
        $doc['@doctype'] = $docType;
        $doc['@filename'] = $fileName;
        $doc = array_merge($doc,$jsonDoc);
      
        $target = $targetPath."/".$targetFileName;
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

