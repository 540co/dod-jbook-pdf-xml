<?php
//error_reporting(E_ERROR | E_PARSE);
use Keboola\Json\Parser;

class Csv {

  /*
  private function createPrimaryKey($rownum,$row) {

    $stopFields = ['JSON_parentId'];

    $stringToHash = $rownum;

    foreach ($row as $fieldkey=>$fieldval) {
      if (!in_array($fieldkey,$stopFields)) {
        $stringToHash .= $fieldval;
      }
    }

    return $rownum.'-'.sha1($stringToHash);
  }
  */

  public function createTables($recordlist) {

    $rowIdLookup = array();
    $tables = [];

    $parser = Parser::create(new \Monolog\Logger('json-parser'));
    $parser->process($recordlist);

    $csvfiles = $parser->getCsvFiles();

    foreach ($csvfiles as $fileIndex=>$file) {

      $csvfile = $file->openFile('r');
      $csvfile->setFlags(\SplFileObject::READ_CSV);

      $attributes = $file->getAttributes();

      $template = array();
      $rows = array();

      foreach ($csvfile as $rownum=>$rowval) {
        if ($rownum == 0) {
            foreach ($rowval as $fieldnum=>$field) {
              $template[$fieldnum] = $field;
            }
        }

        if ($rownum > 0 && $rowval[0] !== NULL) {
          foreach ($rowval as $fieldnum=>$fieldval) {
            $rows[$rownum-1][$template[$fieldnum]] = $fieldval;
          }
        }
      }

      /*
      foreach ($rows as $rownum=>$row) {
          $record['recordid'] = "XXX";
          $rows[$rownum]['@ROWID'] = $record['recordid']."-".sha1($attributes['fullDisplayName'])."-".Csv::createPrimaryKey($rownum,$row);
          $rows[$rownum]['@RECORDID'] = $record['recordid'];

          if (isset($row['JSON_parentId'])) {
            $rows[$rownum]['@parentid'] = $row['JSON_parentId']."-".$record['recordid'];
          }

          foreach ($row as $k=>$v) {
            if (
              substr($k,0,1) !== "@" && 
              substr($k,-4) !== "_val" && 
              strlen($v) > 0 && 
              $k !== "JSON_parentId" && 
              $k !== "val" &&
              $k !== "id" &&
              substr($k,0,5) !== "meta_" &&
              substr($k,0,8) !== "record_@") {
              $rows[$rownum][$k] = $rows[$rownum][$k]."-".$record['recordid'];
              if (isset($rowIdLookup[$attributes['fullDisplayName'].'.'.$k][$v])) {
                echo "ERROR\n";
                die;
              } else {
                $rowIdLookup[$v][$attributes['fullDisplayName'].'.'.str_replace("_",".",$k)] = $rows[$rownum]['@rowid'];
              }

            }
          }



      }
      */

      $tables[$attributes['fullDisplayName']] = $rows;

    }

    // add parent parentrowid
    /*
    foreach ($tables as $table=>$rows) {
      foreach ($rows as $rowNum=>$row) {
        if (isset($row['JSON_parentId'])) {
          echo "Looking up rowid =>".$table." | ".$row['JSON_parentId']."\n";
        
          $tables[$table][$rowNum]['@parentrowid'] = $rowIdLookup[$row['JSON_parentId']][$table];
        }
      }
    }
    */

    return $tables;

  }

  public function writeCsv($file, $rows) {

    $fileHandle = fopen($file, "w");

    $headerRow = "";

    $rowHeader = array_keys($rows[0]);

    foreach ($rowHeader as $header) {
      $headerRow = $headerRow.$header.",";
    }

    $headerRow = rtrim($headerRow,",");

    fwrite($fileHandle, $headerRow."\n");

    foreach ($rows as $row) {
      $rowString = '';

      foreach ($row as $k=>$v) {
        $valToWrite = $row[$k];
        $valToWrite = str_replace('"','',$valToWrite);
        $valToWrite = str_replace("\n",' ',$valToWrite);
        $valToWrite = str_replace("\t",' ',$valToWrite);
        $valToWrite = str_replace("\r",' ',$valToWrite);

        $rowString = $rowString.'"'.$valToWrite.'",';
      }

      $rowString = rtrim($rowString,",");
      fwrite($fileHandle, $rowString."\n");
    }


    fclose($fileHandle);

 }


  /*
  public function write($outputConfig,$records) {

      foreach ($outputConfig->doctypes as $doctype=>$doctypeDefinition) {

        $recordsList = [];

        foreach ($records as $record) {

          if ($doctype == $record['doctype']) {
            $j = $record['json'];
            $recordsList[] = $j;
          }

        }

        $tables = Csv::createTables($recordsList);


        $parser = Csvparser::create(new \Monolog\Logger('json-parser'));


        if (count($recordsList) == 0) {
          continue;
        }

        $parser->process($recordsList);

        $csvfiles = $parser->getCsvFiles();

        $folderToWrite = $outputConfig->folder.$doctype;

        if (!file_exists($folderToWrite)) {
            mkdir($folderToWrite, 0777, true);
        }

        foreach ($csvfiles as $k=>$v) {

          $csvfile = $v->openFile('r');
          $csvfile->setFlags(\SplFileObject::READ_CSV);
          $attributes = $v->getAttributes();

          file_put_contents($folderToWrite."/".substr(str_replace('.','',$attributes['fullDisplayName']),-250).".csv","");

          foreach ($csvfile as $rownum=>$rowval) {

            if ($rowval[0] == null) {
              continue;
            }

            $rowToWrite = "";
            foreach ($rowval as $val) {
              $rowToWrite .= '"'.str_replace(array("\n", "\t", "\r"), '', $val).'",';
            }
            $rowToWrite .= "\n";
            //echo $rowToWrite;
            file_put_contents($folderToWrite."/".substr(str_replace('.','',$attributes['fullDisplayName']),-250).".csv", $rowToWrite, FILE_APPEND);
          }

        }



      }

    }
    */
    
}

