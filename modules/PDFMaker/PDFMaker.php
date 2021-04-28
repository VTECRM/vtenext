<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@17889	//crmv@19166
class PDFMaker {
	
	function __construct() {}
	
	function vtlib_handler($modulename, $event_type) {
		global $table_prefix;
		if($event_type == 'module.postinstall') {
			global $adb;

			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0 WHERE name=?', array($modulename));
			
			$this->importStandardLayouts();
			$this->importProdBlockTemplates(); // crmv@120993
						
			$moduleInstance = Vtecrm_Module::getInstance($modulename);
			$res = $adb->query('SELECT profileid FROM '.$table_prefix.'_profile');
			while ($row = $adb->fetchByAssoc($res)) {
				$adb->pquery('INSERT INTO '.$table_prefix.'_profile2standardperm(profileid,tabid,Operation,permissions) VALUES (?,?,?,?)',array($row['profileid'],$moduleInstance->id,0,0));
				$adb->pquery('INSERT INTO '.$table_prefix.'_profile2standardperm(profileid,tabid,Operation,permissions) VALUES (?,?,?,?)',array($row['profileid'],$moduleInstance->id,1,0));
				$adb->pquery('INSERT INTO '.$table_prefix.'_profile2standardperm(profileid,tabid,Operation,permissions) VALUES (?,?,?,?)',array($row['profileid'],$moduleInstance->id,2,0));
				$adb->pquery('INSERT INTO '.$table_prefix.'_profile2standardperm(profileid,tabid,Operation,permissions) VALUES (?,?,?,?)',array($row['profileid'],$moduleInstance->id,3,0));
				$adb->pquery('INSERT INTO '.$table_prefix.'_profile2standardperm(profileid,tabid,Operation,permissions) VALUES (?,?,?,?)',array($row['profileid'],$moduleInstance->id,4,0));
			}
			
			$sql1 = "SELECT module FROM ".$table_prefix."_pdfmaker GROUP BY module";
	        $result1 = $adb->query($sql1);
	        while($row = $adb->fetchByAssoc($result1))
	        {
	        	$relModuleInstance = Vtecrm_Module::getInstance($row["module"]);
	            Vtecrm_Link::addLink($relModuleInstance->id, 'LISTVIEWBASIC', 'PDF Export', "VTE.PDFMakerActions.getPDFListViewPopup2(this,'$"."MODULE$');", '', 1);
	            Vtecrm_Link::addLink($relModuleInstance->id, 'DETAILVIEWWIDGET', 'PDFMaker', "module=PDFMaker&action=PDFMakerAjax&file=getPDFActions&record=$"."RECORD$", '', 1);
	        }
	        
	        SDK::setMenuButton('contestual','LBL_ADD_TEMPLATE','window.location=\'index.php?module=PDFMaker&action=EditPDFTemplate\';','add','PDFMaker','index');
			SDK::setMenuButton('contestual','LBL_IMPORT','window.location=\'index.php?module=PDFMaker&action=ImportPDFTemplate\';','file_download','PDFMaker','index');
			SDK::setMenuButton('contestual','LBL_EXPORT','return VTE.PDFMaker.ExportTemplates();','file_upload','PDFMaker','index'); // crmv@158392
			SDK::setMenuButton('contestual','LBL_IMPORT','window.location=\'index.php?module=PDFMaker&action=ImportPDFTemplate\';','file_download','PDFMaker','ListPDFTemplates');
			SDK::setMenuButton('contestual','LBL_EXPORT','return VTE.PDFMaker.ExportTemplates();','file_upload','PDFMaker','ListPDFTemplates'); // crmv@158392

			global $php_max_execution_time;
			set_time_limit($php_max_execution_time);
			RecalculateSharingRules();
			
		} else if($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} else if($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} else if($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} else if($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} else if($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
	}

	// crmv@39106
	function generatePDFForEmail($idslist, $module, $templateids, $language = null) {
		require_once("modules/PDFMaker/InventoryPDF.php");
		require_once("include/mpdf/mpdf.php"); //crmv@30066

		global $adb, $table_prefix, $current_user, $current_language;

		if (!is_array($idslist)) $idslist = array($idslist);
		if (!is_array($templateids)) $templateids = array($templateids);
		if (empty($language)) $language = $current_language;

		$modFocus = CRMEntity::getInstance($module);
		$name = "";

		foreach($idslist as $record)
		{
			$modFocus->retrieve_entity_info($record,$module);
			$modFocus->id = $record;


			foreach ($templateids as $templateid)
			{
				$PDFContent = PDFContent::getInstance($templateid, $module, $modFocus, $language); //crmv@34738
				$pdf_content = $PDFContent->getContent();
				$Settings = $PDFContent->getSettings();

				if($name=="")
					$name = $PDFContent->getFilename();

				// crmv@104506
				// no decoding from html, like in checkGenerate file
				$header_html = $pdf_content["header"];
				$body_html = $pdf_content["body"];
				$footer_html = $pdf_content["footer"];
				// crmv@104506e

				$body_html = str_replace("#LISTVIEWBLOCK_START#","",$body_html);
				$body_html = str_replace("#LISTVIEWBLOCK_END#","",$body_html);

				//$encoding = $Settings["encoding"];

				if ($Settings["orientation"] == "landscape")
					$format = $Settings["format"]."-L";
				else
					$format = $Settings["format"];

				if (!isset($mpdf)) {
					$mpdf=new mPDF('',$format,'','Arial',$Settings["margin_left"],$Settings["margin_right"],0,0,$Settings["margin_top"],$Settings["margin_bottom"]);
					$mpdf->SetAutoFont();
					@$mpdf->SetHTMLHeader($header_html);
				} else {
					@$mpdf->SetHTMLHeader($header_html);
					@$mpdf->WriteHTML('<pagebreak sheet-size="'.$format.'" margin-left="'.$Settings["margin_left"].'mm" margin-right="'.$Settings["margin_right"].'mm" margin-top="0mm" margin-bottom="0mm" margin-header="'.$Settings["margin_top"].'mm" margin-footer="'.$Settings["margin_bottom"].'mm" />');
				}
				@$mpdf->SetHTMLFooter($footer_html);
				@$mpdf->WriteHTML($body_html);
			}

			if ($name=="") {
				if (count($idslist)>1) {
					$name="BatchPDF";
				} else {
					$result = $adb->pquery("SELECT fieldname FROM {$table_prefix}_field WHERE uitype=4 AND tabid = ?", array(getTabId($module)));
					$fieldname = $adb->query_result_no_html($result,0,"fieldname");
					if (isset($focus->column_fields[$fieldname]) && $focus->column_fields[$fieldname] != "") {
						$name = generate_cool_uri($focus->column_fields[$fieldname]);
					} else {
						$name = implode(';', $templateids).';'.$record.date("ymdHi");
						$name = str_replace(";","_",$name);
					}
				}
			}

			$name .= '_'.date("ymdHis");	// crmv@101674
			$file = 'storage/'.$name.'.pdf';

			if (file_exists($file)) unlink($file);
			$mpdf->Output($file);
		}

		return $name;
	}
	// crmv@39106e
	
	function importStandardLayouts() {
		
		global $adb, $table_prefix;
		
		$tmp_file_name = 'modules/PDFMaker/StandardLayouts.xml';
		
		$fh = fopen($tmp_file_name,"r");
		$xml_content = fread($fh, filesize($tmp_file_name));
		fclose($fh);
			
		$type = "professional";
			
		$xml = new SimpleXMLElement($xml_content);
		
		foreach ($xml->template AS $data)
		{
		    $filename = $this->cdataDecode($data->templatename);
		    $nameOfFile = $this->cdataDecode($data->filename);
		    $description = $this->cdataDecode($data->description);
		    $modulename = $this->cdataDecode($data->module);
		    $pdf_format = $this->cdataDecode($data->settings->format);
		    $pdf_orientation = $this->cdataDecode($data->settings->orientation);
		    
		    $tabid = getTabId($modulename); 
		
		    if ($type == "professional" || $tabid == "20" || $tabid == "21" || $tabid == "22" || $tabid == "23")
		    {
		        if ($data->settings->margins->top > 0) $margin_top = $data->settings->margins->top; else $margin_top = 0;
		        if ($data->settings->margins->bottom > 0) $margin_bottom = $data->settings->margins->bottom; else $margin_bottom = 0;
		        if ($data->settings->margins->left > 0) $margin_left = $data->settings->margins->left; else $margin_left = 0;
		        if ($data->settings->margins->right > 0) $margin_right = $data->settings->margins->right; else $margin_right = 0;
		    
		        $dec_point = $this->cdataDecode($data->settings->decimals->point);
		        $dec_decimals = $this->cdataDecode($data->settings->decimals->decimals);
		        $dec_thousands = $this->cdataDecode($data->settings->decimals->thousands);
		    
		        $header = $this->cdataDecode($data->header);
		        $body = $this->cdataDecode($data->body);
		        $footer = $this->cdataDecode($data->footer);
		        
		        $check = $adb->pquery("SELECT templateid FROM {$table_prefix}_pdfmaker WHERE filename = ? AND module = ?",array($filename,$modulename));
		        if ($check && $adb->num_rows($check) > 0) {
		        	continue;
		        }
		        
		        $templateid = $adb->getUniqueID($table_prefix.'_pdfmaker');
		      	$sql1 = "insert into ".$table_prefix."_pdfmaker (filename,module,description,body,deleted,templateid) values (?,?,?,NULL,?,?)";
		      	$params1 = array($filename, $modulename, $description, 0, $templateid);
				$adb->pquery($sql1, $params1);
				$adb->updateClob($table_prefix.'_pdfmaker','body',"templateid = $templateid",$body);
		    	  
				$sql2 = "INSERT INTO ".$table_prefix."_pdfmaker_settings (templateid, margin_top, margin_bottom, margin_left, margin_right, format, orientation, decimals, decimal_point, thousands_separator, header, footer, encoding, file_name) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";
		        $params2 = array($templateid, $margin_top, $margin_bottom, $margin_left, $margin_right, $pdf_format, $pdf_orientation, $dec_decimals, $dec_point, $dec_thousands, $header, $footer, "auto", $nameOfFile);
		        $adb->pquery($sql2, $params2);
		    }
		}
	}

	// crmv@120993
	function importProdBlockTemplates() {
		global $adb, $table_prefix;
		include('modules/PDFMaker/installPBTemplates.php');
	}
	// crmv@120993e
	
	function cdataDecode($text)
	{
	    $From = array("<|!|[%|CDATA|[%|", "|%]|]|>");
	    $To = array("<![CDATA[", "]]>");
	    
	    $decode_text = str_replace($From, $To, $text);
	
	    return $decode_text;
	}

	// crmv@151071
	public function getPDFMakerDetails($module, $record) {
		global $adb, $table_prefix, $current_user;
		
		$details = array();
		
		$res = $adb->pquery("SELECT p.templateid, p.filename AS templatename, p.module
				FROM {$table_prefix}_pdfmaker p
				LEFT JOIN {$table_prefix}_pdfmaker_userstatus u ON u.templateid = p.templateid AND u.userid = ?
				WHERE (u.is_active IS NULL OR u.is_active = 1) " . ($module ? " AND p.module = ?" : '') . "
			ORDER BY p.module, p.filename", array($current_user->id, $module));
		
		if ($res) {
			// templates list
			$pdflist = array();
			while ($row = $adb->FetchByAssoc($res, -1, false)) {
				$pdflist[] = $row;
			}
			$details['templates'] = $pdflist;
			$details['actions'] = array('sendemail');
			if (isPermitted('Documents', 'EditView') == 'yes') $details['actions'][] = 'savedoc';
		}
		
		return $details;
	}
	// crmv@151071e

}
//crmv@17889e	//crmv@19166e
?>