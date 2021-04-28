<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//crmv@203484 removed including file
require_once('modules/Emails/Emails.php');


// Contact is used to store customer information.
class Contacts extends CRMEntity {
	var $log;
	var $db;

	var $table_name;
	var $table_index= 'contactid';
	var $tab_name = Array();
	var $tab_name_index = Array();
	/**
	 * Mandatory table for supporting custom fields.
	 */
	var $customFieldTable = Array();

	var $column_fields = Array();

	var $sortby_fields = Array('lastname','firstname','title','email','phone','smownerid','accountname','accountid');

	var $list_link_field= 'lastname';

	// This is the list of vte_fields that are in the lists.
	var $list_fields = Array(
	'Last Name' => Array('contactdetails'=>'lastname'),
	'First Name' => Array('contactdetails'=>'firstname'),
	'Title' => Array('contactdetails'=>'title'),
	'Account Name' => Array('account'=>'accountid'),
	'Email' => Array('contactdetails'=>'email'),
	'Office Phone' => Array('contactdetails'=>'phone'),
	'Assigned To' => Array('crmentity'=>'smownerid')
	);

	var $range_fields = Array(
		'first_name',
		'last_name',
		'primary_address_city',
		'account_name',
		'account_id',
		'id',
		'email1',
		'salutation',
		'title',
		'phone_mobile',
		'reports_to_name',
		'primary_address_street',
		'primary_address_city',
		'primary_address_state',
		'primary_address_postalcode',
		'primary_address_country',
		'alt_address_city',
		'alt_address_street',
		'alt_address_city',
		'alt_address_state',
		'alt_address_postalcode',
		'alt_address_country',
		'office_phone',
		'home_phone',
		'other_phone',
		'fax',
		'department',
		'birthdate',
		'assistant_name',
		'assistant_phone');


	var $list_fields_name = Array(
	'Last Name' => 'lastname',
	'First Name' => 'firstname',
	'Title' => 'title',
	'Account Name' => 'account_id',
	'Email' => 'email',
	'Office Phone' => 'phone',
	'Assigned To' => 'assigned_user_id'
	);

	var $search_fields = Array(
	'Name' => Array('contactdetails'=>'lastname'),
	'Title' => Array('contactdetails'=>'title'),
	'Account Name'=>Array('contactdetails'=>'account_id'),
	'Email' => Array('contactdetails'=>'email'),
	'Fax' => Array('contactdetails'=>'fax'),
	'Mobile' => Array('contactdetails'=>'mobile'),
	'Assigned To'=>Array('crmentity'=>'smownerid'),
		);

	var $search_fields_name = Array(
	'Name' => 'lastname',
	'Title' => 'title',
	'Account Name'=>'account_id',
	'Email' => 'email',
	'Fax' => 'fax',
	'Mobile' => 'mobile',
	'Assigned To'=>'assigned_user_id'
	);

	// This is the list of vte_fields that are required
	var $required_fields =  array("lastname"=>1);

	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vte_field.fieldname values.
	var $mandatory_fields = Array('assigned_user_id','lastname','createdtime' ,'modifiedtime','imagename');

	//Default Fields for Email Templates -- Pavani
	var $emailTemplate_defaultFields = array('firstname','lastname','salutation','title','email','department','phone','mobile','support_start_date','support_end_date');

	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'lastname';
	var $default_sort_order = 'ASC';
	//crmv@10759
	var $search_base_field = 'lastname';
	//crmv@10759 e
	function __construct() {
		global $table_prefix;
		parent::__construct(); // crmv@37004
		// crmv@100399
		$this->extra_relation_tables = array(
			'Events' => array(
				'relation_table' => "{$table_prefix}_cntactivityrel",
				'relation_table_id' => 'contactid',
				'relation_table_otherid' => 'activityid',
				//relation_table_module
				//relation_table_othermodule
			),
		);
		// crmv@100399e
		$this->table_name = $table_prefix."_contactdetails";
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_contactdetails',$table_prefix.'_contactaddress',$table_prefix.'_contactsubdetails',$table_prefix.'_contactscf',$table_prefix.'_customerdetails');
		$this->tab_name_index = Array($table_prefix.'_crmentity'=>'crmid',$table_prefix.'_contactdetails'=>'contactid',$table_prefix.'_contactaddress'=>'contactaddressid',$table_prefix.'_contactsubdetails'=>'contactsubscriptionid',$table_prefix.'_contactscf'=>'contactid',$table_prefix.'_customerdetails'=>'customerid');
		$this->customFieldTable = Array($table_prefix.'_contactscf', 'contactid');
		$this->log = LoggerManager::getLogger('contact');
		$this->db = PearDatabase::getInstance();
		$this->column_fields = getColumnFields('Contacts');
	}

	/** Function to get the number of Contacts assigned to a particular User.
	*  @param varchar $user name - Assigned to User
	*  Returns the count of contacts assigned to user.
	*/
	function getCount($user_name)
	{
		global $log;
		global $table_prefix;
		$log->debug("Entering getCount(".$user_name.") method ...");
		$query = "select count(*) from ".$table_prefix."_contactdetails  inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_contactdetails.contactid inner join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_crmentity.smownerid where user_name=? and ".$table_prefix."_crmentity.deleted=0";
		$result = $this->db->pquery($query,array($user_name),true,"Error retrieving contacts count");
		$rows_found =  $this->db->getRowCount($result);
		$row = $this->db->fetchByAssoc($result, 0);


		$log->debug("Exiting getCount method ...");
		return $row["count(*)"];
	}

