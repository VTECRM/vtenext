<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//crmv@203484 removed including file
//crmv@7216
//require_once('modules/Fax/Fax.php');
//crmv@7216e
//crmv@7217
//require_once('modules/Sms/Sms.php');
//crmv@7217e
class Leads extends CRMEntity {
	var $log;
	var $db;

	var $table_name;
	var $table_index= 'leadid';

	var $tab_name = Array();
	var $tab_name_index = Array();

	var $entity_table;

	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array();

	//construct this from database;
	var $column_fields = Array();
	var $sortby_fields = Array('lastname','firstname','email','phone','company','smownerid','website');

	// This is used to retrieve related vte_fields from form posts.
	var $additional_column_fields = Array('smcreatorid', 'smownerid', 'contactid','potentialid' ,'crmid');

	// This is the list of vte_fields that are in the lists.
	var $list_fields = Array(
		'Last Name'=>Array('leaddetails'=>'lastname'),
		'First Name'=>Array('leaddetails'=>'firstname'),
		'Company'=>Array('leaddetails'=>'company'),
		'Phone'=>Array('leadaddress'=>'phone'),
		'Website'=>Array('leadsubdetails'=>'website'),
		'Email'=>Array('leaddetails'=>'email'),
		'Assigned To'=>Array('crmentity'=>'smownerid')
	);
	var $list_fields_name = Array(
		'Last Name'=>'lastname',
		'First Name'=>'firstname',
		'Company'=>'company',
		'Phone'=>'phone',
		'Website'=>'website',
		'Email'=>'email',
		'Assigned To'=>'assigned_user_id'
	);
	var $list_link_field= 'lastname';

	var $search_fields = Array();
	var $search_fields_name = Array(
		'Name'=>'lastname',
		'Company'=>'company',
		'Fax'=>'fax',
		'Mobile'=>'mobile',
	);

	var $required_fields =  array();

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('assigned_user_id', 'lastname', 'createdtime' ,'modifiedtime');

	//Default Fields for Email Templates -- Pavani
	var $emailTemplate_defaultFields = array('firstname','lastname','leadsource','leadstatus','rating','industry','yahooid','email','annualrevenue','designation','salutation');

	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'lastname';
	var $default_sort_order = 'ASC';

	//var $groupTable = Array('vte_leadgrouprelation','leadid');
	//crmv@10759
	var $search_base_field = 'lastname';
	//crmv@10759 e
	function __construct() {
		global $table_prefix;
		parent::__construct(); // crmv@37004
		$this->table_name = $table_prefix."_leaddetails";
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_leaddetails',$table_prefix.'_leadsubdetails',$table_prefix.'_leadaddress',$table_prefix.'_leadscf');
		$this->tab_name_index = Array($table_prefix.'_crmentity'=>'crmid',$table_prefix.'_leaddetails'=>'leadid',$table_prefix.'_leadsubdetails'=>'leadsubscriptionid',$table_prefix.'_leadaddress'=>'leadaddressid',$table_prefix.'_leadscf'=>'leadid');
		$this->entity_table = $table_prefix."_crmentity";
		$this->customFieldTable = Array($table_prefix.'_leadscf', 'leadid');
		$this->search_fields = Array(
			'Name'=>Array('leaddetails'=>'lastname'),
			'Company'=>Array('leaddetails'=>'company'),
			'Fax'=>Array($table_prefix.'_leadaddress'=>'fax'),
			'Mobile'=>Array($table_prefix.'_leadaddress'=>'mobile'),
		);
		$this->log = LoggerManager::getLogger('lead');
		$this->log->debug("Entering Leads() method ...");
		$this->db = PearDatabase::getInstance();
		$this->column_fields = getColumnFields('Leads');
		$this->log->debug("Exiting Lead method ...");
	}

	/** Function to handle module specific operations when saving a entity
	*/
	function save_module($module)
	{
	}

