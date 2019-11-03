# DoD President Budget Procurement / RDTE Justification Book Data Export

## What is this?

This repo contains an extract of the public **DoD [Procurement](https://dap.dau.mil/acquipedia/Pages/ArticleDetails.aspx?aid=9be81897-aae7-4b76-8887-f9334c6d77af) (P-1) and [RDTE](https://dap.dau.mil/acquipedia/Pages/ArticleDetails.aspx?aid=e933639e-b773-4039-9a17-2eb20f44cf79) (R-1) [justification book](http://comptroller.defense.gov/BudgetMaterials.aspx) exhibits** submitted by the US DoD Military Departments and Defense Agencies into **multiple data formats** to allow analysts / users to use their tool of choice when analyzing and/or merging with other data sources.

The extract includes:

- 2013 base budget
- 2014 base / amended budget
- 2015 base / amended budget
- 2016 base budget
- 2017 base budget
- 2018 base / amended budget
- 2019 base budget
- 2020 base / amended

>Unfort, prior to 2013, there were no machine readable files available to fetch / parse the data

## What data / formats are available in the repo?

### PDF + ATTACHMENTS (xml, xls, etc)
Included in the `0-jbook-pdf` folder is the **PDF** downloaded from the the DoD / Service budget sites and the **extracted attachments** (which is where the XML representation of the justificaton books is stored in addition to other attachments used to build the justification book).

The URLs that were used for the download are included in a a JSON file at the root of the `0-jbook-pdf` folder "per year".

_For example, there is a file `[2020]_jbook_list.json` that includes the URL that was used to download the files for that year._ 

![0-jbook-pdf](https://github.com/540co/dod-jbook-pdf-xml/blob/master/docs/0-jbook-pdf.png?raw=true)

> NOTE:  In some cases (mainly for the older files), the URLs are no longer valid since the sites where the PDFs are hosted have evolved / changed over time.  Most of the older files were downloaded when first released and the URL listed would have been the URL at that time.

### JUSTIFICATION / MASTER JUSTIFICATION BOOK XML FILES
All of the relevant *JustificationBook* / *MasterJustificationBook* XML files have been copied to a single folder `1-jbook-xml` prior to further processing.

![1-jbook-pdf](https://github.com/540co/dod-jbook-pdf-xml/blob/master/docs/1-jbook-xml.png?raw=true)


### JUSTIFICATION / MASTER JUSTIFICATION BOOK JSON FILES
This folder contains a **JSON representation** of each of the XML files contained in `1-jbook-pdf` **following the conversion of the XML to JSON**.

![2-jbook-json](https://github.com/540co/dod-jbook-pdf-xml/blob/master/docs/2-jbook-json.png?raw=true)


### JSON PROCUREMENT-LINEITEMS -and- RDTE-PROGRAMELEMENTS
Each folder contains the "list" of **PROCUREMENT line items** / **RDTE program elements**, respectively in **JSON format**.  

Each **line item** / **program element** is stored in a single unique file (1 file per line item, 1 file per program element).

![3-json](https://github.com/540co/dod-jbook-pdf-xml/blob/master/docs/3-json.png?raw=true)

### CSV PROCUREMENT-LINEITEMS -and- RDTE-PROGRAMELEMENTS
Each folder contains the "list" of **PROCUREMENT line items** / **RDTE program elements**, respectively in **CSV format**.  

The CSV conversion from JSON to CSV results in a "file" subject area within a **line item** / **program element** using the JSON structure as a guide of what can be "flattened" into rows and what requires a separate file to represent another list of rows. 

Each CSV file is "zipped" due to file size.

![4-csv](https://github.com/540co/dod-jbook-pdf-xml/blob/master/docs/4-csv.png?raw=true)

At the root of each folder is a README.md and ERD that shows the list of tables, columns and structure.

#### CSV-PROCUREMENT-LINEITEMS

##### README.md
https://github.com/540co/dod-jbook-pdf-xml/blob/master/4-csv-procurement-lineitems/README.md

##### ERD
(PDF) https://github.com/540co/dod-jbook-pdf-xml/blob/master/4-csv-procurement-lineitems/procurement-lineitems.pdf

(PNG) https://github.com/540co/dod-jbook-pdf-xml/blob/master/4-csv-procurement-lineitems/procurement-lineitems.png

(DOT) https://github.com/540co/dod-jbook-pdf-xml/blob/master/4-csv-procurement-lineitems/procurement-lineitems.dot

> The DOT file follows the GraphViz format (https://www.graphviz.org/) and is used to render the PDF / PNG file.


#### CSV-RDTE-PROGRAMELEMENTS
##### README.md

https://github.com/540co/dod-jbook-pdf-xml/blob/master/4-csv-rdte-programelements/README.md

##### ERD
(PDF) https://github.com/540co/dod-jbook-pdf-xml/blob/master/4-csv-rdte-programelements/rdte-programelements.pdf

(PNG) https://github.com/540co/dod-jbook-pdf-xml/blob/master/4-csv-rdte-programelements/rdte-programelements.png

(DOT) https://github.com/540co/dod-jbook-pdf-xml/blob/master/4-csv-rdte-programelements/rdte-programelements.dot

> The DOT file follows the GraphViz format (https://www.graphviz.org/) and is used to render the PDF / PNG file.


###### NOTE
Contained in each folder is also a `-j` zip file that contains a JSON representation of the data just prior to the CSV conversion.  This was mainly used for troubleshooting, but also may be helpful when importing the data in cases where a flattened JSON representation may be easies to parse / ingest than the CSV.



## How were the file processed?

The attached `process.php` includes all of the code used to processed the files (in coordination with a few libraries) and a flow of the steps is show in the diagram below:

![4-csv-rdte-programelements](https://github.com/540co/dod-jbook-pdf-xml/blob/master/docs/dod-jbook-pdf-xml-json-csv.png?raw=true)

https://github.com/540co/dod-jbook-pdf-xml/blob/master/docs/dod-jbook-pdf-xml-json-csv.pdf?raw=true

To run `processs.php` CLI you will need to have the following installed:

- PHP 7.1.x
- Graphviz (https://www.graphviz.org/)

> All test runs have been done on Mac OSX and/or AWS linux - and most likely will be incompatible with Windows during various steps.

### Step 0: Download JBooks / Extract attachements

- Prepare `[YEAR]_jbook_list.json` following the format of the other files and then run the CLI:

```
$ php process.php --step 0-download-jbooks --jbook-list [YEAR]_jbook_list.json

```

> When downloading XML files for the year, the entire folder is removed and recreated on each run of the script.  Therefore, if downloads are interrupted you will have to re-download the files to ensure all files are downloaded.


### Step 1: Copy Jbooks to single folder

```
$ php process.php --step 1-copy-jbook-xml-to-single-folder

```

> When this step is run, the `1-json` folder will be automatically created.


### Step 2: Analyze XML Jbooks to determine array paths

This step is important to analyze all of the jbook xml files to determine the "lists" within the XML to allow for a consistent JSON conversion in which lists are properly converted to JSON arrays.

This is important due to the fact this conversion is done without the XSD that defines the XML schema per year.

The result of this process is a `jbookArrays.json` file that includes a list of all of the paths that should be arrays.

```
$ php process.php --step 2-determine-jbook-array-paths

```

> This step can take a long time depending on the spec of the machine (~12-24 hours).

### Step 3: Convert XML Jbooks to JSON

This step leverages the `jbookArrays.json` and converts each Jbook XML file into a JSON file and writes it to the `2-jbook-json` folder.

```
$ php process.php --step 3-convert-xml-to-json

```

> This step can take a long time depending on the spec of the machine (~12-24 hours). 


### Step 4: Process Jbooks by copying out line items / program elements into a single file per line item / program element

During this step line items / program elements are extracted from the proper node in the Justification Book / Master Justification Book and copied to a single JSON file per line item / progrem element:

**procurement-lineitems** in Justification Books
`JustificationBook/LineItemList/LineItem`

**procurement-lineitems** in Master Justification Books
`MasterJustificationBook/JustificationBookGroupList/JustificationBookGroup/JustificationBookInfoList/JustificationBook/LineItemList/LineItem`

**rdte-programelements** in Justification Books (2013 - 2016)
`JustificationBook/R2ExhibitList/R2Exhibit`

**rdte-programelements** in Master Justification Books (2013 - 2016)
`MasterJustificationBook/JustificationBookGroupList/JustificationBookGroup/JustificationBookInfoList/JustificationBook/R2ExhibitList/R2Exhibit`

**rdte-programelements** in Justification Books (2017 - current)
`JustificationBook/R2ExhibitList/R2Exhibit`

**rdte-programelements** in Master Justification Books (2017 - current)
``MasterJustificationBook/JustificationBookGroupList/JustificationBookGroup/JustificationBookInfoList/JustificationBook/ProgramElementList/ProgramElement`

```
$ php process.php --step 4-process-json-docs

```

### Step 5: Convert JSON files to CSV

During this step, each JSON file is analyzed and flattened / converted to a collection of CSV files. 

In addition, an ERD and README file is created dynamically based upon the data converted.

```
$ php process.php --step 5-json-to-csv --resource-type procurement-lineitems
$ php process.php --step 5-json-to-csv --resource-type rdte-programelements

```

### Step 6: Zip CSV output

During this step, the CSV files (and associated `-j` files) are compressed for each portability / storage in repos (such as Github).

```
$ php process.php --step 6-csv-to-zip --resource-type procurement-lineitems
$ php process.php --step 6-csv-to-zip--resource-type rdte-programelements

```