    /** Function to process list query for a given query
    *  @param $query
    *  Returns the results of query in array format
    */
    function process_list_query1($query)
    {
	global $log;
	$log->debug("Entering process_list_query1(".$query.") method ...");

        $result =& $this->db->query($query,true,"Error retrieving $this->object_name list: ");
        $list = Array();
        $rows_found =  $this->db->getRowCount($result);
        if($rows_found != 0)
        {
		   $contact = Array();
               for($index = 0 , $row = $this->db->fetchByAssoc($result, $index); $row && $index <$rows_found;$index++, $row = $this->db->fetchByAssoc($result, $index))

             {
                foreach($this->range_fields as $columnName)
                {
                    if (isset($row[$columnName])) {

                        $contact[$columnName] = $row[$columnName];
                    }
                    else
                    {
                            $contact[$columnName] = "";
                    }
	     }
// TODO OPTIMIZE THE QUERY ACCOUNT NAME AND ID are set separetly for every vte_contactdetails and hence
// vte_account query goes for ecery single vte_account row

                    $list[] = $contact;
                }
        }

        $response = Array();
        $response['list'] = $list;
        $response['row_count'] = $rows_found;
        $response['next_offset'] = $next_offset;
        $response['previous_offset'] = $previous_offset;


	$log->debug("Exiting process_list_query1 method ...");
        return $response;
    }


