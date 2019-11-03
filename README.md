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

> NOTE:  Contained in each folder is also a `-j` zip file that contains a JSON representation of the data just prior to the CSV conversion.  This was mainly used for troubleshooting, but also may be helpful when importing the data in cases where a flattened JSON representation may be easies to parse / ingest than the CSV.



## How were the file processed?

The attached `process.php` includes all of the code used to processed the files (in coordination with a few libraries) and a flow of the steps is show in the diagram below:

![4-csv-rdte-programelements](https://github.com/540co/dod-jbook-pdf-xml/blob/master/docs/dod-jbook-pdf-xml-json-csv.png?raw=true)

https://github.com/540co/dod-jbook-pdf-xml/blob/master/docs/dod-jbook-pdf-xml-json-csv.pdf?raw=true

To run `processs.php` you will need to have the following installed:

- PHP 7.1.x
- Graphviz (https://www.graphviz.org/)

### Step 1: Download JBooks / Extract attachements

- Prepare `[YEAR]_jbook_list.json` following the format of the other files.
- From within the repo folder run `php process.php --step 0-download-jbooks --jbook-list [YEAR]_jbook_list.json`

> 





### Step 2

### Step 3

### Step 4

### Step 5

### Step 6

### Step 7









