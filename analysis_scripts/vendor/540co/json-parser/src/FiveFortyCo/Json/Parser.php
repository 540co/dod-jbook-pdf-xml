<?php

namespace FiveFortyCo\Json;
use Keboola\CsvTable\Table;
use Keboola\Temp\Temp;
use Monolog\Logger;
use FiveFortyCo\Json\Exception\JsonParserException,
    FiveFortyCo\Json\Exception\NoDataException;

/**
 * JSON to CSV data analyzer and parser/converter
 *
 * Use to convert JSON objects into CSV file(s).
 * Creates multiple files if the JSON contains arrays
 * to store values of child nodes in a separate table,
 * linked by JSON_parentId column.

 * The analyze function loops through each row of an array
 * (generally an array of results) and passes the row into analyzeRow() method.
 * If the row only contains a string, it's stored in a "data" column,
 * otherwise the row should usually be an object,
 * so each of the object's variables will be used as a column name,
 * and it's value analysed:
 *
 * - if it's a scalar, it'll be saved as a value of that column.
 * - if it's another object, it'll be parsed recursively to analyzeRow(),
 *         with it's variable names prepended by current object's name
 *    - example:
 *            "parent": {
 *                "child" : "value1"
 *            }
 *            will result into a "parent_child" column with a string type of "value1"
 * - if it's an array, it'll be passed to analyze() to create a new table,
 *      linked by JSON_parentId
 *
 *
 * @author        Ondrej Vana (kachna@keboola.com)
 * @package        keboola/json-parser
 * @copyright    Copyright (c) 2014 Keboola Data Services (www.keboola.com)
 * @license        GPL-3.0
 * @link        https://github.com/keboola/php-jsonparser
 *
 * @todo Use a $file parameter to allow writing the same
 *         data $type to multiple files
 *         (ie. type "person" to "customer" and "user")
 *
 */
class Parser
{

    public $tableLookup = [];
    public $tableLookup2 = [];
    public $pkLookup = [];
    public $pkRowLookup = [];

    /**
     * Column name for an array of scalars
     */
    const DATA_COLUMN = 'data';

    /**
     * Headers for each type
     * @var array
     */
    protected $headers = [];

    /**
     * @var Table[]
     */
    protected $csvFiles = [];
    protected $csvTables = [];

    /**
     * @var Cache
     */
    protected $cache;

    /**
     * @var Logger
     */
    protected $log;

    /**
     * @var Temp
     */
    protected $temp;

    /**
     * @var array
     */
    protected $primaryKeys = [];

    public $rowIdLookup = [];


    /**
     * @var Analyzer
     */
    protected $analyzer;

    /**
     * @var Struct
     */
    protected $struct;

    public function __construct(Logger $logger, Analyzer $analyzer, Struct $struct)
    {
        $this->log = $logger;
        $this->analyzer = $analyzer;
        $this->struct = $struct;
    }

    /**
     * @param Logger $logger
     * @param array $struct should contain an array with previously
     *         cached results from analyze() calls (called automatically by process())
     * @param int $analyzeRows determines how many rows of data
     *         (counting only the "root" level of each Json)
     *         will be analyzed [default -1 for infinite/all]
     */
    public static function create(Logger $logger, array $definitions = [], $analyzeRows = -1)
    {
        $struct = new Struct($logger);
        $struct->load($definitions);
        $analyzer = new Analyzer($logger, $struct, $analyzeRows);

        return new static($logger, $analyzer, $struct);
    }

    /**
     * Analyze and store an array of data for parsing.
     * The analysis is done immediately, based on the analyzer settings,
     * then the data is stored using \Keboola\Json\Cache and parsed
     * upon retrieval using getCsvFiles().
     *
     * @param array $data
     * @param string $type is used for naming the resulting table(s)
     * @param string|array $parentId may be either a string,
     *         which will be saved in a JSON_parentId column,
     *         or an array with "column_name" => "value",
     *         which will name the column(s) by array key provided
     *
     * @return void
     *
     * @api
     */
    public function process(array $data, $type = "root", $parentId = null)
    {

        // The analyzer wouldn't set the $struct and parse fails!
        if ((empty($data) || $data == [null]) && !$this->struct->hasDefinitions($type)) {
            throw new NoDataException("Empty data set received for '{$type}'", [
                "data" => $data,
                "type" => $type,
                "parentId" => $parentId
            ]);
        }

        // Log it here since we shouln't log children analysis
        if (empty($this->analyzer->getRowsAnalyzed()[$type])) {
            $this->log->log("debug", "Analyzing {$type}", [
                "rowsAnalyzed" => $this->analyzer->getRowsAnalyzed(),
                "rowsToAnalyze" => count($data)
            ]);
        }

        $this->analyzer->analyze($data, $type);

        $this->getCache()->store([
            "data" => $data,
            "type" => $type,
            "parentId" => $parentId
        ]);


    }