    /** Function to process list query for Plugin with Security Parameters for a given query
    *  @param $query
    *  Returns the results of query in array format
    */
    function plugin_process_list_query($query)
    {
          global $log,$adb,$current_user;
          global $table_prefix;
          $log->debug("Entering process_list_query1(".$query.") method ...");
          $permitted_field_lists = Array();
          require('user_privileges/requireUserPrivileges.php'); // crmv@39110
          if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] == 0)
          {
              $sql1 = "select columnname from ".$table_prefix."_field where tabid=4 and block <> 75 and ".$table_prefix."_field.presence in (0,2)";
			  $params1 = array();
          }else
          {
              $profileList = getCurrentUserProfileList();
              $sql1 = "select columnname from ".$table_prefix."_field inner join ".$table_prefix."_def_org_field on ".$table_prefix."_def_org_field.fieldid=".$table_prefix."_field.fieldid where ".$table_prefix."_field.tabid=4 and ".$table_prefix."_field.block <> 6 and ".$table_prefix."_field.block <> 75 and ".$table_prefix."_field.displaytype in (1,2,4,3) and ".$table_prefix."_def_org_field.visible=0 and ".$table_prefix."_field.presence in (0,2)";
			  $params1 = array();
 			  $sql1.=" AND EXISTS(SELECT * FROM ".$table_prefix."_profile2field WHERE ".$table_prefix."_profile2field.fieldid = ".$table_prefix."_field.fieldid ";
          	  if (count($profileList) > 0) {
			  	 $sql1.=" AND ".$table_prefix."_profile2field.profileid IN (". generateQuestionMarks($profileList) .") ";
			  	 array_push($params1, $profileList);
			  }
 			  $sql1.=" AND ".$table_prefix."_profile2field.visible = 0) ";
          }
          $result1 = $this->db->pquery($sql1, $params1);
          for($i=0;$i < $adb->num_rows($result1);$i++)
          {
              $permitted_field_lists[] = $adb->query_result($result1,$i,'columnname');
          }

          $result =& $this->db->query($query,true,"Error retrieving $this->object_name list: ");
          $list = Array();
          $rows_found =  $this->db->getRowCount($result);
          if($rows_found != 0)
          {
              for($index = 0 , $row = $this->db->fetchByAssoc($result, $index); $row && $index <$rows_found;$index++, $row = $this->db->fetchByAssoc($result, $index))
              {
                  $contact = Array();

		  $contact[lastname] = in_array("lastname",$permitted_field_lists) ? $row[lastname] : "";
		  $contact[firstname] = in_array("firstname",$permitted_field_lists)? $row[firstname] : "";
		  $contact[email] = in_array("email",$permitted_field_lists) ? $row[email] : "";


                  if(in_array("accountid",$permitted_field_lists))
                  {
                      $contact[accountname] = $row[accountname];
                      $contact[account_id] = $row[accountid];
                  }else
		  {
                      $contact[accountname] = "";
                      $contact[account_id] = "";
		  }
                  $contact[contactid] =  $row[contactid];
                  $list[] = $contact;
              }
          }

          $response = Array();
          $response['list'] = $list;
          $response['row_count'] = $rows_found;
          $response['next_offset'] = $next_offset;
          $response['previous_offset'] = $previous_offset;
          $log->debug("Exiting process_list_query1 method ...");
          return $response;
    }

	/** 
	 * Returns a list of the associated tasks
	 */
	function get_activities($id, $cur_tab_id, $rel_tab_id, $actions=false) {
		global $log,$currentModule,$current_user;//crmv@203484 removed global singlepane
		global $table_prefix;
		//crmv@203484
		$VTEP = VTEProperties::getInstance();
		$singlepane_view = $VTEP->getProperty('layout.singlepane_view');
		//crmv@203484e
		$log->debug("Entering get_activities(".$id.") method ...");
		$this_module = $currentModule;

        $related_module = vtlib_getModuleNameById($rel_tab_id);
		$other = CRMEntity::getInstance('Activity');
        vtlib_setup_modulevars($related_module, $other);
		$singular_modname = vtlib_toSingular($related_module);

		$parenttab = getParentTab();

		if($singlepane_view == true)//crmv@203484 changed to normal bool true, not string 'true'
			$returnset = '&return_module='.$this_module.'&return_action=DetailView&return_id='.$id;
		else
			$returnset = '&return_module='.$this_module.'&return_action=CallRelatedList&return_id='.$id;

		$button = '';
		$button .= '<input type="hidden" name="activity_mode">';
		if($actions) {
			$button .= $this->get_related_buttons($currentModule, $id, $related_module, $actions); // crmv@43864
		}

		$query = "SELECT case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name else ".$table_prefix."_groups.groupname end as user_name," .
				" ".$table_prefix."_contactdetails.lastname, ".$table_prefix."_contactdetails.firstname,  ".$table_prefix."_activity.activityid ," .
				" ".$table_prefix."_activity.subject, ".$table_prefix."_activity.activitytype, ".$table_prefix."_activity.date_start, ".$table_prefix."_activity.due_date," .
				" ".$table_prefix."_activity.time_start,".$table_prefix."_activity.time_end, ".$table_prefix."_cntactivityrel.contactid, ".$table_prefix."_crmentity.crmid," .
				" ".$table_prefix."_crmentity.smownerid, ".$table_prefix."_crmentity.modifiedtime, " .
				" case when (".$table_prefix."_activity.activitytype = 'Task') then ".$table_prefix."_activity.status else ".$table_prefix."_activity.eventstatus end as status, " .
				" ".$table_prefix."_seactivityrel.crmid as parent_id " .
				" from ".$table_prefix."_contactdetails " .
				" inner join ".$table_prefix."_cntactivityrel on ".$table_prefix."_cntactivityrel.contactid = ".$table_prefix."_contactdetails.contactid" .
				" inner join ".$table_prefix."_activity on ".$table_prefix."_cntactivityrel.activityid=".$table_prefix."_activity.activityid" .
				" inner join ".$table_prefix."_activitycf on ".$table_prefix."_activitycf.activityid = ".$table_prefix."_activity.activityid" .
				" inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid = ".$table_prefix."_cntactivityrel.activityid " .
				" left join ".$table_prefix."_seactivityrel on ".$table_prefix."_seactivityrel.activityid = ".$table_prefix."_cntactivityrel.activityid " .
				" left join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_crmentity.smownerid" .
				" left outer join ".$table_prefix."_recurringevents on ".$table_prefix."_recurringevents.activityid=".$table_prefix."_activity.activityid" .
				" left join ".$table_prefix."_groups on ".$table_prefix."_groups.groupid=".$table_prefix."_crmentity.smownerid" .
				" where ".$table_prefix."_contactdetails.contactid=".$id." and ".$table_prefix."_crmentity.deleted = 0" .
				" AND (".$table_prefix."_activity.activitytype = 'Task' OR ".$table_prefix."_activity.activitytype IN ".getActivityTypeValues('all','format_sql').")";

		$return_value = GetRelatedList($this_module, $related_module, $other, $query, $button, $returnset);

		if($return_value == null) $return_value = Array();
		$return_value['CUSTOM_BUTTON'] = $button;

		$log->debug("Exiting get_activities method ...");
		return $return_value;
	}

	// crmv@152701
	/** Returns a list of the associated faxes
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

	/** Function to export the contact records in CSV Format
	* @param reference variable - where condition is passed when the query is executed
	* Returns Export Contacts Query.
	*/
	function create_export_query($where,$oCustomView,$viewId)	//crmv@31775
	{
		global $log;
		global $current_user;
		global $table_prefix;
		$log->debug("Entering create_export_query(".$where.") method ...");

		//To get the Permitted fields query and the permitted fields list
		$sql = getPermittedFieldsQuery("Contacts", "detail_view");
		$fields_list = getFieldsListFromQuery($sql);

		$query = "SELECT ".$table_prefix."_contactdetails.salutation as \"Salutation\",$fields_list,case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name else ".$table_prefix."_groups.groupname end as user_name
                                FROM ".$table_prefix."_contactdetails
                                inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_contactdetails.contactid
                                LEFT JOIN ".$table_prefix."_users ON ".$table_prefix."_crmentity.smownerid=".$table_prefix."_users.id and ".$table_prefix."_users.status='Active'
                                LEFT JOIN ".$table_prefix."_account on ".$table_prefix."_contactdetails.accountid=".$table_prefix."_account.accountid
				left join ".$table_prefix."_contactaddress on ".$table_prefix."_contactaddress.contactaddressid=".$table_prefix."_contactdetails.contactid
				left join ".$table_prefix."_contactsubdetails on ".$table_prefix."_contactsubdetails.contactsubscriptionid=".$table_prefix."_contactdetails.contactid
			        left join ".$table_prefix."_contactscf on ".$table_prefix."_contactscf.contactid=".$table_prefix."_contactdetails.contactid
			        left join ".$table_prefix."_customerdetails on ".$table_prefix."_customerdetails.customerid=".$table_prefix."_contactdetails.contactid
	                        LEFT JOIN ".$table_prefix."_groups
                        	        ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
				LEFT JOIN ".$table_prefix."_contactdetails ".$table_prefix."_contactdetails2
					ON ".$table_prefix."_contactdetails2.contactid = ".$table_prefix."_contactdetails.reportsto";

		//crmv@94838
		$focus = CRMEntity::getInstance('Newsletter');
		$unsubscribe_table = $focus->email_fields['Contacts']['tablename'];
		$unsubscribe_field = $focus->email_fields['Contacts']['columnname'];
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

		$query .= getNonAdminAccessControlQuery('Contacts',$current_user);

		$where_auto = " ".$table_prefix."_crmentity.deleted = 0 ";

		if($where != "")
			$query .= "  WHERE ($where) AND ".$where_auto;
		else
			$query .= "  WHERE ".$where_auto;

		$query = $this->listQueryNonAdminChange($query, 'Contacts');

		$log->info("Export Query Constructed Successfully");
		$log->debug("Exiting create_export_query method ...");
		return $query;
	}

