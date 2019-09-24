# Keboola PHP Temp service

## Usage

```php
    use Keboola\Temp\Temp;
	$temp = new Temp('prefix');
	// Creates a file with unique name suffixed by 'suffix'
    $tempFile = $temp->createTmpFile('suffix');

    // Creates a file 'filename.json' in the temp folder and keeps it after the class is destroyed
    $file = $temp->createFile('filename.json', true);
```
