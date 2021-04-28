<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

require_once 'modules/Import/readers/FileReader.php';

class Import_CSV_Reader extends Import_File_Reader {

	// crmv@56463
	public function detectDelimiter() {
		// the idea is to check the number of possible delimiters for the first lines, 
		// they should be the same number, so i choose the delimiter with the smallest discrepancy
			
		//detect these delimeters
		$delA = array(";", ",");
		$linesA = array();
		$resultA = array();

		$maxLines = 10; // maximum lines to parse for detection, this can be higher for more precision
		
		//load lines
		$fileHandler = $this->getFileHandler();
		foreach ($delA as $key => $del) {
			$rowNum = 0;
			$linesA[$key] = array();
			while ((($data = fgetcsv($fileHandler, 0, $del)) !== false) && ($rowNum < $maxLines)) {
				$linesA[$key][] = count($data);
				$rowNum++;
			}
			rewind($fileHandler);
		}
		unset($fileHandler);

		//count rows delimiter number discrepancy from each other
		foreach ($delA as $key => $del) {
			$discr = 0;
			foreach ($linesA[$key] as $actNum) {
				if ($actNum == 1) {
					$resultA[$key] = 65535; //there is only one column with this delimeter in this line, so this is not our delimiter, set this discrepancy to high
					break;
				}

				foreach ($linesA[$key] as $actNum2) {
					$discr += abs($actNum - $actNum2);
				}

				//if its the real delimeter this result should the nearest to 0
				//because in the ideal (errorless) case all lines have same column number
				$resultA[$key] = $discr;
			}
		}

		//select the discrepancy nearest to 0, this would be our delimiter
		$delRes = 65535;
		foreach ($resultA as $key => $res) {
			if ($res < $delRes) {
				$delRes = $res;
				$delKey = $key;
			}
		}
		$delimeter = $delA[$delKey];
		
		// fallback on ,
		if (empty($delimeter)) $delimeter = ',';

		return $delimeter;
	}
	// crmv@56463e

	public function getFirstRowData($hasHeader=true) {
		global $default_charset;

		$fileHandler = $this->getFileHandler();

		$headers = array();
		$firstRowData = array();
		$currentRow = 0;
		// crmv@56463
		$delimiter = $this->userInputObject->get('delimiter');
		if ($delimiter == 'AUTO') $delimiter = $this->detectDelimiter();
		while($data = fgetcsv($fileHandler, 0, $delimiter)) {
		// crmv@56463e
			if($currentRow == 0 || ($currentRow == 1 && $hasHeader)) {
				if($hasHeader && $currentRow == 0) {
					foreach($data as $key => $value) {
						$headers[$key] = $this->convertCharacterEncoding($value, $this->userInputObject->get('file_encoding'), $default_charset);
					}
				} else {
					foreach($data as $key => $value) {
						$firstRowData[$key] = $this->convertCharacterEncoding($value, $this->userInputObject->get('file_encoding'), $default_charset);
					}
					break;
				}
			}
			$currentRow++;
		}	

		if($hasHeader) {
			$noOfHeaders = count($headers);
			$noOfFirstRowData = count($firstRowData);
			// Adjust first row data to get in sync with the number of headers
			if($noOfHeaders > $noOfFirstRowData) {
				$firstRowData = array_merge($firstRowData, array_fill($noOfFirstRowData, $noOfHeaders-$noOfFirstRowData, ''));
			} elseif($noOfHeaders < $noOfFirstRowData) {
				$firstRowData = array_slice($firstRowData, 0, count($headers), true);
			}
			$rowData = array_combine($headers, $firstRowData);
		} else {
			$rowData = $firstRowData;
		}
		
		//crmv@38558
		if($noOfHeaders != count($rowData) && ($hasHeader)){ //duplicate columns found //crmv@47738
			$this->status = 'failed';
			$this->errorMessage = "DUPLICATE_COLUMNS";
			return false;
		}
		//crmv@38558e

		unset($fileHandler);
		return $rowData;
	}

	public function read() {
		set_time_limit(300);
		global $default_charset;
		
		$fileHandler = $this->getFileHandler();
		$status = $this->createTable();
		
		if(!$status) {
			return false;
		}

		$fieldMapping = $this->userInputObject->get('field_mapping');

		$i=-1;
		// crmv@56463
		$delimiter = $this->userInputObject->get('delimiter');
		if ($delimiter == 'AUTO') $delimiter = $this->detectDelimiter();
		while($data = fgetcsv($fileHandler, 0, $delimiter)) {
		// crmv@56463e
			$i++;
			if($this->userInputObject->get('has_header') && $i == 0) continue;
			$mappedData = array();
			$allValuesEmpty = true;
			foreach($fieldMapping as $fieldName => $index) {
				$fieldValue = $data[$index];
				$mappedData[$fieldName] = $fieldValue;
				if($this->userInputObject->get('file_encoding') != $default_charset) {
					$mappedData[$fieldName] = $this->convertCharacterEncoding($fieldValue, $this->userInputObject->get('file_encoding'), $default_charset);
				}
				if(!empty($fieldValue)) $allValuesEmpty = false;
			}
			if($allValuesEmpty) continue;
			$fieldNames = array_keys($mappedData);
			$fieldValues = array_values($mappedData);
			
			$this->addRecordToDB($fieldNames, $fieldValues);
		}
		$this->flushBuffer(); // crmv@156317
		unset($fileHandler);
	}
}
?>