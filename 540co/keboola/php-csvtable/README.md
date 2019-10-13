# Keboola CsvTable

## Description
A class that extends Keboola\CsvFile functionality by adding Keboola StorageApi Attribute and PrimaryKey variables

## Usage

```php
    use Keboola\CsvTable\Table;
	$table = new Table('name', ['id', 'column', 'names']);
    $table->writeRow(['1','row','data']);
    $table->addAttributes(['created_by' => $username]);
    $table->setPrimaryKey('id');
```

Result:

```csv
	id,column,names
	"1","row","data"
```
