<?php
require __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/vendor/540co/xml-tools/src/Xmltools.php';
//use FiveFortyCo\Xmlpipeline\Xmltools;

$GLOBALS['jbookArrayPaths'] = array();
$GLOBALS['jbookArrayPaths']['MasterJustificationBook'] = array();
$GLOBALS['jbookArrayPaths']['JustificationBook'] = array();

date_default_timezone_set ('GMT');
ini_set('memory_limit', -1);

$longOptions = [
    'step:'
];
  
$options = getopt('', $longOptions);

switch ($options['step']) {
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
        determineJbookArrayPaths();
    break;

    case '3-convert-xml-to-json': 
        echo "=========================================================\n";
        echo "[3-convert-xml-to-json]\n";
        echo "=========================================================\n";
        convertXmlToJson();
    break;

    case '4-process-json-docs': 
        echo "=========================================================\n";
        echo "[4-process-json-docs]\n";
        echo "=========================================================\n";
        processJsonDocs();
    break;
}

function saveRecords($recordId, $year, $recordType, $meta, $records, $targetPath) {
    echo "Saving (".count($records).") ".$year." - ".$recordType."\n";
    
    foreach ($records as $k=>$record) {
        $recordToSave = [];
        $recordToSave['id'] = $year.'-'.$meta['service_agency_name'].'-'.$recordId.'-'.$k;
        $recordToSave['meta'] = $meta;
        $recordToSave['record'] = $record;
        
        echo $recordToSave['id']."\n";
        file_put_contents($targetPath."/".$recordToSave['id'].".json", json_encode($recordToSave, JSON_PRETTY_PRINT));
    }

}

function processJsonDocs() {
    $sourcePath = './2-jbook-json';

    $targetPaths = [];
    $targetPaths['procurement-lineitems'] = './3-procurement-lineitems';
    $targetPaths['rdte-programelements'] = './3-rdte-programelements';
    
    foreach ($targetPaths as $targetPath) {
        if (!file_exists($targetPath)) {
            echo "Folder does not exist - creating.\n";
            mkdir($targetPath);
        }
    }

    $fileList = rglob($sourcePath.'/*.json');
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

        $records = [];

        $recordType = '';
        
        // procurement-lineitems in JBOOKS
        if (isset($jbookDoc['JustificationBook']['LineItemList']['LineItem'])) {
            $recordType = 'procurement-lineitems';
            $records = $jbookDoc['JustificationBook']['LineItemList']['LineItem'];
   
            $meta['budget_year'] = $jbookDoc['JustificationBook']['BudgetYear']['val'];
            $meta['budget_cycle'] = $jbookDoc['JustificationBook']['BudgetCycle']['val'];;
            $meta['submission_date'] = $jbookDoc['JustificationBook']['SubmissionDate']['val'];;
            $meta['service_agency_name'] = $jbookDoc['JustificationBook']['ServiceAgencyName']['val'];;
            $meta['appropriation_code'] = $jbookDoc['JustificationBook']['AppropriationCode']['val'];;
            $meta['appropriation_name'] = $jbookDoc['JustificationBook']['AppropriationName']['val'];;

            saveRecords($jbookDoc['@recordid'], $meta['budget_year'], $recordType, $meta, $records, $targetPaths[$recordType]);
        }
       
        // procurement-lineitems in MASTER JBOOKS
        if (isset($jbookDoc['MasterJustificationBook']['JustificationBookGroupList']['JustificationBookGroup'])) {

            /*['JustificationBookInfoList']['JustificationBookInfo']['JustificationBook']['LineItemList']['LineItem'] */
            $recordType = 'procurement-lineitems';
            //$records = $jbookDoc['MasterJustificationBook']['JustificationBookGroupList']['JustificationBookGroup']['JustificationBookInfoList']['JustificationBookInfo']['JustificationBook']['LineItemList']['LineItem'];
            //saveRecords($recordType, $meta, $records, $targetPaths[$recordType]);
        }

        if ($recordType === '') {
            echo "WARNING: No records found in jbook";
        }


    }





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

    echo "\n[done]\n\n";
    
}

function determineJbookArrayPaths() {
    $sourcePath = './1-jbook-xml';
    $arrayConfigOutput = "./jbookArrays.json";
    $jbookArrays = [];

    $fileList = rglob($sourcePath.'/*.xml');
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

        echo "----------------------\n";
        echo "[".($fileIdx+1)."/".$fileCount."]\n";
        echo "FILE = ".$filePath."\n";
        echo "YEAR = ".$fileYear."\n";
        echo "JBOOK_TYPE = ".$jbookType."\n";
        echo "----------------------\n";
        echo "<Loading XML>\n";
        
        $xml = simplexml_load_file($filePath);
        
        echo "<Converting XML to JSON>\n";
        $json = XmlTools::xmlToArray($xml,null,array('removeNamespace'=>true));

        echo "<Determining paths that are arrays>\n";
        getXMLPaths($fileYear, $jbookType, $json);

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
        echo "<Updating jbookArrayPaths.json>\n";
        file_put_contents('jbookArrayPaths.json',json_encode($GLOBALS['jbookArrayPaths'], JSON_PRETTY_PRINT));
   
        echo "=========================================================\n";
       
    }

    echo "----------------------\n";
    
    echo "\n[done]\n\n";
        

    
}

function convertXmlToJson() {
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
  
    $fileList = rglob($sourcePath.'/*.xml');
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

        $jsonDoc = Xmltools::XmlToArray($xml,$jbookType,
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

    echo "\n[done]\n\n";
}

function getXMLPaths($fileYear, $jbookType, $json, $path="") {

    if (is_array($json)) {
      foreach ($json as $key=>$val) {
  
            if (!is_numeric($key)) {
              if (is_array($val)) {
                getXMLPaths($fileYear, $jbookType, $val, ltrim($path.".".$key,"."));
              } else {
                getXMLPaths($fileYear, $jbookType, $val, $path);
              }
            } else {
              if (!isset($GLOBALS['jbookArrayPaths'][$jbookType][$fileYear])) {
                $GLOBALS['jbookArrayPaths'][$jbookType][$fileYear] = [];
              }
              $GLOBALS['jbookArrayPaths'][$jbookType][$fileYear][] = $path;

              $GLOBALS['jbookArrayPaths'][$jbookType][$fileYear] = array_values(array_unique($GLOBALS['jbookArrayPaths'][$jbookType][$fileYear]));
              
              getXMLPaths($fileYear, $jbookType, $val, $path);
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
  