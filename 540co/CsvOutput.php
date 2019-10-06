<?php
error_reporting(E_ERROR | E_PARSE);

use Keboola\Json\Parser;

class Csvparser extends Parser {

public $cache;

public $csvFiles;

protected function createSafeName($name)

{
  return $name;
}

protected function getPrimaryKeyValue($rowNum, \stdClass $dataRow, $type, $outerObjectHash = null)
{

    // Try to find a "real" parent ID
    if (!empty($this->primaryKeys[$this->createSafeName($type)])) {
        $pk = $this->primaryKeys[$this->createSafeName($type)];
        $pKeyCols = explode(',', $pk);
        $pKeyCols = array_map('trim', $pKeyCols);
        $values = [];
        foreach ($pKeyCols as $pKeyCol) {
            if (empty($dataRow->{$pKeyCol})) {
                $values[] = sha1(serialize($dataRow) . $outerObjectHash);
                $this->log->log(
                    "WARNING",
                    "Primary key for type '{$type}' was set to '{$pk}', but its column '{$pKeyCol}' does not exist! Using hash to link child objects instead.",
                    ['row' => $dataRow]
                );
            } else {
                $values[] = $dataRow->{$pKeyCol};
            }
        }
        return $type . "_" . join(";", $values);
    } else {
        // Of no pkey is specified to get the real ID, use a hash of the row
        return $type . "_" . sha1(serialize($dataRow) . $outerObjectHash) . "-".$rowNum;
    }
}

protected function parseField(
      $rowNum,
      \stdClass $dataRow,
      \Keboola\Json\CsvRow $csvRow,
      $arrayParentId,
      $column,
      $dataType,
      $type
  ) {
      // TODO safeColumn should be associated with $this->struct[$type]
      // (and parentCols -> create in parse() where the arr is created)
      // Actually, the csvRow should REALLY have a pointer to the real name (not validated),
      // perhaps sorting the child columns on its own?
      // (because keys in struct don't contain child objects)
      $safeColumn = $this->createSafeName($column);
      // skip empty objects & arrays to prevent creating empty tables
      // or incomplete column names
      if (
          !isset($dataRow->{$column})
          || is_null($dataRow->{$column})
          || (empty($dataRow->{$column}) && !is_scalar($dataRow->{$column}))
      ) {
          // do not save empty objects to prevent creation of ["obj_name" => null]
          if ($dataType != 'object') {
              $csvRow->setValue($safeColumn, null);
          }
          return;
      }
      if ($dataType == "NULL") {
          // Throw exception instead? Any usecase? TODO get rid of it maybe?
          $this->log->log(
              "WARNING", "Encountered data where 'NULL' was expected from previous analysis",
              [
                  'type' => $type,
                  'data' => $dataRow
              ]
          );
          $csvRow->setValue($column, json_encode($dataRow));
          return;
      }
      if ($this->getStruct()->isArrayOf($dataType)) {
          if (!is_array($dataRow->{$column})) {
              $dataRow->{$column} = [$dataRow->{$column}];
          }
          $dataType = 'array';
      }
      switch ($dataType) {
          case "array":
              $csvRow->setValue($safeColumn, $arrayParentId);
              $this->parse($dataRow->{$column}, $type . "." . $column, $arrayParentId);
              break;
          case "object":
              $childRow = $this->parseRow($rowNum, $dataRow->{$column}, $type . "." . $column, [], $arrayParentId);
              foreach($childRow->getRow() as $key => $value) {
                  // FIXME createSafeName is duplicated here
                  $csvRow->setValue($this->createSafeName($safeColumn . '_' . $key), $value);
              }
              break;
          default:
              // If a column is an object/array while $struct expects a single column, log an error
              if (is_scalar($dataRow->{$column})) {
                  $csvRow->setValue($safeColumn, $dataRow->{$column});
              } else {
                  $jsonColumn = json_encode($dataRow->{$column});
                  $this->log->log(
                      "ERROR",
                      "Data parse error in '{$column}' - unexpected '"
                          . $this->analyzer->getType($dataRow->{$column})
                          . "' where '{$dataType}' was expected!",
                      [ "data" => $jsonColumn, "row" => json_encode($dataRow) ]
                  );
                  $csvRow->setValue($safeColumn, $jsonColumn);
              }
              break;
      }
  }


protected function parseRow(
        $rowNum,
        \stdClass $dataRow,
        $type,
        array $parentCols = [],
        $outerObjectHash = null
    ) {
        // move back out to parse/switch if it causes issues
        $csvRow = new \Keboola\Json\CsvRow($this->getHeader($type, $parentCols));

        // Generate parent ID for arrays
        $arrayParentId = $this->getPrimaryKeyValue(
            $rowNum,
            $dataRow,
            $type,
            $outerObjectHash
        );

        foreach (array_merge($this->getStruct()->getDefinitions($type), $parentCols) as $column => $dataType) {
            $this->parseField($rowNum,$dataRow, $csvRow, $arrayParentId, $column, $dataType, $type);

        }


        return $csvRow;
    }


public function parse(array $data, $type, $parentId = null)
    {

  
        if (
            !$this->analyzer->isAnalyzed($type)
            && (empty($this->analyzer->getRowsAnalyzed()[$type])
                || $this->analyzer->getRowsAnalyzed()[$type] < count($data))
        ) {
            // analyse instead of failing if the data is unknown!
            $this->log->log(
                "debug",
                "Trying to parse an unknown data type '{$type}'. Trying on-the-fly analysis",
                [
                    "data" => $data,
                    "type" => $type,
                    "parentId" => $parentId
                ]
            );
            $this->analyzer->analyze($data, $type);
        }
        $parentId = $this->validateParentId($parentId);
        $csvFile = $this->createCsvFile($type, $parentId);
        $parentCols = array_fill_keys(array_keys($parentId), "string");

        foreach ($data as $rowNum=>$row) {
            // in case of non-associative array of strings
            // prepare {"data": $value} objects for each row
            if (is_scalar($row) || is_null($row)) {
                $row = (object) [self::DATA_COLUMN => $row];
            } elseif ($this->analyzer->getNestedArrayAsJson() && is_array($row)) {
                $row = (object) [self::DATA_COLUMN => json_encode($row)];
            }
            // Add parentId to each row
            if (!empty($parentId)) {
                $row = (object) array_replace((array) $row, $parentId);
            }
            $csvRow = $this->parseRow($rowNum,$row, $type, $parentCols);

            $csvFile->writeRow($csvRow->getRow());

        }

    }

}

