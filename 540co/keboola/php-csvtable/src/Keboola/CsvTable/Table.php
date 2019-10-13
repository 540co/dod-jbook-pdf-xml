<?php

namespace Keboola\CsvTable;

use Keboola\Csv\CsvFile;
use Keboola\Temp\Temp;

/**
 * CsvFile class with attribute, primaryKey, incremental and name properties
 */
class Table extends CsvFile {
	/** @var array */
	protected $attributes = array();

	/** @var array */
	protected $primaryKey;

	/** @var Temp */
	protected $temp;

	/** @var string */
	protected $name;

	/** @var bool */
	protected $incremental = null;

	/**
	 * @brief Create a CSV file, and optionally set its header
	 *
	 * @param string $name File name Suffix
	 * @param array $header A header line to write into created file
	 * @param \Keboola\Temp\Temp $temp
	 * @return \Keboola\CsvTable\Table
	 */
	public static function create($name = '', array $header = array(), Temp $temp = null)
	{

		if ($temp == null) {
			$temp = new Temp('csv-table');
		}

		$tmpFile = $temp->createTmpFile($name);
		$csvFile = new self($tmpFile->getPathname());
		// Write header
		if (!empty($header)) {
			$csvFile->writeRow($header);
		}

		// Preserve Temp to prevent deletion!
		$csvFile->setTemp($temp);

		$csvFile->name = $name;

		return $csvFile;
	}

	/**
	 * @brief Resets all attributes to key:value pairs from $attributes
	 * @param array $attributes
	 */
	public function setAttributes(array $attributes)
	{
		$this->attributes = $attributes;
	}

	/**
	 * @brief Adds attributes as key:value pairs from $attributes
	 * Existing attributes will be replaced with new values
	 * @param array $attributes
	 */
	public function addAttributes(array $attributes)
	{
		$this->attributes = array_replace($this->attributes, $attributes);
	}

	/**
	 * @return array
	 */
	public function getAttributes()
	{
		return $this->attributes;
	}

	/**
	 * @brief Set incremental property
	 * @param bool $incremental
	 */
	public function setIncremental($incremental)
	{
		$this->incremental = (bool) $incremental;
	}

	/**
	 * @return bool
	 */
	public function getIncremental()
	{
		return $this->incremental;
	}

	/**
	 * @brief Set a primaryKey (to combine multiple columns, use array or comma separated col names)
	 * @param string|array $primaryKey
	 */
	public function setPrimaryKey($primaryKey)
    {
        if (!is_array($primaryKey)) {
            $primaryKey = explode(',', $primaryKey);
        }

		$this->primaryKey = $primaryKey;
	}

	/**
     * @param bool $asArray
	 * @return string
	 */
	public function getPrimaryKey($asArray = false)
	{
		return empty($this->primaryKey)
            ? null
            : (
                $asArray
                ? $this->primaryKey
                : join(',', $this->primaryKey)
            );
	}

	/**
	 * @param string
	 */
	public function setName($name) {
		$this->name = $name;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param Keboola\Temp\Temp
	 */
	public function setTemp(Temp $temp) {
		$this->temp = $temp;
	}
}
