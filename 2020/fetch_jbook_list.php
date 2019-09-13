<?php

$working_directory = getcwd();

$toc = json_decode(file_get_contents('jbook_list.json'), TRUE);

// Cleanup any existing folders
echo "=====================================================================================\n";
foreach ($toc as $type=>$year_list) {
    echo "Removing folder $type and creating new one...\n";
	exec('rm -Rf '.$type);
}
echo "=====================================================================================\n";

echo "Preparing to download files...\n";
sleep(2);

foreach ($toc as $type=>$year_list) {

	foreach ($year_list as $year=>$pdf_list) {

		foreach ($pdf_list as $folder_name=>$pdf_url) {

        $folder = $working_directory."/".$type."/".$year."/".$folder_name;

        echo "Creating folder $folder\n";
        mkdir($folder,NULL,TRUE);

		echo "-------------------------------------------------------------------------------------\n";
		// curl file into folder
        echo "Getting $pdf_url and putting into folder $folder...\n";
        $pathinfo = pathinfo($pdf_url);

        $download_successful = FALSE;

        while ($download_successful == FALSE) {
            $c_cmd = 'sudo curl --url "'.$pdf_url.'" --output "'.$folder.'/'.$pathinfo['basename'].'"';
            echo $c_cmd."\n";
            exec($c_cmd);

            if (filesize($folder.'/'.$pathinfo['basename']) > 100) {
                $download_successful = TRUE;
                echo "-------------------------------------------------------------------------------------\n";
                echo "----FILE SUCCESSFULLY DOWNLOADED (".filesize($folder.'/'.$pathinfo['basename']).")----\n";
                echo "-------------------------------------------------------------------------------------\n";
            } else {
                $download_successful = FALSE;
                echo "-------------------------------------------------------------------------------------\n";
                echo "****ERROR DOWNLOADING FILE! (".filesize($folder.'/'.$pathinfo['basename']).") ****\n";
                echo "-------------------------------------------------------------------------------------\n";
                sleep(2);

            }

        }

		// extract attachments from PDF
		chdir($folder);

		foreach (glob('*.[pP][dD][fF]') as $filename) {
			echo "Extracting attachments from $folder/$filename...\n";
			exec('qpdf --decrypt "'.$filename.'" "d_'.$filename.'"');
			exec('rm "'.$filename.'"');
			exec('mv "d_'.$filename.'" "'.$filename.'"');
			exec('pdftk "'.$filename.'" unpack_files');
        }


		foreach (glob('*.[pP][dD][fF]*') as $filename) {
			echo "Extracting attachments from $folder/$filename...\n";
			exec('qpdf --decrypt "'.$filename.'" "d_'.$filename.'"');
			exec('rm "'.$filename.'"');
			exec('mv "d_'.$filename.'" "'.$filename.'"');
			exec('pdftk "'.$filename.'" unpack_files');
		}


		// find .zzz files within the $folder and unzip files to folder with same name as filename (with _unzipped as suffix)
		foreach (glob('*.[zZ][zZ][zZ]') as $filename) {
			echo "Unzipping $filename...\n";
			exec('unzip "'.$filename.'" -d "./'.$filename.'_unzipped"');
		}

		chdir($working_directory);

        echo "=====================================================================================\n";

        }
    }
}
