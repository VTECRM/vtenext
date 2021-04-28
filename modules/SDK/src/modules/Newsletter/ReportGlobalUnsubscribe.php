<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@55961

class GlobalUnsubscribeReportRun extends ReportRun {

	public $enableExportPdf = false;
	public $enableExportXls = false;
	public $enablePrint = false;
	public $hideParamsBlock = true;

	function __construct($reportid) {
		$this->reports = Reports::getInstance($reportid); // crmv@167234
		$this->reportid = $reportid;
		$this->primarymodule = 'Newsletter';
		$this->secondarymodule = '';
		$this->reporttype = 'summary';
	}

	function getPrimaryStdFilterHTML() {
		return '';
	}

	function getSecondaryStdFilterHTML() {
		return '';
	}

	function getSDKBlock() {
		$html = getTranslatedString('LBL_FIND','APP_STRINGS').": ";
		$html .= '<input id="filterbox" class="crmButton searchBox" style="cursor:auto;" type="text" value="" name="filterbox">';
		
		return $html;
	}
	
	function getQueryWhereCondition(){
		if(!$_REQUEST['filterbox'] || empty($_REQUEST['filterbox'])) return '';
		
		$value = vtlib_purify($_REQUEST['filterbox']);
		
		$query = "WHERE ";
		$query .= "(";
		$query .= "email like '%$value%' or unsub_date like '%$value%' or entity_num like '%$value%' or crmid like '%$value%'";
		$query .= ")";
		
		return $query;
	}

	function getData($reportid,$filterlist,$outputformat) {
		global $adb, $table_prefix, $current_user;
		
		//crmv@181281
		$union = array();
		$focusNewsletter = CRMEntity::getInstance('Newsletter');
		foreach($focusNewsletter->email_fields as $module => $email_field) {
			$focusModule = CRMEntity::getInstance($module);
			$crmentity_alias = "crm".substr($module,0,3);
			$union[] = "SELECT
				tbl_s_newsletter_g_unsub.email,
				tbl_s_newsletter_g_unsub.unsub_date,
				{$email_field['tablename']}.{$email_field['columnname']} AS entity_num,
				{$crmentity_alias}.crmid AS crmid
			FROM
				tbl_s_newsletter_g_unsub
			LEFT JOIN {$email_field['tablename']}
				ON {$email_field['tablename']}.{$email_field['columnname']} = tbl_s_newsletter_g_unsub.email
			INNER JOIN {$table_prefix}_crmentity {$crmentity_alias}
				ON {$crmentity_alias}.crmid = {$email_field['tablename']}.{$focusModule->tab_name_index[$email_field['tablename']]}
				AND {$crmentity_alias}.deleted = 0";
		}
		$union = implode(' UNION ',$union);
		$query = "SELECT email,unsub_date,entity_num,crmid FROM ({$union}) results ";
		//crmv@181281e
		
		$whereCond = $this->getQueryWhereCondition();
		if(strlen($whereCond) > 0){
			$query .= $whereCond;
		}
		
		$query .= 'ORDER BY email';
		
		$respot = $adb->query($query);
		$data = array();
		while ($forecast = $adb->FetchByAssoc($respot, -1, false)) {
			$row['email'] = $forecast['email'];
			$row['unsub_date'] = $forecast['unsub_date'];
			$row['entity_num'] = $forecast['entity_num'];
			$row['crmid'] = $forecast['crmid'];
			
			$data[] = $row;
		}
		
		return $data;
	}

	function GenerateReport($outputformat, $filterlist = "", $directOutput=false) {
		global $adb, $table_prefix;

		$data = $this->getData($this->reportid,$filterlist,$outputformat);
		$nrows = count($data);

		$html = '';

		switch ($outputformat) {
			default:
			case 'HTML':
			case 'PDF':
			case 'PRINT':

				if (count($data) > 0) {
				$html = "<table cellpadding=\"5\" cellspacing=\"0\" align=\"center\" class=\"rptTable\">";

				$html .=
				"<thead><tr class=\"reportRowTitle\">
					<td class=\"rptCellLabel\">".getTranslatedString('NEWSLETTER_UNSUB_EMAIL','Newsletter')."</td>
					<td class=\"rptCellLabel\">".getTranslatedString('NEWSLETTER_UNSUB_DATE','Newsletter')."</td>
					<td class=\"rptCellLabel\">".getTranslatedString('NEWSLETTER_ENTTITY_NUM','Newsletter')."</td>
					<td class=\"rptCellLabel\">".getTranslatedString('NEWSLETTER_ENTTITY','Newsletter')."</td>
					<td class=\"rptCellLabel\">".getTranslatedString('LBL_MODULE','APP_STRINGS')."</td>
					<td class=\"rptCellLabel\">ID</td>
					<td class=\"rptCellLabel\">".getTranslatedString('LBL_ACTION')."</td>
				</tr></thead><tbody>";
				$rowN = 0;
				$counter = 0;
				$last_email = false;
				foreach ($data as $drow) {
					//data
					$email = $drow['email'];
					$unsub_date = $drow['unsub_date'];
					$entity_num = $drow['entity_num'];
					$crmid = $drow['crmid'];
					//retrieve contact/account/lead basic info
					$mod = getSalesEntityType($crmid);
					$entity = getEntityName($mod,$crmid);
					//building entity detail url
					$action_url = "<a href=\"index.php?module={$mod}&action=DetailView&record={$crmid}\" target='_blank'>".getTranslatedString('LBL_VIEW_DETAILS')."</a>";
					//misc for summary table view
					$groupclass = ($email === $last_email) ? 'rptEmptyGrp' : 'rptGrpHead';
					$email_val = ($groupclass == 'rptEmptyGrp') ? '' : $email;
					//drawing table
					$html .= "<tr class=\"reportRow$rowN\">
						<td class=\"{$groupclass}\">$email_val</td>
						<td class=\"rptData\">$unsub_date</td>
						<td class=\"rptData\">$entity_num</td>
						<td class=\"rptData\">".$entity[$crmid]."</td>
						<td class=\"rptData\">".getTranslatedString($mod)."</td>
						<td class=\"rptData\">$crmid</td>
						<td class=\"rptData\">$action_url</td>
					</tr>";
					
					$last_email = $email;
					$rowN ^= 1;
					++$counter;
				}
				$html .= "</tbody></table>";
				} else {
					$html = "<b>".getTranslatedString('LBL_NO_DATA')."</b>";
				}
				$html .= '<script type="text/javascript">var report_info_override = "";</script>'; // crmv@49622
				$return_data = $html;
				break;
			case 'XLS':
				$return_data = '';
				break;
			case 'TOTALXLS':
			case 'TOTALHTML':
			case 'PRINT_TOTAL':
				$report_data = '';
				break;
		}
		return array($return_data, $nrows);
	}

}

//crmv@55961e
?>