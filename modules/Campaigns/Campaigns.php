<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//crmv@203484 removed including file

class Campaigns extends CRMEntity {
	var $log;
	var $db;
	var $table_name;
	var $table_index= 'campaignid';

	var $tab_name = Array();
	var $tab_name_index = Array();
	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array();
	var $column_fields = Array();

	var $sortby_fields = Array('campaignname','smownerid','campaigntype','productname','expectedrevenue','closingdate','campaignstatus','expectedresponse','targetaudience','expectedcost');

	var $list_fields = Array(
					'Campaign Name'=>Array('campaign'=>'campaignname'),
					'Campaign Type'=>Array('campaign'=>'campaigntype'),
					'Campaign Status'=>Array('campaign'=>'campaignstatus'),
					'Expected Revenue'=>Array('campaign'=>'expectedrevenue'),
					'Expected Close Date'=>Array('campaign'=>'closingdate'),
					'Assigned To' => Array('crmentity'=>'smownerid')
				);

	var $list_fields_name = Array(
					'Campaign Name'=>'campaignname',
					'Campaign Type'=>'campaigntype',
					'Campaign Status'=>'campaignstatus',
					'Expected Revenue'=>'expectedrevenue',
					'Expected Close Date'=>'closingdate',
					'Assigned To'=>'assigned_user_id'
				     );

	var $list_link_field= 'campaignname';
	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'crmid';
	var $default_sort_order = 'DESC';

	//var $groupTable = Array('vte_campaigngrouprelation','campaignid');

	var $search_fields = Array();

	var $search_fields_name = Array(
			'Campaign Name'=>'campaignname',
			'Campaign Type'=>'campaigntype',
			);
	//crmv@10759
	var $search_base_field = 'campaignname';
	//crmv@10759 e
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('campaignname','createdtime' ,'modifiedtime','assigned_user_id');

	function __construct()
	{
		global $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix."_campaign";
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_campaign',$table_prefix.'_campaignscf');
		$this->tab_name_index = Array($table_prefix.'_crmentity'=>'crmid',$table_prefix.'_campaign'=>'campaignid',$table_prefix.'_campaignscf'=>'campaignid');
		$this->customFieldTable = Array($table_prefix.'_campaignscf', 'campaignid');
		$this->search_fields = Array(
			'Campaign Name'=>Array($table_prefix.'_campaign'=>'campaignname'),
			'Campaign Type'=>Array($table_prefix.'_campaign'=>'campaigntype'),
			);
	$this->log =LoggerManager::getLogger('campaign');
		$this->db = PearDatabase::getInstance();
		$this->column_fields = getColumnFields('Campaigns');
	}

	/** Function to handle module specific operations when saving a entity
	*/
	function save_module($module)
	{
	}
	
	// crmv@104975 crmv@152532
	function getExtraDetailTabs() {
		$return = array();
		
		// add the statistics tab
		if (isModuleInstalled('Newsletter')) {
			$return = array(
				array('label'=>getTranslatedString('LBL_STATISTICS', 'Newsletter'),'href'=>'','onclick'=>"changeDetailTab('{$this->modulename}', '{$this->id}', 'Statistics', this)")
			);
		}
		$others = parent::getExtraDetailTabs() ?: array();

		return array_merge($return, $others);
	}
	// crmv@104975e crmv@152532e

	/*
	 * Function to get the secondary query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	//crmv@38798
	function generateReportsSecQuery($module,$secmodule,$reporttype='',$useProductJoin=true,$joinUitype10=true){ // crmv@146653
		global $table_prefix;
		$query = $this->getRelationQuery($module,$secmodule,$table_prefix."_campaign","campaignid");
		$query .=" left join ".$table_prefix."_products ".$table_prefix."_productsCampaigns on ".$table_prefix."_campaign.product_id = ".$table_prefix."_productsCampaigns.productid
			left join ".$table_prefix."_campaignscf on ".$table_prefix."_campaignscf.campaignid = ".$table_prefix."_crmentityCampaigns.crmid
			left join ".$table_prefix."_groups ".$table_prefix."_groupsCampaigns on ".$table_prefix."_groupsCampaigns.groupid = ".$table_prefix."_crmentityCampaigns.smownerid
			left join ".$table_prefix."_users ".$table_prefix."_usersCampaigns on ".$table_prefix."_usersCampaigns.id = ".$table_prefix."_crmentityCampaigns.smownerid";

		return $query;
	}
	//crmv@38798e

	/*
	 * Function to get the relation tables for related modules
	 * @param - $secmodule secondary module name
	 * returns the array with table names and fieldnames storing relations between module and this module
	 */
	function setRelationTables($secmodule){
		global $table_prefix;
		$rel_tables = array (
			"Contacts" => array($table_prefix."_campaigncontrel"=>array("campaignid","contactid"),$table_prefix."_campaign"=>"campaignid"),
			"Leads" => array($table_prefix."_campaignleadrel"=>array("campaignid","leadid"),$table_prefix."_campaign"=>"campaignid"),
			"Accounts" => array($table_prefix."_campaignaccountrel"=>array("campaignid","accountid"),$table_prefix."_campaign"=>"campaignid"),
			"Potentials" => array($table_prefix."_potential"=>array("campaignid","potentialid"),$table_prefix."_campaign"=>"campaignid"),
			"Calendar" => array($table_prefix."_seactivityrel"=>array("crmid","activityid"),$table_prefix."_campaign"=>"campaignid"),
			"Products" => array($table_prefix."_campaign"=>array("campaignid","product_id")),
		);
		return $rel_tables[$secmodule];
	}

	// Function to unlink an entity with given Id from another entity
	function unlinkRelationship($id, $return_module, $return_id) {
		global $log;
		global $table_prefix;
		if(empty($return_module) || empty($return_id)) return;

		if($return_module == 'Leads') {
			$sql = 'DELETE FROM '.$table_prefix.'_campaignleadrel WHERE campaignid=? AND leadid=?';
			$this->db->pquery($sql, array($id, $return_id));
		} elseif($return_module == 'Contacts') {
			$sql = 'DELETE FROM '.$table_prefix.'_campaigncontrel WHERE campaignid=? AND contactid=?';
			$this->db->pquery($sql, array($id, $return_id));
		//crmv@15157
		} elseif($return_module == 'Accounts') {
			$sql = 'DELETE FROM '.$table_prefix.'_campaignaccountrel WHERE campaignid=? AND accountid=?';
			$this->db->pquery($sql, array($id, $return_id));
		//crmv@15157
		} else {
			$sql = 'DELETE FROM '.$table_prefix.'_crmentityrel WHERE (crmid=? AND relmodule=? AND relcrmid=?) OR (relcrmid=? AND module=? AND crmid=?)';
			$params = array($id, $return_module, $return_id, $id, $return_module, $return_id);
			$this->db->pquery($sql, $params);
		}
		$this->db->pquery("UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid IN (?,?)", array($this->db->formatDate(date('Y-m-d H:i:s'), true), $id, $return_id)); // crmv@49398 crmv@69690
	}