	/** Function to export the lead records in CSV Format
	* @param reference variable - where condition is passed when the query is executed
	* Returns Export Leads Query.
	*/
	function create_export_query($where,$oCustomView,$viewId)	//crmv@31775
	{
		global $log;
		global $current_user;
		global $table_prefix;
		$log->debug("Entering create_export_query(".$where.") method ...");

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery("Leads", "detail_view");
		$fields_list = getFieldsListFromQuery($sql);

		$query = "SELECT $fields_list,case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name else ".$table_prefix."_groups.groupname end as user_name
	      			FROM ".$this->entity_table."
				INNER JOIN ".$table_prefix."_leaddetails
					ON ".$table_prefix."_crmentity.crmid=".$table_prefix."_leaddetails.leadid
				LEFT JOIN ".$table_prefix."_leadsubdetails
					ON ".$table_prefix."_leaddetails.leadid = ".$table_prefix."_leadsubdetails.leadsubscriptionid
				LEFT JOIN ".$table_prefix."_leadaddress
					ON ".$table_prefix."_leaddetails.leadid=".$table_prefix."_leadaddress.leadaddressid
				LEFT JOIN ".$table_prefix."_leadscf
					ON ".$table_prefix."_leadscf.leadid=".$table_prefix."_leaddetails.leadid
				LEFT JOIN ".$table_prefix."_groups
					ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				LEFT JOIN ".$table_prefix."_users
					ON ".$table_prefix."_crmentity.smownerid = ".$table_prefix."_users.id and ".$table_prefix."_users.status='Active'
				";

		//crmv@94838
		$focus = CRMEntity::getInstance('Newsletter');
		$unsubscribe_table = $focus->email_fields['Leads']['tablename'];
		$unsubscribe_field = $focus->email_fields['Leads']['columnname'];
		$query .= " LEFT JOIN tbl_s_newsletter_g_unsub 
					ON tbl_s_newsletter_g_unsub.email = {$unsubscribe_table}.{$unsubscribe_field} ";
		//crmv@94838e

		//crmv@31775
		$reportFilter = $oCustomView->getReportFilter($viewId);
		if ($reportFilter) {
			$tableNameTmp = $oCustomView->getReportFilterTableName($reportFilter,$current_user->id);
			$query .= " INNER JOIN $tableNameTmp ON $tableNameTmp.id = {$table_prefix}_crmentity.crmid";
		}
		//crmv@31775e
		$query .= $this->getNonAdminAccessControlQuery('Leads',$current_user);
		$where_auto = " ".$table_prefix."_crmentity.deleted=0 AND ".$table_prefix."_leaddetails.converted =0";

		if($where != "")
			$query .= " where ($where) AND ".$where_auto;
		else
			$query .= " where ".$where_auto;
		$query = $this->listQueryNonAdminChange($query, $thismodule);
		$log->debug("Exiting create_export_query method ...");
		//crmv@16173
		return $query;
		//crmv@16173 end
	}

