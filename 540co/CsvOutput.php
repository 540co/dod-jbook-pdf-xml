<?php

use Keboola\Json\Parser;

class Csv {

  public function createTables($recordlist) {

    $rowIdLookup = array();
    $tables = [];

    $parser = Parser::create(new \Monolog\Logger('json-parser'));
    $parser->process($recordlist);

    echo "<Parser START>\n";
    $csvfiles = $parser->getCsvFiles();
    echo "<Parser END>\n";

    foreach ($csvfiles as $fileIndex=>$file) {

      echo "<$file>\n";
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



      $tables[$attributes['fullDisplayName']] = $rows;

    }

    return $tables;

  }

  public function writeCsv($file, $rows) {

    file_put_contents($file."-j.json", json_encode($rows,JSON_PRETTY_PRINT));

    $fileHandle = fopen($file, "w");

    $headerRow = "";

    $rowHeader = array_keys($rows[0]);

    foreach ($rowHeader as $header) {
      $headerRow = $headerRow.$header.",";
    }

    $headerRow = rtrim($headerRow,",");

    fwrite($fileHandle, $headerRow."\n");

    foreach ($rows as $rowNum=>$row) {
      $rowString = '';

      foreach ($row as $k=>$v) {
        $valToWrite = $row[$k];

        $valToWrite = str_replace("\n\n",' ',$valToWrite);
        $valToWrite = str_replace("\r\n",' ',$valToWrite);
        $valToWrite = str_replace("\n",' ',$valToWrite);
        $valToWrite = str_replace("\t",' ',$valToWrite);
        $valToWrite = str_replace("\r",' ',$valToWrite);
        $valToWrite = str_replace('"','',$valToWrite);

        $rowString = $rowString.'"'.$valToWrite.'",';
      }

      $rowString = rtrim($rowString,",");
      echo $file." -> ".$rowNum." -> ".strlen($rowString)."\n";
      fwrite($fileHandle, $rowString."\n");
    }


    fclose($fileHandle);

 }


}