class Csv {

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

public function createTables($recordlist) {

  $rowIdLookup = array();
  $tables = [];

  $parser = Csvparser::create(new \Monolog\Logger('json-parser'));
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

    foreach ($rows as $rownum=>$row) {

        $rows[$rownum]['@rowid'] = $record['recordid']."-".sha1($attributes['fullDisplayName'])."-".Csv::createPrimaryKey($rownum,$row);
        $rows[$rownum]['@recordid'] = $record['recordid'];

        if (isset($row['JSON_parentId'])) {
          $rows[$rownum]['@parentid'] = $row['JSON_parentId']."-".$record['recordid'];
        }

        foreach ($row as $k=>$v) {
          if (substr($k,0,1) !== "@" && substr($k,-4) !== "_val" && strlen($v) > 0 && $k !== "JSON_parentId" && $k !== "val") {
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

    $tables[$attributes['fullDisplayName']] = $rows;

  }

  // add parent parentrowid
  foreach ($tables as $table=>$rows) {
    foreach ($rows as $rowNum=>$row) {
      if (isset($row['JSON_parentId'])) {
        //echo "Looking up rowid =>".$table." | ".$row['JSON_parentId']."\n";
        $tables[$table][$rowNum]['@parentrowid'] = $rowIdLookup[$row['JSON_parentId']][$table];
      }
    }
  }

  return $tables;

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

