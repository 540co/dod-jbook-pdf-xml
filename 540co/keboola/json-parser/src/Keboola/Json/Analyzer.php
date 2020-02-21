<?php
namespace Keboola\Json;

use Keboola\Json\Exception\JsonParserException;
use Psr\Log\LoggerInterface;

class Analyzer
{
    /**
     * Structures of analyzed data
     * @var Struct
     */
    protected $struct;

    /**
     * @var int
     */
    protected $analyzeRows;

    /**
     * @var bool
     */
    protected $strict = false;

    /**
     * True if analyze() was called
     * @var bool
     */
    protected $analyzed;

    /**
     * Counts of analyzed rows per data type
     * @var array
     */
    protected $rowsAnalyzed = [];

    /**
     * @var bool
     */
    protected $nestedArrayAsJson = false;

    /**
     * @var LoggerInterface
     */
    protected $log;

    public function __construct(LoggerInterface $logger, Struct $struct = null, $analyzeRows = -1)
    {
        $this->log = $logger;
        $this->struct = $struct;
        $this->analyzeRows = $analyzeRows;
    }

    /**
     * Analyze an array of input data and save the result in $this->struct
     *
     * @param array $data
     * @param string $type
     * @return bool|null|string
     * @throws JsonParserException
     */

    public function analyze($fileName, $recordId, array $data, $type)
    {

        if ($this->isAnalyzed($type) || empty($data)) {
            return false;
        }

        $this->rowsAnalyzed[$type] = empty($this->rowsAnalyzed[$type])
            ? count($data)
            : ($this->rowsAnalyzed[$type] + count($data));

        $rowType = $this->getStruct()->getArrayType($type);

        foreach ($data as $rowIdx=>$row) {

            // ** ADDED TO INJECT A UNIQUE ROWID BASED UPON ROOT ID
            // ----

            if (isset($row->meta->filename)) {
                echo "{FILENAME: ".$row->meta->filename."}\n";
                $fileName = $row->meta->filename;
            }

            if (isset($row->id)) {
                echo "{RECORDID: ".$row->id."}\n";
                $recordId = $row->id;
            }

            $filenameSegments = explode("-",$fileName);
            $budgetYear = $filenameSegments[0];

            $row->{'@BUDGET_YEAR'} = $budgetYear;
            $row->{'@SOURCE_FILENAME'} = $fileName;
            $row->{'@RECORDID'} = hash('sha256',$recordId);
            $row->{'@ROWID'} = $this->createPrimaryKey($rowIdx,$row);

            echo "[$type][".$rowIdx."][".$recordId."][".$fileName."][".$budgetYear."]\n";

            // ----


            $newType = $this->analyzeRow($fileName, $recordId, $row, $type);
            if (!is_null($rowType)
                && $newType != $rowType
                && $newType != 'NULL'
                && $rowType != 'NULL'
            ) {
                throw new JsonParserException("Data array in '{$type}' contains incompatible data types '{$rowType}' and '{$newType}'!");
            }
            $rowType = $newType;
        }
        $this->analyzed = true;

        return $rowType;
    }

    // Added - @540CO
    private function createPrimaryKey($rownum,$row) {
        return $rownum.'-'.hash('sha256',random_bytes(200));
        /*
        $row = json_decode(json_encode($row), true);
        $stopFields = ['JSON_parentId'];

        $stringToHash = $rownum;

        foreach ($row as $fieldkey=>$fieldval) {
          if (!in_array($fieldkey,$stopFields)) {
            $stringToHash .= hash('sha256',json_encode($fieldval));
          }
        }

        //return $rownum.'-'.$stringToHash;
        return $rownum.'-'.hash('sha256',$stringToHash);
        */
      }


    protected function analyzeRow($fileName, $recordId, $row, $type)
    {
        // Current row's structure
        $struct = [];

        $rowType = $this->getType($row);

        // If the row is scalar, make it a {"data" => $value} object
        if (is_scalar($row)) {
            $struct[Parser::DATA_COLUMN] = $this->getType($row);
        } elseif (is_object($row)) {
            // process each property of the object
            foreach ($row as $key => $field) {
                $fieldType = $this->getType($field);

                if ($fieldType == "object") {
                    // Only assign the type if the object isn't empty
                    if (\Keboola\Utils\isEmptyObject($field)) {
                        continue;
                    }

                    $this->analyzeRow($fileName, $recordId, $field, $type . "." . $key);
                } elseif ($fieldType == "array") {
                    $arrayType = $this->analyze($fileName, $recordId, $field, $type . "." . $key);
                    if (false !== $arrayType) {
                        $fieldType = 'arrayOf' . $arrayType;
                    } else {
                        $fieldType = 'NULL';
                    }
                }
                $struct[$key] = $fieldType;
            }
        } elseif ($this->nestedArrayAsJson && is_array($row)) {
            $this->log->warning(
                "Unsupported array nesting in '{$type}'! Converting to JSON string.",
                ['row' => $row]
            );
            $rowType = $struct[Parser::DATA_COLUMN] = $this->strict ? 'string' : 'scalar';
        } elseif (is_null($row)) {
            // do nothing
        } else {
            throw new JsonParserException("Unsupported data row in '{$type}'!", ['row' => $row]);
        }

        $this->getStruct()->add($type, $struct);

        return $rowType;
    }

    /**
     * Returns data type of a variable based on 'strict' setting
     * @param mixed $var
     * @return string
     */
    public function getType($var)
    {
        return $this->strict ? gettype($var) :
            (is_scalar($var) ? 'scalar' : gettype($var));
    }

    /**
     * Check whether the data type has been analyzed (enough)
     * @param string $type
     * @return bool
     */
    public function isAnalyzed($type)
    {
        return $this->getStruct()->hasDefinitions($type)
            && $this->analyzeRows != -1
            && !empty($this->rowsAnalyzed[$type])
            && $this->rowsAnalyzed[$type] >= $this->analyzeRows;
    }

    /**
     * Read results of data analysis from $this->struct
     * @return Struct
     */
    public function getStruct()
    {
        if (empty($this->struct) || !($this->struct instanceof Struct)) {
            $this->struct = new Struct($this->log);
        }

        return $this->struct;
    }

    /**
     * @return array
     */
    public function getRowsAnalyzed()
    {
        return $this->rowsAnalyzed;
    }

    /**
     * If enabled, nested arrays will be saved as JSON strings instead
     * @param bool $bool
     */
    public function setNestedArrayAsJson($bool)
    {
        $this->nestedArrayAsJson = (bool) $bool;
    }

    /**
     * @return bool
     */
    public function getNestedArrayAsJson()
    {
        return $this->nestedArrayAsJson;
    }

    /**
     * Set whether scalars are treated as compatible
     * within a field (default = false -> compatible)
     * @param bool $strict
     */
    public function setStrict($strict)
    {
        $this->strict = (bool) $strict;
    }
}