	// crmv@152701
	/** 
	 *Returns a list of the associated faxes
	 */
	function get_faxes($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log, $currentModule;

		$log->debug("Entering get_faxes(".$id.") method ...");
		
		$this_module = $currentModule;
		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$singular_modname = vtlib_toSingular($related_module);

		$button = '<input type="hidden" name="fax_directing_module"><input type="hidden" name="record">';

		if($actions) {
			if(is_string($actions)) $actions = explode(',', strtoupper($actions));
			if(in_array('ADD', $actions) && isPermitted($related_module,1, '') == 'yes') {
				$button .= "<input title='". getTranslatedString('LBL_ADD_NEW')." ". getTranslatedString($singular_modname)."' accessyKey='F' class='crmbutton small create' onclick='fnvshobj(this,\"sendfax_cont\");sendfax(\"$this_module\",$id);' type='button' name='button' value='". getTranslatedString('LBL_ADD_NEW')." ". getTranslatedString($singular_modname)."'>&nbsp;";
			}
		}

		// call standard function
		$ret = $this->get_related_list($id, $cur_tab_id, $rel_tab_id, $actions);
		
		// override button
		$ret['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_faxes method ...");
		return $ret;
	}
	
	/**
	 * Returns a list of the associated sms
	 */
	function get_sms($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log, $currentModule;

		$log->debug("Entering get_sms(".$id.") method ...");
		
		$this_module = $currentModule;
		$related_module = vtlib_getModuleNameById($rel_tab_id);
		$singular_modname = vtlib_toSingular($related_module);

		// create custom button
		$button = '<input type="hidden" name="fax_directing_module"><input type="hidden" name="record">';
		if($actions) {
			if(is_string($actions)) $actions = explode(',', strtoupper($actions));
			if(in_array('ADD', $actions) && isPermitted($related_module,1, '') == 'yes') {
				$button .= "<input title='". getTranslatedString('LBL_ADD_NEW')." ". getTranslatedString($singular_modname)."' accessyKey='F' class='crmbutton small create' onclick='sendsms(\"$this_module\",$id);' type='button' name='button' value='". getTranslatedString('LBL_ADD_NEW')." ". getTranslatedString($singular_modname)."'>&nbsp;"; // crmv@150831
			}
		}
		
		// call standard function
		$ret = $this->get_related_list($id, $cur_tab_id, $rel_tab_id, $actions);
		
		// override button
		$ret['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_sms method ...");
		return $ret;
	}
	// crmv@152701e

	//crmv@16703
	function hide_edit_permission($related_list,$id,$module)
	{
		global $adb,$mod_strings;

		$fieldPos = count($related_list['header'])-1;
		if (!empty($related_list['entries'])) {	//crmv@25809
			foreach($related_list['entries'] as $key => &$entry)
			{
				$tmp = substr($entry[$fieldPos],strpos($entry[$fieldPos],'|')+1);
				$entry[$fieldPos] = $tmp;
			}
		}	//crmv@25809
		return $related_list;
	}
	//crmv@16703e

	/** Function to get the Combo List Values of Leads Field
	 * @param string $list_option
	 * Returns Combo List Options
	*/
	function get_lead_field_options($list_option)
	{
		global $log;
		$log->debug("Entering get_lead_field_options(".$list_option.") method ...");
		$comboFieldArray = getComboArray($this->combofieldNames);
		$log->debug("Exiting get_lead_field_options method ...");
		return $comboFieldArray[$list_option];
	}

	// crmv@186735 - removed code

	/**
	 * Move the related records of the specified list of id's to the given record.
	 * @param String This module name
	 * @param Array List of Entity Id's from which related records need to be transfered
	 * @param Integer Id of the the Record to which the related records are to be moved
	 */
	function transferRelatedRecords($module, $transferEntityIds, $entityId) {
		global $adb,$log;
		global $table_prefix;
		$log->debug("Entering function transferRelatedRecords ($module, $transferEntityIds, $entityId)");

		$rel_table_arr = Array("Activities"=>$table_prefix."_seactivityrel","Documents"=>$table_prefix."_senotesrel","Attachments"=>$table_prefix."_seattachmentsrel",
					"Products"=>$table_prefix."_seproductsrel","Campaigns"=>$table_prefix."_campaignleadrel");

		$tbl_field_arr = Array($table_prefix."_seactivityrel"=>"activityid",$table_prefix."_senotesrel"=>"notesid",$table_prefix."_seattachmentsrel"=>"attachmentsid",
					$table_prefix."_seproductsrel"=>"productid",$table_prefix."_campaignleadrel"=>"campaignid");

		$entity_tbl_field_arr = Array($table_prefix."_seactivityrel"=>"crmid",$table_prefix."_senotesrel"=>"crmid",$table_prefix."_seattachmentsrel"=>"crmid",
					$table_prefix."_seproductsrel"=>"crmid",$table_prefix."_campaignleadrel"=>"leadid");

		foreach($transferEntityIds as $transferId) {
			foreach($rel_table_arr as $rel_module=>$rel_table) {
				$id_field = $tbl_field_arr[$rel_table];
				$entity_id_field = $entity_tbl_field_arr[$rel_table];
				// IN clause to avoid duplicate entries
				$sel_result =  $adb->pquery("select $id_field from $rel_table where $entity_id_field=? " .
						" and $id_field not in (select $id_field from $rel_table where $entity_id_field=?)",
						array($transferId,$entityId));
				$res_cnt = $adb->num_rows($sel_result);
				if($res_cnt > 0) {
					for($i=0;$i<$res_cnt;$i++) {
						$id_field_value = $adb->query_result($sel_result,$i,$id_field);
						$adb->pquery("update $rel_table set $entity_id_field=? where $entity_id_field=? and $id_field=?",
							array($entityId,$transferId,$id_field_value));
					}
				}
			}
		}
		//crmv@15526
		parent::transferRelatedRecords($module, $transferEntityIds, $entityId);
		//crmv@15526 end
		$log->debug("Exiting transferRelatedRecords...");
	}

	/*
	 * Function to get the secondary query part of a report
	 * @param - $module primary module name
	 * @param - $secmodule secondary module name
	 * returns the query string formed on fetching the related data for report for secondary module
	 */
	//crmv@38798
	function generateReportsSecQuery($module,$secmodule,$reporttype='',$useProductJoin=true,$joinUitype10=true){ // crmv@146653
		global $table_prefix;
		$query = $this->getRelationQuery($module,$secmodule,$table_prefix."_leaddetails","leadid");
		$query .= " AND {$table_prefix}_leaddetails.converted = 0"; //crmv@92119
		//crmv@21249
		$query .= " left join ".$table_prefix."_leadaddress on ".$table_prefix."_leaddetails.leadid = ".$table_prefix."_leadaddress.leadaddressid
			left join ".$table_prefix."_leadsubdetails on ".$table_prefix."_leadsubdetails.leadsubscriptionid = ".$table_prefix."_leaddetails.leadid
			left join ".$table_prefix."_leadscf on ".$table_prefix."_leadscf.leadid = ".$table_prefix."_leaddetails.leadid
			left join ".$table_prefix."_groups ".$table_prefix."_groupsLeads on ".$table_prefix."_groupsLeads.groupid = ".$table_prefix."_crmentityLeads.smownerid
			left join ".$table_prefix."_users ".$table_prefix."_usersLeads on ".$table_prefix."_usersLeads.id = ".$table_prefix."_crmentityLeads.smownerid ";
		//crmv@21249e
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
			"Calendar" => array($table_prefix."_seactivityrel"=>array("crmid","activityid"),$table_prefix."_leaddetails"=>"leadid"),
			"Products" => array($table_prefix."_seproductsrel"=>array("crmid","productid"),$table_prefix."_leaddetails"=>"leadid"),
			"Campaigns" => array($table_prefix."_campaignleadrel"=>array("leadid","campaignid"),$table_prefix."_leaddetails"=>"leadid"),
			"Documents" => array($table_prefix."_senotesrel"=>array("crmid","notesid"),$table_prefix."_leaddetails"=>"leadid"),
			"Services" => array($table_prefix."_crmentityrel"=>array("crmid","relcrmid"),$table_prefix."_leaddetails"=>"leadid"),
		);
		return $rel_tables[$secmodule];
	}

	// Function to unlink an entity with given Id from another entity
	function unlinkRelationship($id, $return_module, $return_id) {
		global $log;
		global $table_prefix;
		if(empty($return_module) || empty($return_id)) return;

		if($return_module == 'Campaigns') {
			$sql = 'DELETE FROM '.$table_prefix.'_campaignleadrel WHERE leadid=? AND campaignid=?';
			$this->db->pquery($sql, array($id, $return_id));
		}
		elseif($return_module == 'Products') {
			$sql = 'DELETE FROM '.$table_prefix.'_seproductsrel WHERE crmid=? AND productid=?';
			$this->db->pquery($sql, array($id, $return_id));
		} else {
			$sql = 'DELETE FROM '.$table_prefix.'_crmentityrel WHERE (crmid=? AND relmodule=? AND relcrmid=?) OR (relcrmid=? AND module=? AND crmid=?)';
			$params = array($id, $return_module, $return_id, $id, $return_module, $return_id);
			$this->db->pquery($sql, $params);
		}
		$this->db->pquery("UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid IN (?,?)", array($this->db->formatDate(date('Y-m-d H:i:s'), true), $id, $return_id)); // crmv@49398 crmv@69690
	}

//End

	//crmv@22700	crmv@54900
	function get_campaigns_newsletter($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log,$currentModule,$current_user;//crmv@203484 removed global singlepane
		global $table_prefix;
		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e
		$log->debug("Entering get_campaigns(".$id.") method ...");
		$this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance($related_module);
        vtlib_setup_modulevars($related_module, $other);
		$singular_modname = vtlib_toSingular($related_module);

		$parenttab = getParentTab();

		if($singlepane_view == true)//crmv@203484 changed to normal bool true, not string 'true'
			$returnset = '&return_module='.$this_module.'&return_action=DetailView&return_id='.$id;
		else
			$returnset = '&return_module='.$this_module.'&return_action=CallRelatedList&return_id='.$id;

		// crmv@120052
		// remove the sortering from the request, since I'm, loading another related
		$oldReq = $_REQUEST;
		unset($_REQUEST['order_by'], $_REQUEST['sorder']);
		// crmv@120052e

		global $adb, $onlyquery, $currentModule;
		$onlyquery_bck = $onlyquery; $onlyquery = true;
		$onlybutton_bck = $onlybutton; $onlybutton = false;
		$targetsModule = Vtecrm_Module::getInstance('Targets');
		$targetsFocus = CRMEntity::getInstance('Targets');
		$this->get_related_list($id, $cur_tab_id, $targetsModule->id);
		$result = $adb->query(VteSession::get('targets_listquery'));
		$onlyquery = $onlyquery_bck;
		$onlybutton = $onlybutton_bck;
		//TODO: trovare anche i Target inclusi in questi Target
		$campaigns = array();
		if ($result && $adb->num_rows($result)>0) {
			// crmv@120052
			$currentModuleBackup = $currentModule;
			$currentModule = $targetsModule->name;
			// crmv@120052e
			while($row=$adb->fetchByAssoc($result)) {
				$onlyquery_bck = $onlyquery; $onlyquery = true;
				$onlybutton_bck = $onlybutton; $onlybutton = false;
				$targetsFocus->get_related_list($row['crmid'], $targetsModule->id, 26);
				$result1 = $adb->query(VteSession::get('campaigns_listquery'));
				$onlyquery = $onlyquery_bck;
				$onlybutton = $onlybutton_bck;
				if ($result1 && $adb->num_rows($result1)>0) {
					while($row1=$adb->fetchByAssoc($result1)) {
						$campaigns[$row1['crmid']] = '';
					}
				}
			}
			$currentModule = $currentModuleBackup; // crmv@120052
		}

		// restore the request
		$_REQUEST = $oldReq; // crmv@120052

		$campaigns = array_keys($campaigns);
		if (!empty($campaigns)) {
			$query = "SELECT case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name else ".$table_prefix."_groups.groupname end as user_name ,
					".$table_prefix."_campaign.campaignid, ".$table_prefix."_campaign.campaignname, ".$table_prefix."_campaign.campaigntype, ".$table_prefix."_campaign.campaignstatus,
					".$table_prefix."_campaign.expectedrevenue, ".$table_prefix."_campaign.closingdate, ".$table_prefix."_crmentity.crmid, ".$table_prefix."_crmentity.smownerid,
					".$table_prefix."_crmentity.modifiedtime from ".$table_prefix."_campaign
					inner join ".$table_prefix."_campaignscf on ".$table_prefix."_campaignscf.campaignid = ".$table_prefix."_campaign.campaignid
					inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_campaign.campaignid
					left join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid=".$table_prefix."_crmentity.smownerid
					left join ".$table_prefix."_users on ".$table_prefix."_users.id = ".$table_prefix."_crmentity.smownerid
					where ".$table_prefix."_campaign.campaignid in (".implode(',',$campaigns).") and ".$table_prefix."_crmentity.deleted=0";
			$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);
		}
		if($return_value == null) $return_value = Array();
		else {
			unset($return_value['header'][0]);
			if(is_array($return_value['entries'])){
				foreach ($return_value['entries'] as $id => $info) {
					unset($return_value['entries'][$id][0]);
				}
			}
		}
		$log->debug("Exiting get_campaigns method ...");
		return $return_value;
	}
	//crmv@22700e	crmv@54900e
	function updateConvertLead() {
		global $adb;
		global $table_prefix;
		$tabIdsResult = $adb->query('SELECT tabid, name FROM '.$table_prefix.'_tab');
		$noOfTabs = $adb->num_rows($tabIdsResult);
		$tabIdsList = array();
		for ($i = 0; $i < $noOfTabs; ++$i) {
			$tabIdsList[$adb->query_result($tabIdsResult, $i, 'name')] = $adb->query_result($tabIdsResult, $i, 'tabid');
		}
		$leadTab = $tabIdsList['Leads'];
		$accountTab = $tabIdsList['Accounts'];
		$contactTab = $tabIdsList['Contacts'];
		$potentialTab = $tabIdsList['Potentials'];

		$fieldMap = array(
			array('industry', 'industry', null, null),
			array('phone', 'phone', 'phone', null),
			array('fax', 'fax', 'fax', null),
			array('rating', 'rating', null, null),
			array('email', 'email1', 'email', null),
			array('website', 'website', null, null),
			array('city', 'bill_city', 'mailingcity', null),
			array('code', 'bill_code', 'mailingcode', null),
			array('country', 'bill_country', 'mailingcountry', null),
			array('state', 'bill_state', 'mailingstate', null),
			array('lane', 'bill_street', 'mailingstreet', null),
			array('pobox', 'bill_pobox', 'mailingpobox', null),
			array('city', 'ship_city', null, null),
			array('code', 'ship_code', null, null),
			array('country', 'ship_country', null, null),
			array('state', 'ship_state', null, null),
			array('lane', 'ship_street', null, null),
			array('pobox', 'ship_pobox', null, null),
			array('description', 'description', 'description', 'description'),
			array('salutationtype', null, 'salutationtype', null),
			array('firstname', null, 'firstname', null),
			array('lastname', null, 'lastname', null),
			array('mobile', null, 'mobile', null),
			array('designation', null, 'title', null),
			array('yahooid', null, 'yahooid', null),
			array('leadsource', null, 'leadsource', 'leadsource'),
			array('leadstatus', null, null, null),
			array('noofemployees', 'employees', null, null),
			array('annualrevenue', 'annual_revenue', null, null)
		);

		//fix seq for convertleadmapping table
		$table = $table_prefix.'_convertleadmapping';
		$sql = "select max(cfmid) as crmid from {$table}";
		$res = $adb->query($sql);
		if ($res){
			$adb->database->DropSequence($table."_seq");
			$adb->database->CreateSequence($table."_seq",$adb->query_result($res,0,'crmid')+1);
		}
		//fix end
		//fix fieldid missing on modules not active
		foreach ($fieldMap as $key=>$arr){
			if ($arr[0] != null){
				$fieldids['Leads'][$arr[0]] = null;
			}
			if ($arr[1] != null){
				$fieldids['Accounts'][$arr[1]] = null;
			}
			if ($arr[2] != null){
				$fieldids['Contacts'][$arr[2]] = null;
			}
			if ($arr[3] != null){
				$fieldids['Potentials'][$arr[3]] = null;
			}
		}
		//aggiungo anche gli altri
		$fieldids['Leads']['company'] = null;
		$fieldids['Leads']['email'] = null;
		$fieldids['Leads']['firstname'] = null;
		$fieldids['Leads']['lastname'] = null;
		$fieldids['Accounts']['accountname'] = null;
		$fieldids['Accounts']['email1'] = null;
		$fieldids['Contacts']['email'] = null;
		$fieldids['Contacts']['firstname'] = null;
		$fieldids['Contacts']['lastname'] = null;
		$fieldids['Potentials']['potentialname'] = null;
		foreach ($fieldids as $modulename_fieldid=>$arr_fieldid){
			$sql_fields = "select fieldname,fieldid from {$table_prefix}_field where tabid = ? and fieldname in (".generateQuestionMarks($arr_fieldid).")";
			$res_fields = $adb->pquery($sql_fields,Array($tabIdsList[$modulename_fieldid],array_keys($arr_fieldid)));
			if ($res_fields){
				while($row = $adb->fetchByAssoc($res_fields)){
					$fieldids[$modulename_fieldid][$row['fieldname']] = $row['fieldid'];
				}
			}
		}
		$mapSql = "INSERT INTO ".$table_prefix."_convertleadmapping(cfmid,leadfid,accountfid,contactfid,potentialfid) values(?,?,?,?,?)";
		foreach ($fieldMap as $values) {
			$leadfid = $fieldids['Leads'][$values[0]];
			if ($leadfid == '') continue;
			$accountfid = $fieldids['Accounts'][$values[1]];
			if ($accountfid == '') $accountfid = null;
			$contactfid = $fieldids['Contacts'][$values[2]];
			if ($contactfid == '') $contactfid = null;
			$potentialfid = $fieldids['Potentials'][$values[3]];
			if ($potentialfid == '') $potentialfid = null;
			$adb->pquery($mapSql, array($adb->getUniqueID($table_prefix."_convertleadmapping"), $leadfid, $accountfid, $contactfid, $potentialfid));
		}

		$adb->query("DELETE FROM ".$table_prefix."_convertleadmapping WHERE accountfid=0 AND contactfid=0 AND potentialfid=0");

		$check_mapping = "SELECT 1 FROM ".$table_prefix."_convertleadmapping WHERE leadfid=? AND accountfid=? AND contactfid=? AND  potentialfid=?";
		$insert_mapping = "INSERT INTO ".$table_prefix."_convertleadmapping(cfmid,leadfid,accountfid,contactfid,potentialfid,editable) VALUES(?,?,?,?,?,?)";
		$update_mapping = "UPDATE ".$table_prefix."_convertleadmapping SET editable=0 WHERE leadfid=? AND accountfid=? AND contactfid=? AND potentialfid=?";

		$check_res = $adb->pquery($check_mapping, array($fieldids['Leads']['company'], $fieldids['Accounts']['accountname'], 0, $fieldids['Potentials']['potentialname']));
		if ($adb->num_rows($check_res) > 0) {
			$adb->pquery($update_mapping, array($fieldids['Leads']['company'], $fieldids['Accounts']['accountname'], 0, $fieldids['Potentials']['potentialname']));
		} else {
			$adb->pquery("DELETE FROM ".$table_prefix."_convertleadmapping WHERE leadfid=? ", array($fieldids['Leads']['company']));
			$adb->pquery($insert_mapping, array($adb->getUniqueID($table_prefix."_convertleadmapping"), $fieldids['Leads']['company'], $fieldids['Accounts']['accountname'], null, $fieldids['Potentials']['potentialname'], 0));
		}

		$check_res = $adb->pquery($check_mapping, array($fieldids['Leads']['email'], $fieldids['Accounts']['email1'], $fieldids['Contacts']['email'], 0));
		if ($adb->num_rows($check_res) > 0) {
			$adb->pquery($update_mapping, array($fieldids['Leads']['email'], $fieldids['Accounts']['email1'], $fieldids['Contacts']['email'], 0));
		} else {
			$adb->pquery("DELETE FROM ".$table_prefix."_convertleadmapping WHERE leadfid=? ", array($fieldids['Leads']['email']));
			$adb->pquery($insert_mapping, array($adb->getUniqueID($table_prefix."_convertleadmapping"), $fieldids['Leads']['email'], $fieldids['Accounts']['email1'], $fieldids['Contacts']['email'], null, 0));
		}

		$check_res = $adb->pquery($check_mapping, array($fieldids['Leads']['firstname'], 0, $fieldids['Contacts']['firstname'], 0));
		if ($adb->num_rows($check_res) > 0) {
			$adb->pquery($update_mapping, array($fieldids['Leads']['firstname'], 0, $fieldids['Contacts']['firstname'], 0));
		} else {
			$adb->pquery("DELETE FROM ".$table_prefix."_convertleadmapping WHERE leadfid=? ", array($fieldids['Leads']['firstname']));
			$adb->pquery($insert_mapping, array($adb->getUniqueID($table_prefix."_convertleadmapping"), $fieldids['Leads']['firstname'], null, $fieldids['Contacts']['firstname'], null, 0));
		}

		$check_res = $adb->pquery($check_mapping, array($fieldids['Leads']['lastname'], 0, $fieldids['Contacts']['lastname'], 0));
		if ($adb->num_rows($check_res) > 0) {
			$adb->pquery($update_mapping, array($fieldids['Leads']['lastname'], 0, $fieldids['Contacts']['lastname'], 0));
		} else {
			$adb->pquery("DELETE FROM ".$table_prefix."_convertleadmapping WHERE leadfid=? ", array($fieldids['Leads']['lastname']));
			$adb->pquery($insert_mapping, array($adb->getUniqueID($table_prefix."_convertleadmapping"), $fieldids['Leads']['lastname'], null, $fieldids['Contacts']['lastname'], null, 0));
		}
		//fix end
	}
	
	/* crmv@181281 moved code in CRMEntity and ExportUtils */
	
	//crmv@174987
	function getListQuery($module, $where='') {
		global $table_prefix;
		$where .= " AND {$table_prefix}_leaddetails.converted = 0 ";
		return parent::getListQuery($module, $where);
	}
	//crmv@174987e
	
	// crmv@191501
	public function isConverted($leadid = null) {
		if (!$leadid) $leadid = $this->id;
		$conv = getSingleFieldValue($this->table_name, 'converted', $this->table_index, $leadid);
		return $conv === '1';
	}
	
	public function getConvertedContact($leadid = null) {
		global $adb, $table_prefix;
		
		if (!$leadid) $leadid = $this->id;
		$res = $adb->pquery("SELECT contactid FROM {$table_prefix}_leadconvrel WHERE leadid = ?", array($leadid));
		return $adb->query_result_no_html($res, 0, 'contactid');
	}
	// crmv@191501e
}
?>