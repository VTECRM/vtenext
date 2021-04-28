<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 global $table_prefix;

 $ImportConfig = array(
	'importTypes' => array(
						'csv' => array('reader' => 'Import_CSV_Reader', 'classpath' => 'modules/Import/readers/CSVReader.php'),
						'vcf' => array('reader' => 'Import_VCard_Reader', 'classpath' => 'modules/Import/readers/VCardReader.php'),
						'default' => array('reader' => 'Import_File_Reader', 'classpath' => 'modules/Import/readers/FileReader.php')
					),

	'userImportTablePrefix' => $table_prefix.'_import_',
	// crmv@200009 - limits moved to vteprop
);