    /**
     * Parse data of known type
     *
     * @param array $data
     * @param string $type
     * @param string|array $parentId
     * @return void
     * @see Parser::process()
     */
    public function parse($recordId, array $data, $type, $parentId = null)
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

        $parentId = $this->validateParentId($parentId, $recordId);

        $csvTable = $this->createCsvTable($type, $parentId);

        $parentCols = array_fill_keys(array_keys($parentId), "string");

        foreach ($data as $rowNum=>$row) {

            // This is to get the recordid when in root table (not sure I like how this is being checked)
            if ($type == "root") {
              $recordId = $rowNum;
            }


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

            $csvRow = $this->parseRow($recordId, $rowNum, $row, $type, $parentCols);

            if (is_numeric($rowNum)) {
              $rowId = $csvRow->calculateRowId($recordId.'-'.$rowNum.'-');
            } else {
              $rowId = $csvRow->calculateRowId($recordId.'-');
            }

            $csvRow->addRecordId($recordId);

            if (!isset($this->csvTables[$type])) {
              $this->csvTables[$type] = [];
              $this->csvTables[$type][] = $csvRow;
            } else {
              $this->csvTables[$type][] = $csvRow;
            }



        }

    }

    public function postParse() {

      foreach ($this->csvTables as $type=>$csvRows) {
        foreach ($csvRows as $rowNum=>$csvRow) {
          $colVals = $csvRow->getRow();
          $recordId = $colVals['@RECORDID'];
          $rowId = $colVals['@ROWID'];

          $recordPrimaryKeys = array_keys($this->pkLookup[$recordId]);

          foreach ($colVals as $col=>$val) {
            if (in_array($val,$recordPrimaryKeys) && $col !== "@JSONPARENTID") {
              //$this->csvTables[$type][$rowNum]->addJsonChild($type.".".str_replace("_",".",$col));
              //$this->pkRowLookup[$recordId][$val][$this->pkLookup[$recordId][$val]][$rowNum] = $rowId;
              //$this->pkRowLookup[$recordId][$val][$type][$rowNum][$col] = $rowId;
              $this->pkRowLookup[$recordId][$val][$type.".".str_replace("_",".",$col)]['parent_rowid'] = $rowId;
              $this->pkRowLookup[$recordId][$val][$type.".".str_replace("_",".",$col)]['parent_join_column'] = $col;
              $this->pkRowLookup[$recordId][$val][$type.".".str_replace("_",".",$col)]['parent_table'] = $type;
            }
          }

        }

      }

      foreach ($this->csvTables as $type=>$csvRows) {
        foreach ($csvRows as $rowNum=>$csvRow) {
          $colVals = $csvRow->getRow();
          $recordId = $colVals['@RECORDID'];
          $rowId = $colVals['@ROWID'];

          if (isset($colVals['@JSONPARENTID'])) {
            $this->csvTables[$type][$rowNum]->addJsonParent($this->pkRowLookup[$recordId][$colVals['@JSONPARENTID']][$type]['parent_table']);
            //$this->csvTables[$type][$rowNum]->addJsonParentColumn($this->pkRowLookup[$recordId][$colVals['@JSONPARENTID']][$type]['parent_join_column']);
            $this->csvTables[$type][$rowNum]->addParentRowId($this->pkRowLookup[$recordId][$colVals['@JSONPARENTID']][$type]['parent_rowid']);
            $this->csvTables[$type][$rowNum]->removeJsonParentId();
          }

        }
      }

    }

    /**
     * Parse a single row
     * If the row contains an array, it's recursively parsed
     *
     * @param \stdClass $dataRow Input data
     * @param string $type
     * @param array $parentCols to inject parent columns, which aren't part of $this->struct
     * @param string $outerObjectHash Outer object hash to distinguish different parents in deep nested arrays
     * @return CsvRow
     */
    protected function parseRow(
        $recordId,
        $rowNum,
        \stdClass $dataRow,
        $type,
        array $parentCols = [],
        $outerObjectHash = null
    ) {

        // move back out to parse/switch if it causes issues
        $csvRow = new CsvRow($this->getHeader($type, $parentCols));

        // Generate parent ID for arrays
        $arrayParentId = $this->getPrimaryKeyValue(
            $recordId,
            $rowNum,
            $dataRow,
            $type,
            $outerObjectHash
        );

        foreach (array_merge($this->getStruct()->getDefinitions($type), $parentCols) as $column => $dataType) {
            $this->parseField($recordId, $rowNum, $dataRow, $csvRow, $arrayParentId, $column, $dataType, $type);
        }

        return $csvRow;
    }

    /**
     * Handle the actual write to CsvRow
     * @param object $dataRow
     * @param CsvRow $csvRow
     * @param string $arrayParentId
     * @param string $column
     * @param string $dataType
     * @param string $type
     * @return void
     */
    protected function parseField(
        $recordId,
        $rowNum,
        \stdClass $dataRow,
        CsvRow $csvRow,
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
                $this->parse($recordId, $dataRow->{$column}, $type . "." . $column, $arrayParentId);

                break;
            case "object":
                $childRow = $this->parseRow($recordId, $rowNum, $dataRow->{$column}, $type . "." . $column, [], $arrayParentId);

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

    /**
     * Get header for a data type
     * @param string $type Data type
     * @param string|array $parent String with a $parentId or an array with $colName => $parentId
     * @return array
     */
    protected function getHeader($type, $parent = false)
    {
        $header = [];

        foreach ($this->struct->getDefinitions($type) as $column => $dataType) {
            if ($dataType == "object") {
                foreach ($this->getHeader($type . "." . $column) as $val) {
                    // FIXME this is awkward, the createSafeName shouldn't need to be used twice
                    // (here and in validateHeader again)
                    // Is used to trim multiple "_" in column name before appending
                    $header[] = $this->createSafeName($column) . "_" . $val;

                }
            } else {
                $header[] = $column;
            }
        }

        if ($parent) {
            if (is_array($parent)) {
                $header = array_merge($header, array_keys($parent));
            } else {
                $header[] = "@JSONPARENTID";
            }
        }

        // TODO set $this->headerNames[$type] = array_combine($validatedHeader, $header);
        // & add a getHeaderNames fn()
        $headerToReturn = $this->validateHeader($header);

        return $headerToReturn;
    }

    /**
     * Validate header column names to comply with MySQL limitations
     *
     * @param array $header Input header
     * @return array
     */
    protected function validateHeader(array $header)
    {
        $newHeader = [];
        foreach ($header as $key => $colName) {
            $newName = $this->createSafeName($colName);

            // prevent duplicates
            if (in_array($newName, $newHeader)) {
                $newHeader[$key] = md5($colName);
            } else {
                $newHeader[$key] = $newName;
            }
        }
        return $newHeader;
    }

    /**
     * Removed safe name creation for now - just returns same value as passed
     *
     */
    protected function createSafeName($name)
    {
        return $name;
    }



    protected function createCsvFile($type, $parentId)
    {
      /*
        $safeType = $this->createSafeName($type);
        if (empty($this->csvFiles[$safeType])) {
            $this->csvFiles[$safeType] = Table::create(
                $safeType,
                $this->headers[$type],
                $this->getTemp()
            );
            $this->csvFiles[$safeType]->addAttributes(["fullDisplayName" => $type]);
            if (!empty($this->primaryKeys[$safeType])) {
                $this->csvFiles[$safeType]->setPrimaryKey($this->primaryKeys[$safeType]);
            }
        }
        return $this->csvFiles[$safeType];
        */
    }

    /**
     * @todo Add a $file parameter to use instead of $type
     * to allow saving a single type to different files
     *
     * @param string $type
     * @return Table
     */
    protected function createCsvTable($type, $parentId)
    {

        if (empty($this->headers[$type])) {
            $this->headers[$type] = $this->getHeader($type, $parentId);
        }

        $this->headers[$type][] = "@ROWID";
        $this->headers[$type][] = "@RECORDID";

        $safeType = $this->createSafeName($type);

        if (empty($this->csvFiles[$safeType])) {
            $this->csvFiles[$safeType] = Table::create(
                $safeType,
                $this->headers[$type],
                $this->getTemp()
            );
            $this->csvFiles[$safeType]->addAttributes(["fullDisplayName" => $type]);
            if (!empty($this->primaryKeys[$safeType])) {
                $this->csvFiles[$safeType]->setPrimaryKey($this->primaryKeys[$safeType]);
            }
        }

        return $this->csvFiles[$safeType];
    }

    /**
     * @param \stdClass $dataRow
     * @param string $type for logging
     * @param string $outerObjectHash
     * @return string
     */
    protected function getPrimaryKeyValue($recordId, $rowNum, \stdClass $dataRow, $type, $outerObjectHash = null)
    {

        /*
        // Try to find a "real" parent ID
        if (!empty($this->primaryKeys[$this->createSafeName($type)])) {
            $pk = $this->primaryKeys[$this->createSafeName($type)];
            $pKeyCols = explode(',', $pk);
            $pKeyCols = array_map('trim', $pKeyCols);
            $values = [];
            foreach ($pKeyCols as $pKeyCol) {
                if (empty($dataRow->{$pKeyCol})) {
                    $values[] = md5(serialize($dataRow) . $outerObjectHash);
                    $this->log->log(
                        "WARNING",
                        "Primary key for type '{$type}' was set to '{$pk}', but its column '{$pKeyCol}' does not exist! Using hash to link child objects instead.",
                        ['row' => $dataRow]
                    );
                } else {
                    $values[] = $dataRow->{$pKeyCol};
                }
            }
            return $type . "-" . join(";", $values);
        } else {
        */
            // Of no pkey is specified to get the real ID, use a hash of the row
            $primaryKey = $type . "-" . $recordId . "-" . $rowNum. "-". sha1(serialize($dataRow) . $outerObjectHash);

            $this->pkLookup[$recordId][$primaryKey] = $type;

            return $primaryKey;
        /*
        }
        */
    }

    /**
     * Ensure the parentId array is not multidimensional
     *
     * @param string|array $parentId
     * @return array
     */
    protected function validateParentId($parentId, $recordId)
    {
        if (!empty($parentId)) {
            if (is_array($parentId)) {
                if (count($parentId) != count($parentId, COUNT_RECURSIVE)) {
                    throw new JsonParserException(
                        'Error assigning parentId to a CSV file! $parentId array cannot be multidimensional.',
                        [
                            'parentId' => $parentId
                        ]
                    );
                }
            } else {
                $parentId = ['@JSONPARENTID' => $parentId];
            }
        } else {
            $parentId = [];
        }

        return $parentId;
    }

    /**
     * Returns an array of CSV files containing results
     * @return Table[]
     */
    public function getCsvTables()
    {
        // parse what's in cache before returning results
        $this->processCache();
        ksort($this->csvTables);
        return $this->csvTables;
    }


    public function getCsvTableDetails($analyzeVals=FALSE) {
      $tables = [];

      foreach ($this->csvTables as $table=>$csvRows) {
        $tables[$table] = array();

        $tables[$table]['column_count'] = count($csvRows[0]->getRow());
        $tables[$table]['row_count'] = count($csvRows);

        foreach ($csvRows[0]->getRow() as $column=>$val) {

          if ($column == "@RECORDID" && $table == 'root') {
            $unique = TRUE;
          } else {
            $unique = FALSE;
          }

          if ($column == "@ROWID") {
            $primaryKey = TRUE;
          } else {
            $primaryKey = FALSE;
          }

          if ($column == "@PARENT") {

            $tables[$table]['relationships'][$val]['from'] = array('table'=>$table, 'column'=>'@PARENTROWID');
            $tables[$table]['relationships'][$val]['to'] = array('table'=>$val, 'column'=>'@ROWID');

            $tables[$table]['relationships']['@RECORDID']['from'] = array('table'=>$table, 'column'=>'@RECORDID');
            $tables[$table]['relationships']['@RECORDID']['to'] = array('table'=>'root', 'column'=>'@RECORDID');

          }


          $tables[$table]['column'][$column]['primarykey'] = $primaryKey;
          $tables[$table]['column'][$column]['unique'] = $unique;

        }

        if ($analyzeVals == TRUE) {

          $vals = array();
          foreach ($csvRows as $rowNum=>$row) {
            foreach ($row->getRow() as $column=>$val) {
              $vals[$column][] = $val;
            }
          }

          foreach ($csvRows as $rowNum=>$row) {
            foreach ($row->getRow() as $column=>$val) {
              $tables[$table]['column'][$column]['value_analysis']['minmax_length'] = $this->analyzeMinMaxLengthVals($vals[$column]);
              $tables[$table]['column'][$column]['value_analysis']['isNumericOrNull_percent'] = $this->analyzeNumericVals($vals[$column]);
              $tables[$table]['column'][$column]['value_analysis']['isBoolOrNull_percent'] = $this->analyzeBooleanVals($vals[$column]);
              $tables[$table]['column'][$column]['value_analysis']['isNull_percent'] = $this->analyzeBooleanVals($vals[$column]);
              $tables[$table]['column'][$column]['value_analysis']['isDate_percent'] = $this->analyzeDateVals($vals[$column]);
            }
          }


        }


      }

      ksort($tables);
      return $tables;
    }


    private function analyzeDateVals($vals) {
      $totalCount = count($vals);
      $dateCount = 0;

      foreach ($vals as $k=>$v) {
        $d = date_parse($v);
        if ($d['error_count'] == 0 || $v == null) {
          $dateCount++;
        }

      }

      return $dateCount / $totalCount;
    }

    private function analyzeMinMaxLengthVals($vals) {
      $min = null;
      $max = null;

      foreach ($vals as $k=>$v) {
        $len = strlen($v);

        if ($min == null && $max == null) {
          $min = $len;
          $max = $len;
          continue;
        }

        if ($len < $min) {
          $min = $len;
        }

        if ($len > $max) {
          $max = $len;
        }

      }


      return array('min'=>$min, 'max'=>$max);
    }

    private function analyzeNumericVals($vals) {
      $totalCount = count($vals);
      $numericCount = 0;

      foreach ($vals as $k=>$v) {
        if (strlen($v) !== 0) {
          if (is_numeric($v)) {
            $numericCount++;
          }
        } else {
          $numericCount++;
        }
      }
      return $numericCount / $totalCount;
    }

    private function analyzeBooleanVals($vals) {
      $totalCount = count($vals);
      $booleanCount = 0;

      foreach ($vals as $k=>$v) {
        if (strlen($v) !== 0) {
          if (is_bool($v)) {
            $booleanCount++;
          }
        } else {
          $booleanCount++;
        }
      }
      return $booleanCount / $totalCount;
    }

    private function analyzeNullVals($vals) {
      $totalCount = count($vals);
      $nullCount = 0;

      foreach ($vals as $k=>$v) {
        if (strlen($v) !== 0) {
          if (is_null($v)) {
            $nullCount++;
          }
        } else {
          $nullCount++;
        }
      }
      return $nullCount / $totalCount;
    }

    /**
     * @return Cache
     */
    protected function getCache()
    {
        if (empty($this->cache)) {
            $this->cache = new Cache();
        }

        return $this->cache;
    }

    /**
     * @return void
     */
    public function processCache()
    {

        if (!empty($this->cache)) {
            while ($batch = $this->cache->getNext()) {
                $this->parse(null, $batch["data"], $batch["type"], $batch["parentId"]);
            }
        }

        $this->postParse();
    }

    /**
     * @return Struct
     */
    public function getStruct()
    {
        return $this->struct;
    }

    /**
     * Version of $struct array used in parser
     * @return double
     * @deprecated use Struct::getStructVersion()
     */
    public function getStructVersion()
    {
        return $this->getStruct()->getStructVersion();
    }

    /**
     * Returns (bool) whether the analyzer analyzed anything in this instance
     * @return bool
     * @deprecated
     */
    public function hasAnalyzed()
    {
        return !empty($this->getAnalyzer()->getRowsAnalyzed());
    }

    /**
     * @return Analyzer
     */
    public function getAnalyzer()
    {
        return $this->analyzer;
    }

    /**
     * Initialize $this->temp
     * @return Temp
     */
    protected function getTemp()
    {
        if (!($this->temp instanceof Temp)) {
            $this->temp = new Temp("ex-parser-data");
        }
        return $this->temp;
    }

    /**
     * Override the self-initialized Temp
     * @param Temp $temp
     */
    public function setTemp(Temp $temp)
    {
        $this->temp = $temp;
    }

    /**
     * @param array $pks
     */
    public function addPrimaryKeys(array $pks)
    {
        if (!empty($this->csvFiles)) {
            throw new JsonParserException('"addPrimaryKeys" must be used before any data is parsed');
        }

        $this->primaryKeys += $pks;
    }

    /**
     * Set maximum memory used before Cache starts using php://temp
     * @param string|int $limit
     */
    public function setCacheMemoryLimit($limit)
    {
        return $this->getCache()->setMemoryLimit($limit);
    }
}
