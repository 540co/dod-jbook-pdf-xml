# Json2Table Parser

> This is a work in progress fork of the awesome parser written by Kebloola (https://github.com/keboola/php-jsonparser) to tweak the resulting structure to help with various projects in which we are using JSON representations of objects during transformation to different outputs (CSV, SQL, etc).

## Description

- Parse set of JSON strings and returns multiple tables that can be used for different scenarios (output to CSV, inserting in database, etc)
- Creates multiple tables from a single JSON, if said JSON contains numbered arrays
- Appends columns to resulting tables to reflect relationships between tables


## Usage

```php

$r = array();
$r['ff52436aa931128b4220047f67a956d2b3aab8eb'] = json_decode(file_get_contents('./tests/_data/lineitems/ff52436aa931128b4220047f67a956d2b3aab8eb.json'));
$r['ffa9b89f42e0e059df93cd3e79f328e1f11b359f'] = json_decode(file_get_contents('./tests/_data/lineitems/ffa9b89f42e0e059df93cd3e79f328e1f11b359f.json'));

$parser = \FiveFortyCo\Json\Parser::create(new \Monolog\Logger('json-parser'));
$parser->process($r);
$csvTables = $parser->getCsvTables();
$csvTableDefinition = $parser->getCsvTableDefinition();
$csvTableStatus = $parser->getCsvTableStats();

var_dump($csvTables);
var_dump($csvTableDefinition);
var_dump($csvTableStatus);

```


# Notes


## create(\Monolog\Logger $logger, $struct, $analyzeRows)
- $struct should contain an array with results from previous analyze() calls (called automatically by process())
- $analyzeRows determines, how many rows of data (counting only the "root" level of each Json)  will be analyzed [default -1 for infinite]

## process($data, $type, $parentId)
- Expects an array of results as the $data parameter
- $type is used for naming the resulting table(s)
- The $parentId may be either a string, which will be saved in a JSON_parentId column, or an array with "column_name" => "value", which will name the column(s) by array key provided
- Checks whether the data needs to be analyzed, and either analyzes or parses it into `$this->tables[$type]` ($type is polished to comply with SAPI naming requirements)
- If the data is analyzed, it is stored in Cache and **NOT PARSED** until $this->getCsvFiles() is called

## getCsvTables()
- returns a list of \Common\Rows objects with parse results

# Parse characteristics
The analyze function loops through each row of an array (generally an array of results) and passes the row into analyzeRow() method. If the row only contains a string, it's stored in a "data" column, otherwise the row should usually be an object, so each of the object's variables will be used as a column name, and it's value analysed:
- if it's a scalar, it'll be saved as a value of that column.
- if it's another object, it'll be parsed recursively to analyzeRow(), with it's variable names prepended by current object's name
	- example:
			"parent": {
				"child" : "value1"
			}
			will result into a "parent_child" column with a string type of "value1"
- if it's an array, it'll be passed to analyze() to create a new table, linked by JSON_parentId