	//crmv@22700	//crmv@25083
	function get_newsletter($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $adb,$theme;
		global $table_prefix;
		$return_value = $this->get_dependents_list($id, $cur_tab_id, $rel_tab_id, $actions);
		if (!empty($return_value['entries'])) {	//crmv@25809
			foreach ($return_value['entries'] as $id => $info) {
				$res = $adb->pquery('select linklabel,linkurl from '.$table_prefix.'_links where tabid = ? and linklabel = ?',array(getTabid('Newsletter'),'LBL_SEND_MAIL_BUTTON'));
				$link = str_replace('$RECORD$',$id,$adb->query_result($res,0,'linkurl'));
				$label = getTranslatedString($adb->query_result($res,0,'linklabel'),'Newsletter');
				$return_value['entries'][$id][0] .= '&nbsp;&nbsp;<a href="'.$link.'"><i class="vteicon" title="'.$label.'">send</i></a>'; // crmv@195115

				$res = $adb->pquery('select linklabel,linkurl from '.$table_prefix.'_links where tabid = ? and linklabel = ?',array(getTabid('Newsletter'),'LBL_SEND_TEST_MAIL_BUTTON'));
				$link = str_replace('$RECORD$',$id,$adb->query_result($res,0,'linkurl'));
				$label = getTranslatedString($adb->query_result($res,0,'linklabel'),'Newsletter');
				$return_value['entries'][$id][0] .= '&nbsp;&nbsp;<a href="'.$link.'"><i class="vteicon" title="'.$label.'">mail_outline</i></a>'; // crmv@195115
			}
		}	//crmv@25809
		return $return_value;
	}
	function getStatisticRelatedLists() {
		global $adb;
		global $table_prefix;
		$labels = array();
		$result = $adb->query("SELECT label FROM ".$table_prefix."_relatedlists WHERE tabid = 26 AND related_tabid = 0 AND name LIKE 'get_statistics_%' ORDER BY sequence");
		while($row=$adb->fetchByAssoc($result)) {
			$labels[] = $row['label'];
		}
		return $labels;
	}
	function filterStatisticRelatedLists($mode,&$related_array,$ir_rel_list=false) {
		$statistic_related_lists = $this->getStatisticRelatedLists();
		if ($mode == 'maintain') {
			if ($ir_rel_list) {
				foreach($related_array as $id => $key) {
					if (!in_array($key,$statistic_related_lists)) {
						unset($related_array[$id]);
					}
				}
			} else {
				foreach($related_array as $key => $info) {
					if (!in_array($key,$statistic_related_lists)) {
						unset($related_array[$key]);
					}
				}
			}
		} elseif ($mode == 'remove') {
			foreach($statistic_related_lists as $name) {
				if ($ir_rel_list) {
					unset($related_array[array_search($name,$related_array)]);
				} else {
					unset($related_array[$name]);
				}
			}
		}
	}
	//related list functions - i
	function get_statistics_message_queue($id, $cur_tab_id, $rel_tab_id, $actions=false, $only_query=false, $char=false, $xls_export=false) {
		global $currentModule;//crmv@203484 removed global singlepane
		global $table_prefix;
		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e
		if($singlepane_view == true) {//crmv@203484 changed to normal bool true, not string 'true'
			$returnset = '&return_module='.$currentModule.'&return_action=DetailView&return_id='.$id;
		} else {
			$returnset = '&return_module='.$currentModule.'&return_action=CallRelatedList&return_id='.$id;
		}
		$button = '';
		$title = 'Message Queue';
		VteSession::set(strtolower($title)."_listquery", '');
		if ($char) {
			$query = "select ".$table_prefix."_crmentity.crmid, ".$table_prefix."_crmentity.setype
						from tbl_s_newsletter_queue
						inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = tbl_s_newsletter_queue.crmid";
		} else {
			$query = "select * from tbl_s_newsletter_queue";
		}
		$query .= " where tbl_s_newsletter_queue.status = 'Scheduled'";
		$where = $this->get_statistics_message_where_condition($id,'tbl_s_newsletter_queue.newsletterid');
		if ($where != '') {
			$query .= $where;
			$return_value = $this->GetStatisticList($currentModule, $title, $query, $button, $returnset, $only_query,$xls_export); //crmv@116390
		}
		if($return_value == null) {
			$return_value = Array();
		}
		//crmv@101503
		if($xls_export){
			if(isset($return_value['entries'])){ //crmv@167234
				foreach($return_value['entries'] as $keyRow => $arrRow){
					array_pop($return_value['entries'][$keyRow]);
					foreach($arrRow as $kCol => $valCol){
						$return_value['entries'][$keyRow][$return_value['header'][$kCol]] = strip_tags($valCol);
						unset($return_value['entries'][$keyRow][$kCol]);
					}
				}
			} //crmv@167234
		}
		$nomeCamp = $this->getNewTargetName($id, $title); // crmv@142678
		$hasEntries = isset($return_value['entries']) ? count($return_value['entries']) : 0; //crmv@167234
		$button = "<input class='crmbutton small create' type='button' onclick='StatisticsScript.export_statistics(\"$currentModule\",$id,\"$title\",\"$hasEntries\")' value='".getTranslatedString('EXPORT_LIST','Campaigns')."' name='Export'>";
		$button .= "&nbsp;&nbsp;&nbsp;<input class='crmbutton small create' type='button' onclick='StatisticsScript.create_target(\"$currentModule\",$id,\"$title\",\"$nomeCamp\",this,\"$hasEntries\");' value='".getTranslatedString('CREATE_TARGET','Campaigns')."' name='CreateTarget'>";
		//crmv@101503e
		$return_value['CUSTOM_BUTTON'] = $button;
		return $return_value;
	}
	function get_statistics_sent_messages($id, $cur_tab_id, $rel_tab_id, $actions=false, $only_query=false, $char=false, $xls_export=false) {
		global $currentModule;//crmv@203484 removed global singlepane
		global $table_prefix;
		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e
		if($singlepane_view == true) {////crmv@203484 changed to normal bool true, not string 'true'
			$returnset = '&return_module='.$currentModule.'&return_action=DetailView&return_id='.$id;
		} else {
			$returnset = '&return_module='.$currentModule.'&return_action=CallRelatedList&return_id='.$id;
		}
		$button = '';
		$title = 'Sent Messages';
		VteSession::set(strtolower($title)."_listquery", '');
		if ($char) {
			$query = "select ".$table_prefix."_crmentity.crmid, ".$table_prefix."_crmentity.setype
						from tbl_s_newsletter_queue
						inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = tbl_s_newsletter_queue.crmid";
		} else {
			$query = "select * from tbl_s_newsletter_queue";
		}
		$query .= " where tbl_s_newsletter_queue.status = 'Sent'";
		$where = $this->get_statistics_message_where_condition($id,'tbl_s_newsletter_queue.newsletterid');
		if ($where != '') {
			$query .= $where;
			$return_value = $this->GetStatisticList($currentModule, $title, $query, $button, $returnset, $only_query,$xls_export); //crmv@116390
		}

		if($return_value == null) {
			$return_value = Array();
		}
		//crmv@101503
		if($xls_export){
			if(isset($return_value['entries'])){ //crmv@167234
				foreach($return_value['entries'] as $keyRow => $arrRow){
					array_pop($return_value['entries'][$keyRow]);
					foreach($arrRow as $kCol => $valCol){
						$return_value['entries'][$keyRow][$return_value['header'][$kCol]] = strip_tags($valCol);
						unset($return_value['entries'][$keyRow][$kCol]);
					}
				}
			} //crmv@167234
		}
		$nomeCamp = $this->getNewTargetName($id, $title); // crmv@142678
		$hasEntries = isset($return_value['entries']) ? count($return_value['entries']) : 0; //crmv@167234
		$button = "<input class='crmbutton small create' type='button' onclick='StatisticsScript.export_statistics(\"$currentModule\",$id,\"$title\",\"$hasEntries\")' value='".getTranslatedString('EXPORT_LIST','Campaigns')."' name='Export'>";
		$button .= "&nbsp;&nbsp;&nbsp;<input class='crmbutton small create' type='button' onclick='StatisticsScript.create_target(\"$currentModule\",$id,\"$title\",\"$nomeCamp\",this,\"$hasEntries\");' value='".getTranslatedString('CREATE_TARGET','Campaigns')."' name='CreateTarget'>";
		//crmv@101503e
		$return_value['CUSTOM_BUTTON'] = $button;
		return $return_value;
	}
	function get_statistics_viewed_messages($id, $cur_tab_id, $rel_tab_id, $actions=false, $only_query=false, $char=false, $xls_export=false) {
		global $currentModule;//crmv@203484 removed global singlepane
		global $table_prefix;
		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e
		if($singlepane_view == true) {//crmv@203484 changed to normal bool true, not string 'true'
			$returnset = '&return_module='.$currentModule.'&return_action=DetailView&return_id='.$id;
		} else {
			$returnset = '&return_module='.$currentModule.'&return_action=CallRelatedList&return_id='.$id;
		}
		$button = '';
		$title = 'Viewed Messages';
		VteSession::set(strtolower($title)."_listquery", '');
		if ($char) {
			$query = "select ".$table_prefix."_crmentity.crmid, ".$table_prefix."_crmentity.setype
						from tbl_s_newsletter_queue
						inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = tbl_s_newsletter_queue.crmid";
		} else {
			$query = "select * from tbl_s_newsletter_queue";
		}
		$query .= " where tbl_s_newsletter_queue.num_views > 0";
		$where = $this->get_statistics_message_where_condition($id,'tbl_s_newsletter_queue.newsletterid');
		if ($where != '') {
			$query .= $where;
			$return_value = $this->GetStatisticList($currentModule, $title, $query, $button, $returnset, $only_query,$xls_export); //crmv@116390
		}
		if($return_value == null) {
			$return_value = Array();
		}
		//crmv@101503
		if($xls_export){
			if(isset($return_value['entries'])){ //crmv@167234
				foreach($return_value['entries'] as $keyRow => $arrRow){
					array_pop($return_value['entries'][$keyRow]);
					foreach($arrRow as $kCol => $valCol){
						$return_value['entries'][$keyRow][$return_value['header'][$kCol]] = strip_tags($valCol);
						unset($return_value['entries'][$keyRow][$kCol]);
					}
				}
			} //crmv@167234
		}
		$nomeCamp = $this->getNewTargetName($id, $title); // crmv@142678
		$hasEntries = isset($return_value['entries']) ? count($return_value['entries']) : 0; //crmv@167234
		$button = "<input class='crmbutton small create' type='button' onclick='StatisticsScript.export_statistics(\"$currentModule\",$id,\"$title\",\"$hasEntries\")' value='".getTranslatedString('EXPORT_LIST','Campaigns')."' name='Export'>";
		$button .= "&nbsp;&nbsp;&nbsp;<input class='crmbutton small create' type='button' onclick='StatisticsScript.create_target(\"$currentModule\",$id,\"$title\",\"$nomeCamp\",this,\"$hasEntries\");' value='".getTranslatedString('CREATE_TARGET','Campaigns')."' name='CreateTarget'>";
		//crmv@101503e
		$return_value['CUSTOM_BUTTON'] = $button;
		return $return_value;
	}
	function get_statistics_tracked_link($id, $cur_tab_id, $rel_tab_id, $actions=false, $only_query=false, $char=false, $xls_export=false) {
		global $currentModule;//crmv@203484 removed global singlepane
		global $table_prefix;
		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e
		if($singlepane_view == true) {//crmv@203484 changed to normal bool true, not string 'true'
			$returnset = '&return_module='.$currentModule.'&return_action=DetailView&return_id='.$id;
		} else {
			$returnset = '&return_module='.$currentModule.'&return_action=CallRelatedList&return_id='.$id;
		}
		$button = '';
		$title = 'Tracked Link';
		VteSession::set(strtolower($title)."_listquery", '');
		if ($char) {
			$query = "select ".$table_prefix."_crmentity.crmid, ".$table_prefix."_crmentity.setype
						from tbl_s_newsletter_tl
						inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = tbl_s_newsletter_tl.crmid";
		} else {
			$query = "select * from tbl_s_newsletter_tl";
		}
		// crmv@38592
		$query .= "
			inner join tbl_s_newsletter_links on tbl_s_newsletter_links.linkid = tbl_s_newsletter_tl.linkurlid
			where tbl_s_newsletter_tl.clicked > 0";
		// crmv@38592e
		$where = $this->get_statistics_message_where_condition($id,'tbl_s_newsletter_tl.newsletterid');
		if ($where != '') {
			$query .= $where;
			$return_value = $this->GetStatisticList($currentModule, $title, $query, $button, $returnset, $only_query,$xls_export); //crmv@116390
		}
		if($return_value == null) {
			$return_value = Array();
		}
		//crmv@101503
		if($xls_export){
			if(isset($return_value['entries'])){ //crmv@167234
				foreach($return_value['entries'] as $keyRow => $arrRow){
					array_pop($return_value['entries'][$keyRow]);
					foreach($arrRow as $kCol => $valCol){
						$return_value['entries'][$keyRow][$return_value['header'][$kCol]] = strip_tags($valCol);
						unset($return_value['entries'][$keyRow][$kCol]);
					}
				}
			} //crmv@167234
		}
		$nomeCamp = $this->getNewTargetName($id, $title); // crmv@142678
		$hasEntries = isset($return_value['entries']) ? count($return_value['entries']) : 0; //crmv@167234
		$button = "<input class='crmbutton small create' type='button' onclick='StatisticsScript.export_statistics(\"$currentModule\",$id,\"$title\",\"$hasEntries\")' value='".getTranslatedString('EXPORT_LIST','Campaigns')."' name='Export'>";
		$button .= "&nbsp;&nbsp;&nbsp;<input class='crmbutton small create' type='button' onclick='StatisticsScript.create_target(\"$currentModule\",$id,\"$title\",\"$nomeCamp\",this,\"$hasEntries\");' value='".getTranslatedString('CREATE_TARGET','Campaigns')."' name='CreateTarget'>";
		//crmv@101503e
		$return_value['CUSTOM_BUTTON'] = $button;
		return $return_value;
	}
	function get_statistics_unsubscriptions($id, $cur_tab_id, $rel_tab_id, $actions=false, $only_query=false, $char=false, $xls_export=false) {
		global $currentModule;//crmv@203484 removed global singlepane
		global $table_prefix;
		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e
		if($singlepane_view == true) {//crmv@203484 changed to normal bool true, not string 'true'
			$returnset = '&return_module='.$currentModule.'&return_action=DetailView&return_id='.$id;
		} else {
			$returnset = '&return_module='.$currentModule.'&return_action=CallRelatedList&return_id='.$id;
		}
		$button = '';
		$title = 'Unsubscriptions';
		VteSession::set(strtolower($title)."_listquery", '');
		$focus = CRMEntity::getInstance('Newsletter');
		if ($char) {
			$query = "select ".$table_prefix."_crmentity.crmid, ".$table_prefix."_crmentity.setype
						from tbl_s_newsletter_tl
						inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = tbl_s_newsletter_tl.crmid";
		} else {
			$query = "select * from tbl_s_newsletter_tl";
		}
		// crmv@38592
		$query .= "
			inner join tbl_s_newsletter_links on tbl_s_newsletter_links.linkid = tbl_s_newsletter_tl.linkurlid
			where tbl_s_newsletter_tl.clicked > 0 and tbl_s_newsletter_links.url = '$focus->url_unsubscription_file'";
		// crmv@38592e
		$where = $this->get_statistics_message_where_condition($id,'tbl_s_newsletter_tl.newsletterid');
		if ($where != '') {
			$query .= $where;
			$return_value = $this->GetStatisticList($currentModule, $title, $query, $button, $returnset, $only_query,$xls_export); //crmv@116390
		}
		if($return_value == null) {
			$return_value = Array();
		}
		//crmv@101503
		if($xls_export){
			if(isset($return_value['entries'])){ //crmv@167234
				foreach($return_value['entries'] as $keyRow => $arrRow){
					array_pop($return_value['entries'][$keyRow]);
					foreach($arrRow as $kCol => $valCol){
						$return_value['entries'][$keyRow][$return_value['header'][$kCol]] = strip_tags($valCol);
						unset($return_value['entries'][$keyRow][$kCol]);
					}
				}
			} //crmv@167234
		}
		$nomeCamp = $this->getNewTargetName($id, $title); // crmv@142678
		$hasEntries = isset($return_value['entries']) ? count($return_value['entries']) : 0; //crmv@167234
		$button = "<input class='crmbutton small create' type='button' onclick='StatisticsScript.export_statistics(\"$currentModule\",$id,\"$title\",\"$hasEntries\")' value='".getTranslatedString('EXPORT_LIST','Campaigns')."' name='Export'>";
		$button .= "&nbsp;&nbsp;&nbsp;<input class='crmbutton small create' type='button' onclick='StatisticsScript.create_target(\"$currentModule\",$id,\"$title\",\"$nomeCamp\",this,\"$hasEntries\");' value='".getTranslatedString('CREATE_TARGET','Campaigns')."' name='CreateTarget'>";
		//crmv@101503e
		$return_value['CUSTOM_BUTTON'] = $button;
		return $return_value;
	}
	function get_statistics_bounced_messages($id, $cur_tab_id, $rel_tab_id, $actions=false, $only_query=false, $char=false, $xls_export=false) {
		global $currentModule;//crmv@203484 removed global singlepane
		global $table_prefix;
		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e
		if($singlepane_view == true) {//crmv@203484 changed to normal bool true, not string 'true'
			$returnset = '&return_module='.$currentModule.'&return_action=DetailView&return_id='.$id;
		} else {
			$returnset = '&return_module='.$currentModule.'&return_action=CallRelatedList&return_id='.$id;
		}
		$button = '';
		$title = 'Bounced Messages';
		VteSession::set(strtolower($title)."_listquery", '');
		if ($char) {
			$query = "select ".$table_prefix."_crmentity.crmid, ".$table_prefix."_crmentity.setype
						from tbl_s_newsletter_queue
						inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = tbl_s_newsletter_queue.crmid";
		} else {
			$query = "select * from tbl_s_newsletter_queue";
		}
		$query .= " inner join tbl_s_newsletter_bounce_rel on (tbl_s_newsletter_queue.newsletterid = tbl_s_newsletter_bounce_rel.newsletterid AND tbl_s_newsletter_queue.crmid = tbl_s_newsletter_bounce_rel.crmid)
					where tbl_s_newsletter_queue.status = 'Sent'";
		$where = $this->get_statistics_message_where_condition($id,'tbl_s_newsletter_queue.newsletterid');
		if ($where != '') {
			$query .= $where;
			$return_value = $this->GetStatisticList($currentModule, $title, $query, $button, $returnset, $only_query,$xls_export); //crmv@116390
		}
		if($return_value == null) {
			$return_value = Array();
		}
		//crmv@101503
		if($xls_export){
			if(isset($return_value['entries'])){ //crmv@167234
				foreach($return_value['entries'] as $keyRow => $arrRow){
					array_pop($return_value['entries'][$keyRow]);
					foreach($arrRow as $kCol => $valCol){
						$return_value['entries'][$keyRow][$return_value['header'][$kCol]] = strip_tags($valCol);
						unset($return_value['entries'][$keyRow][$kCol]);
					}
				}
			} //crmv@167234
		}
		$nomeCamp = $this->getNewTargetName($id, $title); // crmv@142678
		$hasEntries = isset($return_value['entries']) ? count($return_value['entries']) : 0; //crmv@167234
		$button = "<input class='crmbutton small create' type='button' onclick='StatisticsScript.export_statistics(\"$currentModule\",$id,\"$title\",\"$hasEntries\")' value='".getTranslatedString('EXPORT_LIST','Campaigns')."' name='Export'>";
		$button .= "&nbsp;&nbsp;&nbsp;<input class='crmbutton small create' type='button' onclick='StatisticsScript.create_target(\"$currentModule\",$id,\"$title\",\"$nomeCamp\",this,\"$hasEntries\");' value='".getTranslatedString('CREATE_TARGET','Campaigns')."' name='CreateTarget'>";
		//crmv@101503e
		$return_value['CUSTOM_BUTTON'] = $button;
		return $return_value;
	}
	function get_statistics_suppression_list($id, $cur_tab_id, $rel_tab_id, $actions=false, $only_query=false, $char=false, $xls_export=false) {
		global $currentModule;//crmv@203484 removed global singlepane
		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e
		if($singlepane_view == true) {//crmv@203484 changed to normal bool true, not string 'true'
			$returnset = '&return_module='.$currentModule.'&return_action=DetailView&return_id='.$id;
		} else {
			$returnset = '&return_module='.$currentModule.'&return_action=CallRelatedList&return_id='.$id;
		}
		$button = '';
		$title = 'Suppression list';
		VteSession::set(strtolower($title)."_listquery", '');
		$query = "SELECT tbl_s_newsletter_unsub.*, tbl_s_newsletter_status.name as type FROM tbl_s_newsletter_unsub LEFT JOIN tbl_s_newsletter_status on tbl_s_newsletter_status.id = tbl_s_newsletter_unsub.statusid"; // crmv@38592
		// crmv@142678
		//forzo la related a vedere gli elementi di tutta la campagna
		$statistics_newsletter_tmp = VteSession::get('statistics_newsletter_'.$id);
		VteSession::set('statistics_newsletter_'.$id, '');
		$where = $this->get_statistics_message_where_condition($id,'tbl_s_newsletter_unsub.newsletterid','where');
		VteSession::set('statistics_newsletter_'.$id, $statistics_newsletter_tmp);
		// crmv@142678e
		if ($where != '') {
			$query .= $where;
			$return_value = $this->GetStatisticList($currentModule, $title, $query, $button, $returnset, $only_query,$xls_export); //crmv@116390
		}
		if($return_value == null) {
			$return_value = Array();
		}
		//crmv@101503
		if($xls_export){
			if(isset($return_value['entries'])){ //crmv@167234
				foreach($return_value['entries'] as $keyRow => $arrRow){
					array_pop($return_value['entries'][$keyRow]);
					foreach($arrRow as $kCol => $valCol){
						$return_value['entries'][$keyRow][$return_value['header'][$kCol]] = strip_tags($valCol);
						unset($return_value['entries'][$keyRow][$kCol]);
					}
				}
			} //crmv@167234
		}
		$nomeCamp = $this->getNewTargetName($id, $title); // crmv@142678
		$hasEntries = isset($return_value['entries']) ? count($return_value['entries']) : 0; //crmv@167234
		$button = "<input class='crmbutton small create' type='button' onclick='StatisticsScript.export_statistics(\"$currentModule\",$id,\"$title\",\"$hasEntries\")' value='".getTranslatedString('EXPORT_LIST','Campaigns')."' name='Export'>";
		$button .= "&nbsp;&nbsp;&nbsp;<input class='crmbutton small create' type='button' onclick='StatisticsScript.create_target(\"$currentModule\",$id,\"$title\",\"$nomeCamp\",this,\"$hasEntries\");' value='".getTranslatedString('CREATE_TARGET','Campaigns')."' name='CreateTarget'>";
		//crmv@101503e
		$return_value['CUSTOM_BUTTON'] = $button;
		return $return_value;
	}
	//crmv@25872
	function get_statistics_failed_messages($id, $cur_tab_id, $rel_tab_id, $actions=false, $only_query=false, $char=false, $xls_export=false) {
		global $currentModule;//crmv@203484 removed global singlepane
		global $table_prefix;
		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e
		if($singlepane_view == true) {//crmv@203484 changed to normal bool true, not string 'true'
			$returnset = '&return_module='.$currentModule.'&return_action=DetailView&return_id='.$id;
		} else {
			$returnset = '&return_module='.$currentModule.'&return_action=CallRelatedList&return_id='.$id;
		}
		$button = '';
		$title = 'Failed Messages';
		VteSession::set(strtolower($title)."_listquery", '');
		// crmv@38592
		if ($char) {
			$query =
				"select ".$table_prefix."_crmentity.crmid, ".$table_prefix."_crmentity.setype
				from tbl_s_newsletter_queue
				inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = tbl_s_newsletter_queue.crmid";
		} else {
			$query = "select tbl_s_newsletter_queue.*,tbl_s_newsletter_failed.*,tbl_s_newsletter_status.name as note FROM tbl_s_newsletter_queue";
		}
		$query .= "
			inner join tbl_s_newsletter_failed on tbl_s_newsletter_queue.newsletterid = tbl_s_newsletter_failed.newsletterid and tbl_s_newsletter_queue.crmid = tbl_s_newsletter_failed.crmid
			left join tbl_s_newsletter_status on tbl_s_newsletter_status.id = tbl_s_newsletter_failed.statusid
			WHERE tbl_s_newsletter_queue.status = 'Failed'";
		// crmv@38592e
		$where = $this->get_statistics_message_where_condition($id,'tbl_s_newsletter_queue.newsletterid');
		if ($where != '') {
			$query .= $where;
			$return_value = $this->GetStatisticList($currentModule, $title, $query, $button, $returnset, $only_query,$xls_export); //crmv@116390
		}
		if($return_value == null) {
			$return_value = Array();
		}
		//crmv@101503
		if($xls_export){
			if(isset($return_value['entries'])){ //crmv@167234
				foreach($return_value['entries'] as $keyRow => $arrRow){
					array_pop($return_value['entries'][$keyRow]);
					foreach($arrRow as $kCol => $valCol){
						$return_value['entries'][$keyRow][$return_value['header'][$kCol]] = strip_tags($valCol);
						unset($return_value['entries'][$keyRow][$kCol]);
					}
				}
			} //crmv@167234
		}
		$nomeCamp = $this->getNewTargetName($id, $title); // crmv@142678
		$hasEntries = isset($return_value['entries']) ? count($return_value['entries']) : 0; //crmv@167234
		$button = "<input class='crmbutton small create' type='button' onclick='StatisticsScript.export_statistics(\"$currentModule\",$id,\"$title\",\"$hasEntries\")' value='".getTranslatedString('EXPORT_LIST','Campaigns')."' name='Export'>";
		$button .= "&nbsp;&nbsp;&nbsp;<input class='crmbutton small create' type='button' onclick='StatisticsScript.create_target(\"$currentModule\",$id,\"$title\",\"$nomeCamp\",this,\"$hasEntries\");' value='".getTranslatedString('CREATE_TARGET','Campaigns')."' name='CreateTarget'>";
		//crmv@101503e
		$return_value['CUSTOM_BUTTON'] = $button;
		return $return_value;
	}
	//crmv@25872e
		