/** Function to get the Columnnames of the Contacts
* Used By vteCRM Word Plugin
* Returns the Merge Fields for Word Plugin
*/
function getColumnNames()
{
	global $log, $current_user;
	global $table_prefix;
	$log->debug("Entering getColumnNames() method ...");
	require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] == 0)
	{
	 $sql1 = "select fieldlabel from ".$table_prefix."_field where tabid=4 and block <> 75 and ".$table_prefix."_field.presence in (0,2)";
	 $params1 = array();
	}else
	{
	 $profileList = getCurrentUserProfileList();
	 $sql1 = "select ".$table_prefix."_field.fieldid,fieldlabel from ".$table_prefix."_field inner join ".$table_prefix."_def_org_field on ".$table_prefix."_def_org_field.fieldid=".$table_prefix."_field.fieldid where ".$table_prefix."_field.tabid=4 and ".$table_prefix."_field.block <> 75 and ".$table_prefix."_field.displaytype in (1,2,4,3) and ".$table_prefix."_def_org_field.visible=0 and ".$table_prefix."_field.presence in (0,2)";
	 $params1 = array();
	   $sql1.=" AND EXISTS(SELECT * FROM ".$table_prefix."_profile2field WHERE ".$table_prefix."_profile2field.fieldid = ".$table_prefix."_field.fieldid ";
	            if (count($profileList) > 0) {
	  	 $sql1.=" AND ".$table_prefix."_profile2field.profileid IN (". generateQuestionMarks($profileList) .") ";
	  	 array_push($params1, $profileList);
	  }
	   $sql1.=" AND ".$table_prefix."_profile2field.visible = 0) ";
  }
	$result = $this->db->pquery($sql1, $params1);
	$numRows = $this->db->num_rows($result);
	for($i=0; $i < $numRows;$i++)
	{
	$custom_fields[$i] = $this->db->query_result($result,$i,"fieldlabel");
	$custom_fields[$i] = str_replace(" ","",$custom_fields[$i]);
	$custom_fields[$i] = strtoupper($custom_fields[$i]);
	}
	$mergeflds = $custom_fields;
	$log->debug("Exiting getColumnNames method ...");
	return $mergeflds;
}
//End
/** Function to get the Contacts assigned to a user with a valid email address.
* @param varchar $username - User Name
* @param varchar $emailaddress - Email Addr for each contact.
* Used By vteCRM Outlook Plugin
* Returns the Query
*/
function get_searchbyemailid($username,$emailaddress)
{
	global $log;
	global $current_user;
	global $table_prefix;
	$seed_user= CRMEntity::getInstance('Users');
	$user_id=$seed_user->retrieve_user_id($username);
	$current_user=$seed_user;
	$current_user->retrieve_entity_info($user_id, 'Users');
	require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
	$log->debug("Entering get_searchbyemailid(".$username.",".$emailaddress.") method ...");
	$query = "select ".$table_prefix."_contactdetails.lastname,".$table_prefix."_contactdetails.firstname,
					".$table_prefix."_contactdetails.contactid, ".$table_prefix."_contactdetails.salutation,
					".$table_prefix."_contactdetails.email,".$table_prefix."_contactdetails.title,
					".$table_prefix."_contactdetails.mobile,".$table_prefix."_account.accountname,
					".$table_prefix."_account.accountid as accountid  from ".$table_prefix."_contactdetails
						inner join ".$table_prefix."_contactscf on ".$table_prefix."_contactscf.contactid = ".$table_prefix."_contactdetails.contactid
						inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_contactdetails.contactid
						inner join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_crmentity.smownerid
						left join ".$table_prefix."_account on ".$table_prefix."_account.accountid=".$table_prefix."_contactdetails.accountid
						left join ".$table_prefix."_contactaddress on ".$table_prefix."_contactaddress.contactaddressid=".$table_prefix."_contactdetails.contactid
			      LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid";
	$query .= getNonAdminAccessControlQuery('Contacts',$current_user);
	$query .= "where ".$table_prefix."_crmentity.deleted=0";
	if(trim($emailaddress) != '') {
		$query .= " and ((".$table_prefix."_contactdetails.email like '". formatForSqlLike($emailaddress) .
		"') or ".$table_prefix."_contactdetails.lastname REGEXP REPLACE('".$emailaddress.
		"',' ','|') or ".$table_prefix."_contactdetails.firstname REGEXP REPLACE('".$emailaddress.
		"',' ','|'))  and ".$table_prefix."_contactdetails.email != ''";
	} else {
		$query .= " and (".$table_prefix."_contactdetails.email like '". formatForSqlLike($emailaddress) .
		"' and ".$table_prefix."_contactdetails.email != '')";
	}
	$query = $this->listQueryNonAdminChange($query, 'Contacts');
	$log->debug("Exiting get_searchbyemailid method ...");
	return $this->plugin_process_list_query($query);
}

/** Function to get the Contacts associated with the particular User Name.
*  @param varchar $user_name - User Name
*  Returns query
*/

