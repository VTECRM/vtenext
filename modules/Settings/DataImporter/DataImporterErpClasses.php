<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@65455 - some utility classes for the importer */

// basic exception
class DataImporterException extends Exception {
};

// the log class
class DataImporterLogger extends VTEMultiLogger {
}

// extends the csv reader to integrate it with the erp import
require_once('modules/Import/api/UserInput.php');
require_once('modules/Import/readers/CSVReader.php');

class DataImporterErpCSVReader extends Import_CSV_Reader {

	public $headers;
	public $delimiter;
	public $currentRow = -1;
	
	public function __construct($user, $config=array()) {
		$uinput = new Import_API_UserInput();
		foreach ($config as $cfg=>$val) {
			$uinput->set($cfg, $val);
		}
		parent::__construct($uinput, $user);
	}
	
	public function setFHandle($fhandle) {
		$currentFh = $this->getFileHandler();
		if ($currentFh !== $fhandle) {
			$this->currentRow = -1;
			$this->headers = null;
			$this->delimiter = null;
			$this->userInputObject->set('fhandle', $fhandle);
		}
	}
	
	public function getFileHandler() {
		return $this->userInputObject->get('fhandle');
	}
	
	public function readRow() {
		global $default_charset;
		
		$mappedData = array();
		$fileHandler = $this->getFileHandler();
		if (!$fileHandler) return $mappedData;
		
		$hasHeader = $this->userInputObject->get('has_header');
		if (empty($this->delimiter)) {
			$this->delimiter = $this->userInputObject->get('delimiter');
			if ($this->delimiter == 'AUTO') $this->delimiter = $this->detectDelimiter();
		}
		
		while($data = fgetcsv($fileHandler, 0, $this->delimiter)) {
			$this->currentRow++;
			if ($hasHeader && $this->currentRow == 0) {
				if (empty($this->headers)) {
					// populate the header
					foreach($data as $key => $value) {
						$this->headers[$key] = $this->convertCharacterEncoding($value, $this->userInputObject->get('file_encoding'), $default_charset);
					}
				}
				// skip row anyway
				continue;
			}
			$mappedData = array();
			$allValuesEmpty = true;
			
			foreach($data as $key => $value) {
				if ($this->userInputObject->get('trim_values')) $value = trim($value);
				$mappedData[$key] = $this->convertCharacterEncoding($value, $this->userInputObject->get('file_encoding'), $default_charset);
				if($value !== '') $allValuesEmpty = false;
			}

			// skip the row if all fields are empty
			if($allValuesEmpty) continue;
			break;
		}

		if ($data && $hasHeader) {
			$noOfHeaders = count($this->headers);
			$noOfFirstRowData = count($mappedData);
			// Adjust first row data to get in sync with the number of headers
			if($noOfHeaders > $noOfFirstRowData) {
				$mappedData = array_merge($mappedData, array_fill($noOfFirstRowData, $noOfHeaders-$noOfFirstRowData, ''));
			} elseif($noOfHeaders < $noOfFirstRowData) {
				$mappedData = array_slice($mappedData, 0, count($this->headers), true);
			}
			$mappedData = array_combine($this->headers, $mappedData);			
		}
		
		return $mappedData;
	}
	
}