	// crmv@142678
	function getNewTargetName($id, $title) {
		$idNews = VteSession::get('statistics_newsletter_'.$id);
		if(is_numeric($idNews)){
			$focusNL = CRMEntity::getInstance('Newsletter');
			$focusNL->retrieve_entity_info($idNews,'Newsletter');

			$nomeCamp = $focusNL->column_fields['newslettername']."-".getTranslatedString($title,'Campaigns')."-".$focusNL->column_fields['date_scheduled']." ".$focusNL->column_fields['time_scheduled'];
		}else{
			$focusC = CRMEntity::getInstance('Campaigns');
			$focusC->retrieve_entity_info($id,'Campaigns');

			$nomeCamp = $focusC->column_fields['campaignname']."-".getTranslatedString($title,'Campaigns');
		}
		//crmv@160856
		global $default_charset;
		$nomeCamp = htmlspecialchars(addslashes(html_entity_decode(strip_tags($nomeCamp), ENT_QUOTES,$default_charset)), ENT_QUOTES,$default_charset); // Remove any previous html conversion
		//crmv@160856e
		return $nomeCamp;
	}
	
	function get_statistics_message_where_condition($id,$field,$separator='and') {
		global $adb, $table_prefix;
		$statistics_newsletter = VteSession::get('statistics_newsletter_'.$id);
		if ($statistics_newsletter == '' || ($_REQUEST['load_header'] == 'yes')){	//All //crmv@67827
			$result = $adb->query(
				'SELECT newsletterid FROM '.$table_prefix.'_newsletter
				INNER JOIN '.$table_prefix.'_crmentity ON '.$table_prefix.'_crmentity.crmid = '.$table_prefix.'_newsletter.newsletterid
				WHERE deleted = 0 AND campaignid = '.$id
			);
			$newsletterid = array();
			if ($result && $adb->num_rows($result)>0) {
				while($row=$adb->fetchByAssoc($result)) {
					$newsletterid[] = $row['newsletterid'];
				}
			} else {
				return '';
			}
			VteSession::set('statistics_newsletter_'.$id, implode(',',$newsletterid));
		}
		return ' '.$separator.' '.$field.' in ('.VteSession::get('statistics_newsletter_'.$id).')';
	}
	// crmv@142678e
	