function get_contactsforol($user_name)
{
	global $log,$adb;
	global $current_user;
	global $table_prefix;
	$seed_user=CRMEntity::getInstance('Users');
	$user_id=$seed_user->retrieve_user_id($user_name);
	$current_user=$seed_user;
	$current_user->retrieve_entity_info($user_id, 'Users');
	require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	require('user_privileges/sharing_privileges_'.$current_user->id.'.php');

	if($is_admin == true || $profileGlobalPermission[1] == 0 || $profileGlobalPermission[2] == 0)
  {
    $sql1 = "select tablename,columnname from ".$table_prefix."_field where tabid=4 and ".$table_prefix."_field.presence in (0,2)";
	$params1 = array();
  }else
  {
    $profileList = getCurrentUserProfileList();
    $sql1 = "select tablename,columnname from ".$table_prefix."_field inner join ".$table_prefix."_def_org_field on ".$table_prefix."_def_org_field.fieldid=".$table_prefix."_field.fieldid where ".$table_prefix."_field.tabid=4 and ".$table_prefix."_field.displaytype in (1,2,4,3) and ".$table_prefix."_def_org_field.visible=0 and ".$table_prefix."_field.presence in (0,2)";
    $params1 = array();
	   $sql1.=" AND EXISTS(SELECT * FROM ".$table_prefix."_profile2field WHERE ".$table_prefix."_profile2field.fieldid = ".$table_prefix."_field.fieldid ";
	            if (count($profileList) > 0) {
	  	 $sql1.=" AND ".$table_prefix."_profile2field.profileid IN (". generateQuestionMarks($profileList) .") ";
	  	 array_push($params1, $profileList);
	  }
	   $sql1.=" AND ".$table_prefix."_profile2field.visible = 0) ";
  }
  $result1 = $adb->pquery($sql1, $params1);
  for($i=0;$i < $adb->num_rows($result1);$i++)
  {
      $permitted_lists[] = $adb->query_result($result1,$i,'tablename');
      $permitted_lists[] = $adb->query_result($result1,$i,'columnname');
      if($adb->query_result($result1,$i,'columnname') == "accountid")
      {
        $permitted_lists[] = $table_prefix.'_account';
        $permitted_lists[] = 'accountname';
      }
  }
	$permitted_lists = array_chunk($permitted_lists,2);
	$column_table_lists = array();
	for($i=0;$i < count($permitted_lists);$i++)
	{
	   $column_table_lists[] = implode(".",$permitted_lists[$i]);
  }

	$log->debug("Entering get_contactsforol(".$user_name.") method ...");
	$query = "select ".$table_prefix."_contactdetails.contactid as id, ".implode(',',$column_table_lists)." from ".$table_prefix."_contactdetails
					inner join ".$table_prefix."_contactscf on ".$table_prefix."_contactscf.contactid = ".$table_prefix."_contactdetails.contactid
					inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_contactdetails.contactid
					inner join ".$table_prefix."_users on ".$table_prefix."_users.id=".$table_prefix."_crmentity.smownerid
					INNER JOIN ".$table_prefix."_contactscf ON ".$table_prefix."_contactscf.contactid=".$table_prefix."_contactdetails.contactid
					left join ".$table_prefix."_customerdetails on ".$table_prefix."_customerdetails.customerid=".$table_prefix."_contactdetails.contactid
					left join ".$table_prefix."_account on ".$table_prefix."_account.accountid=".$table_prefix."_contactdetails.accountid
					left join ".$table_prefix."_contactaddress on ".$table_prefix."_contactaddress.contactaddressid=".$table_prefix."_contactdetails.contactid
					left join ".$table_prefix."_contactsubdetails on ".$table_prefix."_contactsubdetails.contactsubscriptionid = ".$table_prefix."_contactdetails.contactid
                    left join ".$table_prefix."_campaigncontrel on ".$table_prefix."_contactdetails.contactid = ".$table_prefix."_campaigncontrel.contactid
                    left join ".$table_prefix."_campaignrelstatus on ".$table_prefix."_campaignrelstatus.campaignrelstatusid = ".$table_prefix."_campaigncontrel.campaignrelstatusid
			      LEFT JOIN ".$table_prefix."_groups ON ".$table_prefix."_groups.groupid = ".$table_prefix."_crmentity.smownerid
 				where ".$table_prefix."_crmentity.deleted=0 and ".$table_prefix."_users.user_name='".$user_name."'";
  $log->debug("Exiting get_contactsforol method ...");
	return $query;
}


	/** Function to handle module specific operations when saving a entity
	*/
	function save_module($module)
	{
		$this->insertIntoAttachment($this->id,$module);
		$this->syncPortalInfo(); // crmv@137993
	}
	
	// crmv@137993
	/**
	 * Syncronizes the informations for the customer portal and send the email to the contact
	 */
	function syncPortalInfo() {
		global $adb, $table_prefix;
		
		$sendMail = false;
		$portal = $this->column_fields['portal'];
		$email = $this->column_fields['email'];
		
		$portalActive = ($portal == 1 || $portal == 'on');
		
		if ($portalActive && !empty($email)) {
			// portal is active
			//crmv@157490
			require_once('include/utils/encryption.php');
			$encryption = new Encryption();
			//crmv@157490e
			// check if entry exists
			$res = $adb->pquery("SELECT user_name, user_password, isactive FROM {$table_prefix}_portalinfo WHERE id = ?", array($this->id));
			if ($res && $adb->num_rows($res) > 0) {
				// should be updated
				$wasActive = $adb->query_result_no_html($res, 0, 'isactive');
				$oldEmail = $adb->query_result_no_html($res, 0, 'user_name');
				$password = $encryption->decrypt($adb->query_result_no_html($res, 0, 'user_password'));	//crmv@157490
				// update the table
				$sql = "UPDATE {$table_prefix}_portalinfo set user_name = ?, isactive = 1 where id=?";
				$adb->pquery($sql, array($email, $this->id));
				// now check if I should send the email (activated or changed the address)
				$sendMail = ($wasActive == 0 || $oldEmail != $email);
			} else {
				// should be inserted
				$password = makeRandomPassword();
				// crmv@195054
				if ($adb->isMssql()) {
					$params = array($this->id, $email, $encryption->encrypt($password), 'C', NULL, NULL, NULL, 1);
				} else {
					$params = array($this->id, $email, $encryption->encrypt($password), 'C', '0000-00-00 00:00:00', '0000-00-00 00:00:00', '0000-00-00 00:00:00', 1);	//crmv@157490
				}
				// crmv@195054e
				$sql = "INSERT INTO {$table_prefix}_portalinfo (id, user_name, user_password, type, last_login_time, login_time, logout_time, isactive) VALUES (".generateQuestionMarks($params).")";
				$adb->pquery($sql, $params);
				$sendMail = true;
			}
		} else {
			// portal is not active, disable if present
			$sql = "UPDATE {$table_prefix}_portalinfo SET user_name = ?, isactive = 0 WHERE id = ?";
			$adb->pquery($sql, array($email, $this->id));
		}
		
		if ($sendMail) {
			$this->sendPortalEmail($email, $email, $password);
		}
	}
	
	function sendPortalEmail($email, $username, $password) {
		global $PORTAL_URL, $current_user;
		
		$data_array = Array();
		$data_array['first_name'] = $this->column_fields['firstname'];
		$data_array['last_name'] = $this->column_fields['lastname'];
		$data_array['email'] = $username;
		$data_array['portal_url'] = '<a href="'.$PORTAL_URL.'" style="font-family:Arial, Helvetica, sans-serif;font-size:12px; font-weight:bolder;text-decoration:none;color: #4242FD;">'.getTranslatedString('Please Login Here', 'Contacts').'</a>';
	
		$value = getmail_contents_portalUser($data_array,$password,"LoginDetails");
		$contents = $value["body"];
		$subject = $value["subject"];

		$mail_status = send_mail('Support',$email,$current_user->user_name,'',$subject,$contents);
		return $mail_status;
	}
	// crmv@137993e
	
	//crmv@157490
	function encryptAllPortalPasswords() {
		global $adb, $table_prefix;
		require_once('include/utils/encryption.php');
		$encryption = new Encryption();
		$result = $adb->query("select id, user_password from {$table_prefix}_portalinfo");
		if ($result && $adb->num_rows($result) > 0) {
			while($row=$adb->fetchByAssoc($result)) {
				if (!empty($row['user_password'])) {
					$user_password= $encryption->encrypt($row['user_password']);
					$adb->pquery("update {$table_prefix}_portalinfo set user_password = ? where id = ?", array($user_password, $row['id']));
				}
			}
		}
	}
	function checkPortalDuplicates($email, $record='') {
		global $adb, $table_prefix;
		$query = "select contactid from {$table_prefix}_contactdetails
			inner join {$table_prefix}_crmentity on crmid = contactid
			inner join {$table_prefix}_customerdetails on customerid = contactid
			where deleted = 0 and portal = 1 and email = ?";
		$params = array($email);
		if (!empty($record)) {
			$query .= ' and contactid <> ?';
			$params[] = $record;
		}
		$result = $adb->pquery($query,$params);
		return ($adb->num_rows($result) > 0);
	}
	//crmv@157490e

	/**
	 *      This function is used to add the vte_attachments. This will call the function uploadAndSaveFile which will upload the attachment into the server and save that attachment information in the database.
	 *      @param int $id  - entity id to which the vte_files to be uploaded
	 *      @param string $module  - the current module name
	*/
	function insertIntoAttachment($id,$module)
	{
		global $log, $adb;
		global $table_prefix;
		$log->debug("Entering into insertIntoAttachment($id,$module) method.");

		$file_saved = false;
		//This is to added to store the existing attachment id of the contact where we should delete this when we give new image
		$old_attachmentid = $adb->query_result($adb->pquery("select ".$table_prefix."_crmentity.crmid from ".$table_prefix."_seattachmentsrel inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid=".$table_prefix."_seattachmentsrel.attachmentsid where  ".$table_prefix."_seattachmentsrel.crmid=?", array($id)),0,'crmid');
		foreach($_FILES as $fileindex => $files)
		{
			if($files['name'] != '' && $files['size'] > 0)
			{
				$files['original_name'] = vtlib_purify($_REQUEST[$fileindex.'_hidden']);
				$file_saved = $this->uploadAndSaveFile($id,$module,$files);
			}
		}

		//This is to handle the delete image for contacts
		if($module == 'Contacts' && $file_saved)
		{
			if($old_attachmentid != '')
			{
				$setype =  getSalesEntityType($old_attachmentid); //crmv@171021
				if($setype == 'Contacts Image')
				{
					$del_res1 = $adb->pquery("delete from ".$table_prefix."_attachments where attachmentsid=?", array($old_attachmentid));
					$del_res2 = $adb->pquery("delete from ".$table_prefix."_seattachmentsrel where attachmentsid=?", array($old_attachmentid));
				}
			}
		}

		$log->debug("Exiting from insertIntoAttachment($id,$module) method.");
	}

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

		$rel_table_arr = Array("Potentials"=>$table_prefix."_contpotentialrel","Activities"=>$table_prefix."_cntactivityrel","Emails"=>$table_prefix."_seactivityrel",
				"HelpDesk"=>$table_prefix."_troubletickets","Quotes"=>$table_prefix."_quotes","PurchaseOrder"=>$table_prefix."_purchaseorder",
				"SalesOrder"=>$table_prefix."_salesorder","Products"=>$table_prefix."_seproductsrel","Documents"=>$table_prefix."_senotesrel",
				"Attachments"=>$table_prefix."_seattachmentsrel","Campaigns"=>$table_prefix."_campaigncontrel");

		$tbl_field_arr = Array($table_prefix."_contpotentialrel"=>"potentialid",$table_prefix."_cntactivityrel"=>"activityid",$table_prefix."_seactivityrel"=>"activityid",
				$table_prefix."_troubletickets"=>"ticketid",$table_prefix."_quotes"=>"quoteid",$table_prefix."_purchaseorder"=>"purchaseorderid",
				$table_prefix."_salesorder"=>"salesorderid",$table_prefix."_seproductsrel"=>"productid",$table_prefix."_senotesrel"=>"notesid",
				$table_prefix."_seattachmentsrel"=>"attachmentsid",$table_prefix."_campaigncontrel"=>"campaignid");

		$entity_tbl_field_arr = Array($table_prefix."_contpotentialrel"=>"contactid",$table_prefix."_cntactivityrel"=>"contactid",$table_prefix."_seactivityrel"=>"crmid",
				$table_prefix."_troubletickets"=>"parent_id",$table_prefix."_quotes"=>"contactid",$table_prefix."_purchaseorder"=>"contactid",
				$table_prefix."_salesorder"=>"contactid",$table_prefix."_seproductsrel"=>"crmid",$table_prefix."_senotesrel"=>"crmid",
				$table_prefix."_seattachmentsrel"=>"crmid",$table_prefix."_campaigncontrel"=>"contactid");

		// crmv@158080
		if(isModuleInstalled('Corsi') && vtlib_isModuleActive("Corsi")) {
			$rel_table_arr["Corsi"] = $table_prefix."_corsicontrel";
			$tbl_field_arr[$table_prefix."_corsicontrel"] = "corsiid";
			$entity_tbl_field_arr[$table_prefix."_corsicontrel"] = "contactid";
		}
		// crmv@158080e
		
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
		$query = $this->getRelationQuery($module,$secmodule,$table_prefix."_contactdetails","contactid");
		$query .= " left join ".$table_prefix."_contactdetails ".$table_prefix."_contactdetailsContacts on ".$table_prefix."_contactdetailsContacts.contactid = ".$table_prefix."_contactdetails.reportsto
			left join ".$table_prefix."_contactaddress on ".$table_prefix."_contactdetails.contactid = ".$table_prefix."_contactaddress.contactaddressid
			left join ".$table_prefix."_customerdetails on ".$table_prefix."_customerdetails.customerid = ".$table_prefix."_contactdetails.contactid
			left join ".$table_prefix."_contactsubdetails on ".$table_prefix."_contactdetails.contactid = ".$table_prefix."_contactsubdetails.contactsubscriptionid
			left join ".$table_prefix."_account ".$table_prefix."_accountContacts on ".$table_prefix."_accountContacts.accountid = ".$table_prefix."_contactdetails.accountid
			left join ".$table_prefix."_contactscf on ".$table_prefix."_contactdetails.contactid = ".$table_prefix."_contactscf.contactid
			left join ".$table_prefix."_groups ".$table_prefix."_groupsContacts on ".$table_prefix."_groupsContacts.groupid = ".$table_prefix."_crmentityContacts.smownerid
			left join ".$table_prefix."_users ".$table_prefix."_usersContacts on ".$table_prefix."_usersContacts.id = ".$table_prefix."_crmentityContacts.smownerid ";

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
			"Calendar" => array($table_prefix."_cntactivityrel"=>array("contactid","activityid"),$table_prefix."_contactdetails"=>"contactid"),
			"HelpDesk" => array($table_prefix."_troubletickets"=>array("parent_id","ticketid"),$table_prefix."_contactdetails"=>"contactid"),
			"Quotes" => array($table_prefix."_quotes"=>array("contactid","quoteid"),$table_prefix."_contactdetails"=>"contactid"),
			"PurchaseOrder" => array($table_prefix."_purchaseorder"=>array("contactid","purchaseorderid"),$table_prefix."_contactdetails"=>"contactid"),
			"SalesOrder" => array($table_prefix."_salesorder"=>array("contactid","salesorderid"),$table_prefix."_contactdetails"=>"contactid"),
			"Products" => array($table_prefix."_seproductsrel"=>array("crmid","productid"),$table_prefix."_contactdetails"=>"contactid"),
			"Campaigns" => array($table_prefix."_campaigncontrel"=>array("contactid","campaignid"),$table_prefix."_contactdetails"=>"contactid"),
			"Documents" => array($table_prefix."_senotesrel"=>array("crmid","notesid"),$table_prefix."_contactdetails"=>"contactid"),
			"Accounts" => array($table_prefix."_contactdetails"=>array("contactid","accountid")),
			"Invoice" => array($table_prefix."_invoice"=>array("contactid","invoiceid"),$table_prefix."_contactdetails"=>"contactid"),
		);
		return $rel_tables[$secmodule];
	}

	// Function to unlink all the dependent entities of the given Entity by Id
	function unlinkDependencies($module, $id) {
		global $log;
		global $table_prefix;
		//Deleting Contact related Potentials.
		$pot_q = 'SELECT '.$table_prefix.'_crmentity.crmid FROM '.$table_prefix.'_crmentity
			INNER JOIN '.$table_prefix.'_potential ON '.$table_prefix.'_crmentity.crmid='.$table_prefix.'_potential.potentialid
			INNER JOIN '.$table_prefix.'_potentialscf ON '.$table_prefix.'_potentialscf.potentialid = '.$table_prefix.'_potential.potentialid
			LEFT JOIN '.$table_prefix.'_account ON '.$table_prefix.'_account.accountid='.$table_prefix.'_potential.related_to
			WHERE '.$table_prefix.'_crmentity.deleted=0 AND '.$table_prefix.'_potential.related_to=?';
		$pot_res = $this->db->pquery($pot_q, array($id));
		$pot_ids_list = array();
		for($k=0;$k < $this->db->num_rows($pot_res);$k++)
		{
			$pot_id = $this->db->query_result($pot_res,$k,"crmid");
			$pot_ids_list[] = $pot_id;
			$sql = 'UPDATE '.$table_prefix.'_crmentity SET deleted = 1 WHERE crmid = ?';
			$this->db->pquery($sql, array($pot_id));
		}
		//Backup deleted Contact related Potentials.
		$params = array($id, RB_RECORD_UPDATED, $table_prefix.'_crmentity', 'deleted', 'crmid', implode(",", $pot_ids_list));
		$this->db->pquery('INSERT INTO '.$table_prefix.'_relatedlists_rb VALUES(?,?,?,?,?,?)', $params);

		//Backup Contact-Trouble Tickets Relation
		$tkt_q = 'SELECT ticketid FROM '.$table_prefix.'_troubletickets WHERE parent_id=?';
		$tkt_res = $this->db->pquery($tkt_q, array($id));
		if ($this->db->num_rows($tkt_res) > 0) {
			$tkt_ids_list = array();
			for($k=0;$k < $this->db->num_rows($tkt_res);$k++)
			{
				$tkt_ids_list[] = $this->db->query_result($tkt_res,$k,"ticketid");
			}
			$params = array($id, RB_RECORD_UPDATED, $table_prefix.'_troubletickets', 'parent_id', 'ticketid', implode(",", $tkt_ids_list));
			$this->db->pquery('INSERT INTO '.$table_prefix.'_relatedlists_rb VALUES (?,?,?,?,?,?)', $params);
		}
		//removing the relationship of contacts with Trouble Tickets
		$this->db->pquery('UPDATE '.$table_prefix.'_troubletickets SET parent_id=0 WHERE parent_id=?', array($id));

		//Backup Contact-PurchaseOrder Relation
		$po_q = 'SELECT purchaseorderid FROM '.$table_prefix.'_purchaseorder WHERE contactid=?';
		$po_res = $this->db->pquery($po_q, array($id));
		if ($this->db->num_rows($po_res) > 0) {
			$po_ids_list = array();
			for($k=0;$k < $this->db->num_rows($po_res);$k++)
			{
				$po_ids_list[] = $this->db->query_result($po_res,$k,"purchaseorderid");
			}
			$params = array($id, RB_RECORD_UPDATED, $table_prefix.'_purchaseorder', 'contactid', 'purchaseorderid', implode(",", $po_ids_list));
			$this->db->pquery('INSERT INTO '.$table_prefix.'_relatedlists_rb VALUES (?,?,?,?,?,?)', $params);
		}
		//removing the relationship of contacts with PurchaseOrder
		$this->db->pquery('UPDATE '.$table_prefix.'_purchaseorder SET contactid=0 WHERE contactid=?', array($id));

		//Backup Contact-SalesOrder Relation
		$so_q = 'SELECT salesorderid FROM '.$table_prefix.'_salesorder WHERE contactid=?';
		$so_res = $this->db->pquery($so_q, array($id));
		if ($this->db->num_rows($so_res) > 0) {
			$so_ids_list = array();
			for($k=0;$k < $this->db->num_rows($so_res);$k++)
			{
				$so_ids_list[] = $this->db->query_result($so_res,$k,"salesorderid");
			}
			$params = array($id, RB_RECORD_UPDATED, $table_prefix.'_salesorder', 'contactid', 'salesorderid', implode(",", $so_ids_list));
			$this->db->pquery('INSERT INTO '.$table_prefix.'_relatedlists_rb VALUES (?,?,?,?,?,?)', $params);
		}
		//removing the relationship of contacts with SalesOrder
		$this->db->pquery('UPDATE '.$table_prefix.'_salesorder SET contactid=0 WHERE contactid=?', array($id));

		//Backup Contact-Quotes Relation
		$quo_q = 'SELECT quoteid FROM '.$table_prefix.'_quotes WHERE contactid=?';
		$quo_res = $this->db->pquery($quo_q, array($id));
		if ($this->db->num_rows($quo_res) > 0) {
			$quo_ids_list = array();
			for($k=0;$k < $this->db->num_rows($quo_res);$k++)
			{
				$quo_ids_list[] = $this->db->query_result($quo_res,$k,"quoteid");
			}
			$params = array($id, RB_RECORD_UPDATED, $table_prefix.'_quotes', 'contactid', 'quoteid', implode(",", $quo_ids_list));
			$this->db->pquery('INSERT INTO '.$table_prefix.'_relatedlists_rb VALUES (?,?,?,?,?,?)', $params);
		}
		//removing the relationship of contacts with Quotes
		$this->db->pquery('UPDATE '.$table_prefix.'_quotes SET contactid=0 WHERE contactid=?', array($id));
		//remove the portal info the contact
		$this->db->pquery('DELETE FROM '.$table_prefix.'_portalinfo WHERE id = ?', array($id));
		$this->db->pquery('UPDATE '.$table_prefix.'_customerdetails SET portal=0,support_start_date=NULL,support_end_date=NULl WHERE customerid=?', array($id));
		parent::unlinkDependencies($module, $id);
	}

	// Function to unlink an entity with given Id from another entity
	function unlinkRelationship($id, $return_module, $return_id) {
		global $log;
		global $table_prefix;
		if(empty($return_module) || empty($return_id)) return;

		if($return_module == 'Accounts') {
			$sql = 'UPDATE '.$table_prefix.'_contactdetails SET accountid = 0 WHERE contactid = ?';
			$this->db->pquery($sql, array($id));
		} elseif($return_module == 'Potentials') {
			$sql = 'DELETE FROM '.$table_prefix.'_contpotentialrel WHERE contactid=? AND potentialid=?';
			$this->db->pquery($sql, array($id, $return_id));
		} elseif($return_module == 'Campaigns') {
			$sql = 'DELETE FROM '.$table_prefix.'_campaigncontrel WHERE contactid=? AND campaignid=?';
			$this->db->pquery($sql, array($id, $return_id));
		} elseif($return_module == 'Products') {
			$sql = 'DELETE FROM '.$table_prefix.'_seproductsrel WHERE crmid=? AND productid=?';
			$this->db->pquery($sql, array($id, $return_id));
		} elseif($return_module == 'Vendors') {
			$sql = 'DELETE FROM '.$table_prefix.'_vendorcontactrel WHERE vendorid=? AND contactid=?';
			$this->db->pquery($sql, array($return_id, $id));
		//crmv@28583
		} elseif($return_module == 'Calendar') {
			$sql = 'DELETE FROM '.$table_prefix.'_cntactivityrel WHERE activityid=? AND contactid=?';
			$this->db->pquery($sql, array($return_id, $id));
		//crmv@28583e
		} else {
			$sql = 'DELETE FROM '.$table_prefix.'_crmentityrel WHERE (crmid=? AND relmodule=? AND relcrmid=?) OR (relcrmid=? AND module=? AND crmid=?)';
			$params = array($id, $return_module, $return_id, $id, $return_module, $return_id);
			$this->db->pquery($sql, $params);
		}
		$this->db->pquery("UPDATE {$table_prefix}_crmentity SET modifiedtime = ? WHERE crmid IN (?,?)", array($this->db->formatDate(date('Y-m-d H:i:s'), true), $id, $return_id)); // crmv@49398 crmv@69690
	}

//End

	//crmv@22700
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

		global $adb, $onlybutton;
		$onlyquery_bck = $onlyquery; $onlyquery = true;
		$onlybutton_bck = $onlybutton; $onlybutton = false;
		$targetsModule = Vtecrm_Module::getInstance('Targets');
		CRMEntity::get_related_list($id, $cur_tab_id, $targetsModule->id);
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
				CRMEntity::get_related_list($row['crmid'], $targetsModule->id, 26);
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
			$query = "SELECT case when (".$table_prefix."_users.user_name is not null) then ".$table_prefix."_users.user_name else ".$table_prefix."_groups.groupname end as user_name,
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
	//crmv@22700e
	
	/* crmv@181281 moved code in CRMEntity and ExportUtils */
}
?>