	function GetStatisticList($module, $statistic, $query, $button, $returnset, $only_query=false, $xls_export=false) { //crmv@116390
		require_once("data/Tracker.php");
		require_once('include/database/PearDatabase.php');
		global $adb,$app_strings,$current_language;
		$current_module_strings = return_module_language($current_language, $module);
		global $list_max_entries_per_page,$urlPrefix,$currentModule,$theme,$theme_path,$mod_strings;
		// focus_list is the means of passing data to a ListView.
		global $focus_list;
		$smarty = new VteSmarty();
		$button = '<table cellspacing=0 cellpadding=2><tr><td>'.$button.'</td></tr></table>';
		// Added to have Purchase Order as form Title
		$theme_path="themes/".$theme."/";
		$image_path=$theme_path."images/";
		$smarty->assign("MOD", $mod_strings);
		$smarty->assign("APP", $app_strings);
		$smarty->assign("THEME", $theme);
		$smarty->assign("IMAGE_PATH",$image_path);
		$smarty->assign("MODULE",$statistic);

		$mod_listquery = strtolower($statistic)."_listquery";
		VteSession::set($mod_listquery, $query);

		if ($only_query) {
			return;
		}

//		$url_qry .="&order_by=".$order_by."&sorder=".$sorder;
		$count_query = mkCountQuery($query);
		$count_result = $adb->query($count_query);

		if($adb->num_rows($count_result) > 0)
			$noofrows =$adb->query_result($count_result,0,"count");
		else
			$noofrows = $adb->num_rows($count_result);

		//crmv@25809
		if ($_REQUEST['onlycount'] == 'true'){
			return Array('count'=>$noofrows);
		}
		//crmv@25809e

		//Setting Listview session object while sorting/pagination
		if(isset($_REQUEST['relmodule']) && $_REQUEST['relmodule']!='' && $_REQUEST['relmodule'] == $statistic)
		{
			$relmodule = vtlib_purify($_REQUEST['relmodule']);
			if(VteSession::getArray(array('rlvs', $module, $relmodule)))
			{
				setSessionVar(VteSession::getArray(array('rlvs', $module, $relmodule)),$noofrows,$list_max_entries_per_page,$module,$relmodule);
			}
		}
		global $relationId;
		$start = RelatedListViewSession::getRequestCurrentPage($relationId, $query);
		$navigation_array =  VT_getSimpleNavigationValues($start, $list_max_entries_per_page,
		$noofrows);

		$limit_start_rec = ($start-1) * $list_max_entries_per_page;

		// crmv@116390
		if ($xls_export) {
			$list_result = $adb->query($query);
		} else {
			$list_result = $adb->limitQuery($query,$limit_start_rec,$list_max_entries_per_page);
		}
		// crmv@116390e

		//Retreive the List View Table Header
		$id = vtlib_purify($_REQUEST['record']);
		$listview_header = $this->getStatisticListViewHeader($module,$statistic);
		if ($noofrows > 15) {
			$smarty->assign('SCROLLSTART','<div style="overflow:auto;height:315px;width:100%;">');
			$smarty->assign('SCROLLSTOP','</div>');
		}
		$smarty->assign("LISTHEADER", $listview_header);

		$listview_entries = $this->getStatisticListViewEntries($module,$statistic,$list_result,$navigation_array,$returnset);

		$navigationOutput = Array();
		if ($noofrows > 0){
			$navigationOutput[] =  getRecordRangeMessage($list_max_entries_per_page, $limit_start_rec,$noofrows);
			if(empty($id) && !empty($_REQUEST['record'])) $id = vtlib_purify($_REQUEST['record']);
			$navigationOutput[] = $this->getStatisticRelatedTableHeaderNavigation($navigation_array, $url_qry,$module,$statistic,$id);
		}
		$related_entries = array('header'=>$listview_header,'entries'=>$listview_entries,'navigation'=>$navigationOutput);
		return $related_entries;
	}
	function getStatisticListViewHeader($module,$statistic) {
		$list_header = array();
		if ($statistic == 'Message Queue') {
			$list_header = array('Recipient Name','Recipient Email','Newsletter','Schedule Date','Sent Date');
		} elseif ($statistic == 'Sent Messages' || $statistic == 'Bounced Messages') {
			$list_header = array('Recipient Name','Recipient Email','Newsletter','Sent Date','LBL_PREVIEW'); // crmv@38592
		} elseif ($statistic == 'Viewed Messages') {
			$list_header = array('Recipient Name','Recipient Email','Newsletter','Sent Date','No Views','First View','Last View');
		} elseif ($statistic == 'Tracked Link' || $statistic == 'Unsubscriptions') {
			$list_header = array('Recipient Name','Recipient Email','Newsletter','Link','No Click');
		} elseif ($statistic == 'Suppression list') {
			$list_header = array('Recipient Email','Type');
		//crmv@25872
		} elseif ($statistic == 'Failed Messages') {
			$list_header = array('Recipient Name','Recipient Email','Newsletter','Schedule Date','FailedNotes');
		//crmv@25872e
		//crmv@49823
		} elseif ($statistic == 'Newsletter Emails') {
			$list_header = array('Recipient Name','Recipient Email','Newsletter','Sent Date','No Views','LBL_PREVIEW');
		//crmv@49823e
		}
		foreach($list_header as $id => $title) {
			$list_header[$id] = getTranslatedString($title,'Newsletter');
		}
		return $list_header;
	}
	function getStatisticListViewEntries($module,$statistic,$list_result,$navigation_array,$returnset) {
		global $adb;
		$list_entries = array();
		$noofrows = $adb->num_rows($list_result);
		if($navigation_array['start'] !=0) {
			for ($i=1; $i<=$noofrows; $i++)
			{
				if ($statistic == 'Message Queue') {
					$list_entries[] = array(
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"crmid"),'name'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"crmid"),'email'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"newsletterid"),'name'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"date_scheduled"),'datetime'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"date_sent"),'datetime'),
					);
				} elseif ($statistic == 'Sent Messages' || $statistic == 'Bounced Messages') {
					$list_entries[] = array(
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"crmid"),'name'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"crmid"),'email'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"newsletterid"),'name'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"date_sent"),'datetime'),
						$this->getStatisticValue(array($adb->query_result($list_result,$i-1,"newsletterid"), $adb->query_result($list_result,$i-1,"crmid")),'preview'), // crmv@38592
					);
				} elseif ($statistic == 'Viewed Messages') {
					$list_entries[] = array(
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"crmid"),'name'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"crmid"),'email'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"newsletterid"),'name'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"date_sent"),'datetime'),
						$adb->query_result($list_result,$i-1,"num_views"),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"first_view"),'datetime'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"last_view"),'datetime'),
					);
				} elseif ($statistic == 'Tracked Link' || $statistic == 'Unsubscriptions') {
					$list_entries[] = array(
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"crmid"),'name'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"crmid"),'email'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"newsletterid"),'name'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"url"),'url'),
						$adb->query_result($list_result,$i-1,"clicked"),
					);
				} elseif ($statistic == 'Suppression list') {
					$list_entries[] = array(
						$adb->query_result($list_result,$i-1,"email"),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"type"),'translate'),
					);
				//crmv@25872
				} elseif ($statistic == 'Failed Messages') {
					$list_entries[] = array(
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"crmid"),'name'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"crmid"),'email'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"newsletterid"),'name'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"date_scheduled"),'datetime'),
						getTranslatedString($adb->query_result($list_result,$i-1,"note"),'Newsletter'),
					);
				//crmv@25872e
				//crmv@49823
				} elseif ($statistic == 'Newsletter Emails') {
					$list_entries[] = array(
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"crmid"),'name'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"crmid"),'email'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"newsletterid"),'name'),
						$this->getStatisticValue($adb->query_result($list_result,$i-1,"date_sent"),'datetime'),
						$adb->query_result($list_result,$i-1,"num_views"),
						$this->getStatisticValue(array($adb->query_result($list_result,$i-1,"newsletterid"), $adb->query_result($list_result,$i-1,"crmid")),'preview'),
					);
				//crmv@49823e
				}
			}
		}
		return $list_entries;
	}
	function getStatisticRelatedTableHeaderNavigation($navigation_array, $url_qry,$module,$related_module,$recordid) {
		global $app_strings, $adb;
		global $theme;
		global $table_prefix;
		$tabid = getTabid($module);

		$relatedListResult = $adb->pquery('SELECT * FROM '.$table_prefix.'_relatedlists WHERE tabid=? AND label=?', array($tabid,$related_module));
		if(empty($relatedListResult)) return;
		$relatedListRow = $adb->fetch_row($relatedListResult);
		$header = $relatedListRow['label'];
		$actions = $relatedListRow['actions'];
		$functionName = $relatedListRow['name'];

		$urldata = "module=$module&action={$module}Ajax&file=DetailViewAjax&record={$recordid}&".
		"ajxaction=LOADRELATEDLIST&header={$header}&relation_id={$relatedListRow['relation_id']}".
		"&actions={$actions}&{$url_qry}";

		$formattedHeader = str_replace(' ','',$header);
		$target = 'tbl_'.$module.'_'.$formattedHeader;
		$imagesuffix = $module.'_'.$formattedHeader;

		if(($navigation_array['prev']) != 0) {
			$output .= '<a href="javascript:;" onClick="loadRelatedListBlock(\''. $urldata.'&start=1\',\''. $target.'\',\''. $imagesuffix.'\');" alt="'.$app_strings['LBL_FIRST'].'" title="'.$app_strings['LBL_FIRST'].'"><img src="' . vtecrm_imageurl('start.gif', $theme) . '" border="0" align="absmiddle"></a>&nbsp;';
			$output .= '<a href="javascript:;" onClick="loadRelatedListBlock(\''. $urldata.'&start='.$navigation_array['prev'].'\',\''. $target.'\',\''. $imagesuffix.'\');" alt="'.$app_strings['LNK_LIST_PREVIOUS'].'"title="'.$app_strings['LNK_LIST_PREVIOUS'].'"><img src="' . vtecrm_imageurl('previous.gif', $theme) . '" border="0" align="absmiddle"></a>&nbsp;';
		} else {
			$output .= '<img src="' . resourcever('start_disabled.gif') . '" border="0" align="absmiddle">&nbsp;';
			$output .= '<img src="' . resourcever('previous_disabled.gif') . '" border="0" align="absmiddle">&nbsp;';
		}

		$jsHandler = "return VT_disableFormSubmit(event);";
		$output .= "<input class='small' name='pagenum' type='text' value='{$navigation_array['current']}'
		style='width: 3em;margin-right: 0.7em;' onchange=\"loadRelatedListBlock('{$urldata}&start='+this.value+'','{$target}','{$imagesuffix}');\"
		onkeypress=\"$jsHandler\">";
		$output .= "<span name='listViewCountContainerName' class='small' style='white-space: nowrap;'>";
		$computeCount = $_REQUEST['withCount'];
		$output .= $app_strings['LBL_LIST_OF'].' '.$navigation_array['verylast'];
		$output .= '</span>';

		if(($navigation_array['next']) !=0) {
			$output .= '<a href="javascript:;" onClick="loadRelatedListBlock(\''. $urldata.'&start='.$navigation_array['next'].'\',\''. $target.'\',\''. $imagesuffix.'\');"><img src="' . resourcever('next.gif') . '" border="0" align="absmiddle"></a>&nbsp;';
			$output .= '<a href="javascript:;" onClick="loadRelatedListBlock(\''. $urldata.'&start='.$navigation_array['verylast'].'\',\''. $target.'\',\''. $imagesuffix.'\');"><img src="' . resourcever('end.gif') . '" border="0" align="absmiddle"></a>&nbsp;';
		} else {
			$output .= '<img src="' . resourcever('next_disabled.gif') . '" border="0" align="absmiddle">&nbsp;';
			$output .= '<img src="' . resourcever('end_disabled.gif') . '" border="0" align="absmiddle">&nbsp;';
		}
		if($navigation_array['first']=='')
			return;
		else
			return $output;
	}
	function getStatisticValue($value,$type) {
		global $adb, $table_prefix;
		switch($type) {
			case  'name':
				//crmv@25243
				$result = $adb->pquery("select * from ".$table_prefix."_crmentity where crmid=?", array($value));
			    if(!$result || $adb->query_result($result,0,"deleted") == 1) {
			    	return getTranslatedString('LBL_NOT_AVAILABLE','Newsletter');
			    } else {
					$module = getSalesEntityType($value);
					$name = getEntityName($module,$value);
					return "<a href='index.php?module=$module&action=DetailView&record=$value'>".$name[$value]."</a>";
			    }
			    //crmv@25243e
				break;
			case 'email':
				$module = getSalesEntityType($value);
				if (empty($module)) return '';
				$focus = CRMEntity::getInstance($module);
				$focus_newsletter = CRMEntity::getInstance('Newsletter');
				$result = $adb->query("select ".$focus_newsletter->email_fields[$module]['columnname']." from ".$focus_newsletter->email_fields[$module]['tablename']." where ".$focus->tab_name_index[$focus_newsletter->email_fields[$module]['tablename']]." = ".$value);
				if ($result && $adb->num_rows($result)>0) {
					return $adb->query_result($result,0,$focus_newsletter->email_fields[$module]['columnname']);
				}
				break;
			case 'datetime':
				return getDisplayDate($value);
				break;
			case 'translate':
				return getTranslatedString($value,'Newsletter');
				break;
			case 'url':
				$focus = CRMEntity::getInstance('Newsletter');
				if ($value == $focus->url_unsubscription_file) {
					return '<a href="javascript:;" title="'.$value.'">'.getTranslatedString('LBL_UNSUBSCRIPTION_LINK','Newsletter').'</a>';
				} else {
					return $value;
				}
				break;
			// crmv@38592
			case 'preview':
				return '<a href="javascript:;" onClick="openPopup(\'index.php?module=Newsletter&action=NewsletterAjax&file=ShowPreview&record='.$value[0].'&crmid='.$value[1].'\');">'.getTranslatedString('LBL_PREVIEW', 'APP_STRINGS').'</a>';
				break;
			// crmv@38592e
		}
	}
	//crmv@22700e	//crmv@25083e
	
	function save_related_module($module, $crmid, $with_module, $with_crmid, $skip_check=false) {
		if (!is_array($with_crmid)) $with_crmid = array($with_crmid);
		
		parent::save_related_module($module, $crmid, $with_module, $with_crmid, $skip_check);
		
		//crmv@52391
		if (isModuleInstalled('Fiere') && vtlib_isModuleActive('Fiere') && $with_module == 'Targets' && !empty($with_crmid)) {
			$fiereFocus = CRMEntity::getInstance('Fiere');
			foreach($with_crmid as $id) {
				$fiereFocus->create_fiera_to_entity2($crmid,$id);
			}
		}
		if (isModuleInstalled('Telemarketing') && vtlib_isModuleActive('Telemarketing') && $with_module == 'Targets' && !empty($with_crmid)) {
			$tlmktFocus = CRMEntity::getInstance('Telemarketing');
			foreach($with_crmid as $id) {
				$tlmktFocus->create_tlmkt_to_entity2($crmid,$id);
			}
		}
		//crmv@52391e
	}
	
	//crmv@152532
	function getStatistics($record, $newsletterStatistics=false, $ajax=false, $statistics_newsletter_id=null) {
		global $currentModule, $adb, $table_prefix, $app_strings;
		$smarty = new VteSmarty();
		
		$focus = CRMEntity::getInstance($currentModule);
		$focus->retrieve_entity_info_no_html($record,$currentModule);
		
		$smarty->assign('NEWSLETTER_STATISTICS', $newsletterStatistics);
		$smarty->assign('STATISTICSTAB',true);
		$smarty->assign('FASTLOADRELATEDLIST',true);
		
		$smarty->assign('APP',$app_strings);
		$smarty->assign('MODULE',$currentModule);
		$smarty->assign("CAMPAIGNID",$focus->id);
		$smarty->assign("ID",$focus->id);
		if ($newsletterStatistics) {
			$smarty->assign("MODULE",'Newsletter');
			$smarty->assign("ID",$statistics_newsletter_id);
		}
		
		$related_array=getRelatedLists($currentModule,$focus);
		$focus->filterStatisticRelatedLists('maintain',$related_array);//crmv@22700
		$smarty->assign("RELATEDLISTS", $related_array);
		
		require_once('include/ListView/RelatedListViewSession.php');
		$open_related_modules = RelatedListViewSession::getRelatedModulesFromSession();
		$smarty->assign("SELECTEDHEADERS", $open_related_modules);
		
		if ($newsletterStatistics) {
			VteSession::set('statistics_newsletter_'.$focus->id, $statistics_newsletter_id); // crmv@142678
			$smarty->assign('STATISTICS_SELECT','<input type="hidden" id="statistics_newsletter" value="'.$statistics_newsletter_id.'">');
		} else {
			// crmv@142678
			$showNewsletterid = ''; // all newsletters
			//crmv@28170
			//crmv@36534
			$result = $adb->pquery("SELECT
					        ".$table_prefix."_newsletter.newsletterid AS newsletterid,
					        newslettername
					      FROM ".$table_prefix."_newsletter
					        INNER JOIN ".$table_prefix."_crmentity
					          ON ".$table_prefix."_crmentity.crmid = ".$table_prefix."_newsletter.newsletterid
					      WHERE ".$table_prefix."_crmentity.deleted = 0
					          AND ".$table_prefix."_newsletter.campaignid = ?
					      ORDER BY COALESCE(".$table_prefix."_newsletter.date_scheduled,CAST('' AS DATE)) DESC",array($focus->id));
			//crmv@36534 e
			$statistics_newsletter = '<select id="statistics_newsletter" class="small" onchange="StatisticsScript.filter_statistics_newsletter('.$focus->id.',this);">';
			$statistics_newsletter .= '<option value="">'.$app_strings['LBL_ALL'].'</option>';
			if ($result && $adb->num_rows($result)) {
				if (!empty($ajax)) {
					// display selected newsletter
					$showNewsletterid = $statistics_newsletter_id;
				} else {
					// display last newsletter
					$showNewsletterid = $adb->query_result($result,0,'newsletterid'); // last one
				}
				$result->MoveFirst();
				while($row=$adb->fetchByAssoc($result)) {
					$statistics_newsletter_array[$row['newsletterid']] = $row['newslettername'];
				}
				//asort($statistics_newsletter_array);
				foreach ($statistics_newsletter_array as $newsletterid => $newslettername) {
					if ($showNewsletterid == $newsletterid) {
						$selected = 'selected';
					} else {
						$selected = '';
					}
					$statistics_newsletter .= '<option value="'.$newsletterid.'" '.$selected.'>'.$newslettername.'</option>';
				}
			}
			$statistics_newsletter .= '</select>';
			// save in the session for the related lists
			VteSession::set('statistics_newsletter_'.$focus->id, $showNewsletterid);
			$smarty->assign('STATISTICS_SELECT',$statistics_newsletter);
			//crmv@28170e crmv@142678e
		}
		
		include('modules/Campaigns/StatisticsChart.php'); // crmv@38600
		
		// crmv@172994
		$sc = StatisticsChart::getInstance($focus, array_reverse($related_array));
		$chartData = $sc->generateChart();
		$chartPlugin = $sc->getChartPluginName();
		
		if (!empty($ajax)) {
			$html = $smarty->fetch("RelatedListContents.tpl");
			$response = array('success' => true, 'html' => $html, 'chart_data' => $chartData, 'chart_plugin' => $chartPlugin);
			echo Zend_Json::encode($response);
			exit();
		} else {
			$smarty->assign('CHART_DATA', Zend_Json::encode($chartData));
			$smarty->assign('CHART_PLUGIN', $chartPlugin);
			$smarty->display("modules/Campaigns/Statistics.tpl");
		}
		// crmv@172994e
	}
	//crmv@152532e
}
?>