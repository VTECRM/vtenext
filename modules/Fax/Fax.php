<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@152701 */

// Fax is used to store customer information.
class Fax extends CRMEntity {
	var $log;
	var $db;
	var $table_name;
	var $table_index= 'faxid';	//crmv@24834

	var $tab_name = Array();
    var $tab_name_index = Array();

	// This is the list of vte_fields that are in the lists.
        var $list_fields = Array(
				       'Subject'=>Array('fax'=>'subject'),
				       //'Related to'=>Array('seactivityrel'=>'parent_id'),
        				 'Document'=>Array('fax'=>'filename'),
				       'Date Sent'=>Array('fax'=>'date_start'),
				       'Assigned To'=>Array('crmentity','smownerid')
			        );

       var $list_fields_name = Array(
				       'Subject'=>'subject',
				       //'Related to'=>'parent_id',
       				 'Document'=>'filename',
				       'Date Sent'=>'date_start',
				       'Assigned To'=>'assigned_user_id'
				    );

       var $list_link_field= 'subject';

	var $column_fields = Array();

	var $sortby_fields = Array('subject','date_start','smownerid');

	//Added these variables which are used as default order by and sortorder in ListView
	var $default_order_by = 'date_start';
	var $default_sort_order = 'ASC';

	/** This function will set the columnfields for Email module
	*/

	function __construct() {
		global $table_prefix;
		parent::__construct(); // crmv@37004

		$this->table_name = $table_prefix."_fax";
		$this->tab_name = Array($table_prefix.'_crmentity',$table_prefix.'_fax');
        $this->tab_name_index = Array($table_prefix.'_crmentity'=>'crmid',$table_prefix.'_fax'=>'faxid');
		
		$this->log = LoggerManager::getLogger('fax');
		$this->db = new PearDatabase();
		$this->column_fields = getColumnFields('Fax');
	}


	function save_module($module) {
		global $adb, $table_prefix, $current_user;

		if ($this->id && $this->parent_id && $this->parent_type) {

			$ids = array();
			$myids = explode("|", $this->parent_id);  //2@71|
			foreach ($myids as $fullid)	{
				$realid = explode("@", $fullid);
				if ($realid[1] > 0) {
					$ids[] = $realid[0];
				}
			}
			if (count($ids) > 0) {
				$this->save_related_module('Fax', $this->id, $this->parent_type, $ids, true);
			}
			
			//Inserting into attachment
			$this->insertIntoAttachment($this->id,$module);
				
			//saving the fax details in vte_faxdetails vte_table
			$qry = 'select phone_fax from '.$table_prefix.'_users where id = ?';
			$res = $adb->pquery($qry, array($current_user->id));
			$user_fax = $adb->query_result_no_html($res,0,"phone_fax");
			
			if(isset($_REQUEST["hidden_toid"]) && $_REQUEST["hidden_toid"]!='')
				$all_to_ids = str_replace(",","###",$_REQUEST["hidden_toid"]);
			if(isset($_REQUEST["saved_toid"]) && $_REQUEST["saved_toid"]!='')
				$all_to_ids .= str_replace(",","###",$_REQUEST["saved_toid"]);


			//added to save < as $lt; and > as &gt; in the database so as to retrive the faxID
			$all_to_ids = str_replace('<','&lt;',$all_to_ids);
			$all_to_ids = str_replace('>','&gt;',$all_to_ids);

			$query = 'select faxid from '.$table_prefix.'_faxdetails where faxid = ?';
			$result = $adb->pquery($query, array($this->id));
			if ($adb->num_rows($result) > 0) {
				$query = 'update '.$table_prefix.'_faxdetails set to_number=?, idlists=?, fax_flag=\'SAVED\' where faxid = ?';
				$qparams = array($all_to_ids, $_REQUEST["parent_id"], $this->id);
			} else {
				$query = 'insert into '.$table_prefix.'_faxdetails values (?,?,?,\'\',?,\'SAVED\')';
				$qparams = array($this->id, $user_fax, $all_to_ids, $_REQUEST["parent_id"]);
			}
			$adb->pquery($query, $qparams);
		
		}

	}


	function insertIntoAttachment($id,$module)
	{
		global $log, $adb;
		global $table_prefix;
		$log->debug("Entering into insertIntoAttachment($id,$module) method.");

		$file_saved = false;

		//Added to send generated Invoice PDF with mail
		$pdfAttached = $_REQUEST['pdf_attachment'];
		//created Invoice pdf is attached with the mail
			if(isset($_REQUEST['pdf_attachment']) && $_REQUEST['pdf_attachment'] !='')
			{
				$file_saved = pdfAttachFax($this,$module,$pdfAttached,$id);
			}

		//This is to added to store the existing attachment id of the contact where we should delete this when we give new image
		foreach($_FILES as $fileindex => $files)
		{
			if($files['name'] != '' && $files['size'] > 0)
			{
				$files['original_name'] = $_REQUEST[$fileindex.'_hidden'];
				$file_saved = $this->uploadAndSaveFile($id,$module,$files);
			}
		}
		if($module == 'Emails' && isset($_REQUEST['att_id_list']) && $_REQUEST['att_id_list'] != '')
		{
			$att_lists = explode(";",$_REQUEST['att_id_list'],-1);
			$id_cnt = count($att_lists);
			if($id_cnt != 0)
			{
				for($i=0;$i<$id_cnt;$i++)
				{
					$sql_rel='insert into '.$table_prefix.'_seattachmentsrel values(?,?)';
		                        $adb->pquery($sql_rel, array($id, $att_lists[$i]));
				}
			}
		}
		$log->debug("Exiting from insertIntoAttachment($id,$module) method.");
	}

	/**
	  * Returns a list of the associated vte_attachments and vte_notes of the Email
	  */
	function get_attachments($id, $cur_tab_id, $rel_tab_id, $actions=false) // crmv@146653
	{
		global $log,$adb;
		global $table_prefix;
		$log->debug("Entering get_attachments(".$id.") method ...");
		$query = "select ".$table_prefix."_notes.title,'Documents      '  ActivityType, ".$table_prefix."_notes.filename,
		".$table_prefix."_attachments.type  FileType,crm2.modifiedtime lastmodified,
		".$table_prefix."_seattachmentsrel.attachmentsid attachmentsid, ".$table_prefix."_notes.notesid crmid,
		".$table_prefix."_notes.notecontent description, ".$table_prefix."_users.user_name
		from ".$table_prefix."_notes
			inner join ".$table_prefix."_notescf on ".$table_prefix."_notescf.notesid = ".$table_prefix."_notes.notesid
			inner join ".$table_prefix."_senotesrel on ".$table_prefix."_senotesrel.notesid= ".$table_prefix."_notes.notesid
			inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid= ".$table_prefix."_senotesrel.crmid
			inner join ".$table_prefix."_crmentity crm2 on crm2.crmid=".$table_prefix."_notes.notesid and crm2.deleted=0
			left join ".$table_prefix."_seattachmentsrel  on ".$table_prefix."_seattachmentsrel.crmid =".$table_prefix."_notes.notesid
			left join ".$table_prefix."_attachments on ".$table_prefix."_seattachmentsrel.attachmentsid = ".$table_prefix."_attachments.attachmentsid
			inner join ".$table_prefix."_users on crm2.smcreatorid= ".$table_prefix."_users.id
		where ".$table_prefix."_crmentity.crmid=".$adb->quote($id);
		$query .= ' union all ';
		$query .= "select ".$table_prefix."_attachments.description title ,'Attachments'  ActivityType,
		".$table_prefix."_attachments.name filename, ".$table_prefix."_attachments.type FileType,crm2.modifiedtime lastmodified,
		".$table_prefix."_attachments.attachmentsid  attachmentsid,".$table_prefix."_seattachmentsrel.attachmentsid crmid,
		".$table_prefix."_attachments.description, ".$table_prefix."_users.user_name
		from ".$table_prefix."_attachments
			inner join ".$table_prefix."_seattachmentsrel on ".$table_prefix."_seattachmentsrel.attachmentsid= ".$table_prefix."_attachments.attachmentsid
			inner join ".$table_prefix."_crmentity on ".$table_prefix."_crmentity.crmid= ".$table_prefix."_seattachmentsrel.crmid
			inner join ".$table_prefix."_crmentity crm2 on crm2.crmid=".$table_prefix."_attachments.attachmentsid
			inner join ".$table_prefix."_users on crm2.smcreatorid= ".$table_prefix."_users.id
		where ".$table_prefix."_crmentity.crmid=".$adb->quote($id);

		$log->info("Documents&Attachments Related List for Email is Displayed");
		$log->debug("Exiting get_attachments method ...");
		return getAttachmentsAndNotes('Emails',$query,$id);
	}

	/**
	* Invoked when special actions are performed on the module.
	* @param String Module name
	* @param String Event Type
	*/
	function vtlib_handler($moduleName, $eventType) {

		global $adb, $table_prefix;
 		
 		if($eventType == 'module.postinstall') {
			require_once('vtlib/Vtecrm/Module.php');//crmv@207871

			$moduleInstance = Vtecrm_Module::getInstance($moduleName);
			
			$linkCondition = 'checkFaxWidgetPermission:modules/Fax/widgets/CheckWidgetPermission.php'; // crmv@180638

			$accModuleInstance = Vtecrm_Module::getInstance('Accounts');
			$accModuleInstance->setRelatedList($moduleInstance,'Fax',array('add'),'get_faxes');
			Vtecrm_Link::addLink($accModuleInstance->id, 'DETAILVIEWBASIC', 'TITLE_COMPOSE_FAX', "javascript:fnvshobj(this,'sendfax_cont');sendfax('\$MODULE\$','\$RECORD\$');", '', 1, $linkCondition); // crmv@180638

			$accModuleInstance = Vtecrm_Module::getInstance('Contacts');
			$accModuleInstance->setRelatedList($moduleInstance,'Fax',array('add'),'get_faxes');
			Vtecrm_Link::addLink($accModuleInstance->id, 'DETAILVIEWBASIC', 'TITLE_COMPOSE_FAX', "javascript:fnvshobj(this,'sendfax_cont');sendfax('\$MODULE\$','\$RECORD\$');", '', 1, $linkCondition); // crmv@180638

			$accModuleInstance = Vtecrm_Module::getInstance('Leads');
			$accModuleInstance->setRelatedList($moduleInstance,'Fax',array('add'),'get_faxes');
			Vtecrm_Link::addLink($accModuleInstance->id, 'DETAILVIEWBASIC', 'TITLE_COMPOSE_FAX', "javascript:fnvshobj(this,'sendfax_cont');sendfax('\$MODULE\$','\$RECORD\$');", '', 1, $linkCondition); // crmv@180638

			$accModuleInstance = Vtecrm_Module::getInstance('Vendors');
			$accModuleInstance->setRelatedList($moduleInstance,'Fax',array('add'),'get_faxes');
			Vtecrm_Link::addLink($accModuleInstance->id, 'DETAILVIEWBASIC', 'TITLE_COMPOSE_FAX', "javascript:fnvshobj(this,'sendfax_cont');sendfax('\$MODULE\$','\$RECORD\$');", '', 1, $linkCondition); // crmv@180638

			// Mark the module as Standard module
			$adb->pquery('UPDATE '.$table_prefix.'_tab SET customized=0,ownedby = 1 WHERE name=?', array($moduleName));

			//set fax through mail
			$adb->pquery('insert into tbl_s_faxservertype (server_type,presence) values (?,?)', array('fax_mail',1));

			//filters
			$id = $adb->getUniqueID($table_prefix.'_customview');
			$params = array($id,'All',1,0,'Fax',0,1);
			$sql = "INSERT INTO ".$table_prefix."_customview (cvid,viewname,setdefault,setmetrics,entitytype,status,userid) VALUES (".generateQuestionMarks($params).")";
			$adb->pquery($sql,$params);
			$params = array($id,0,$table_prefix.'_activity:subject:subject:Fax_Subject:V');
			$sql = "INSERT INTO ".$table_prefix."_cvcolumnlist VALUES (".generateQuestionMarks($params).")";
			$adb->pquery($sql,$params);
			$params = array($id,1,$table_prefix.'_emaildetails:to_email:saved_toid:Fax_To:V');
			$sql = "INSERT INTO ".$table_prefix."_cvcolumnlist VALUES (".generateQuestionMarks($params).")";
			$adb->pquery($sql,$params);
			$params = array($id,2,$table_prefix.'_activity:date_start:date_start:Fax_Date_Sent:D');
			$sql = "INSERT INTO ".$table_prefix."_cvcolumnlist VALUES (".generateQuestionMarks($params).")";
			$adb->pquery($sql,$params);

			//disable sharing
			$moduleInstance->disallowSharing();

			$moduleInstance->hide(array('hide_report'=>1)); // crmv@38798

		} else if($eventType == 'module.disabled') {
		} else if($eventType == 'module.enabled') {
		} else if($eventType == 'module.preuninstall') {
		// TODO Handle actions when this module is about to be deleted.
		} else if($eventType == 'module.preupdate') {
		// TODO Handle actions before this module is updated.
		} else if($eventType == 'module.postupdate') {
		// TODO Handle actions after this module is updated.
		}
 	}

}
/** Function to get the emailids for the given ids form the request parameters
 *  It returns an array which contains the mailids and the parentidlists
*/

function get_to_faxids($module)
{
	global $adb;
	global $table_prefix;
	if(isset($_REQUEST["field_lists"]) && $_REQUEST["field_lists"] != "")
	{
		$field_lists = $_REQUEST["field_lists"];
		if (is_string($field_lists)) $field_lists = explode(":", $field_lists);
		$query = 'select columnname,fieldid from '.$table_prefix.'_field where fieldid in('. generateQuestionMarks($field_lists) .')';
		$result = $adb->pquery($query, array($field_lists));
		$columns = Array();
		$idlists = '';
		$faxids = '';
		while($row = $adb->fetch_array($result))
    	{
			$columns[]=$row['columnname'];
			$fieldid[]=$row['fieldid'];
		}
		$columnlists = implode(',',$columns);
		//crmv@27096 //crmv@27917
		$idarray = getListViewCheck($module);
		if (empty($idarray)) {
			$idstring = $_REQUEST['idlist'];
		} else {
			$idstring = implode(':',$idarray);
		}
		//crmv@27096e //crmv@27917e
		$single_record = false;
		if(!strpos($idstring,':'))
		{
			$single_record = true;
		}
		$crmids = str_replace(':',',',$idstring);
		$crmids = explode(",", $crmids);
		switch($module)
		{
			case 'Leads':
				$query = 'select crmid,'.$adb->sql_concat(Array('firstname',"' '",'lastname')).' as entityname,'.$columnlists.' from '.$table_prefix.'_leaddetails
				inner join '.$table_prefix.'_leadaddress on '.$table_prefix.'_leadaddress.leadaddressid='.$table_prefix.'_leaddetails.leadid
				inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid='.$table_prefix.'_leaddetails.leadid left join '.$table_prefix.'_leadscf on '.$table_prefix.'_leadscf.leadid = '.$table_prefix.'_leaddetails.leadid where '.$table_prefix.'_crmentity.deleted=0 and '.$table_prefix.'_crmentity.crmid in ('. generateQuestionMarks($crmids) .')';
				break;
			case 'Contacts':
				//email opt out funtionality works only when we do mass mailing.
//				if(!$single_record)
//				$concat_qry = '(((ltrim(vte_contactdetails.email) != \'\')  or (ltrim(vte_contactdetails.yahooid) != \'\')) and (vte_contactdetails.emailoptout != 1)) and ';
//				else
//				$concat_qry = '((ltrim(vte_contactdetails.email) != \'\')  or (ltrim(vte_contactdetails.yahooid) != \'\')) and ';
				$query = 'select crmid,'.$adb->sql_concat(Array('firstname',"' '",'lastname')).' as entityname,'.$columnlists.' from '.$table_prefix.'_contactdetails inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid='.$table_prefix.'_contactdetails.contactid left join '.$table_prefix.'_contactscf on '.$table_prefix.'_contactscf.contactid = '.$table_prefix.'_contactdetails.contactid where '.$table_prefix.'_crmentity.deleted=0 and '.$table_prefix.'_crmentity.crmid in ('. generateQuestionMarks($crmids) .')';
				break;
			case 'Accounts':
				//added to work out email opt out functionality.
//				if(!$single_record)
//					$concat_qry = '(((ltrim(vte_account.email1) != \'\') or (ltrim(vte_account.email2) != \'\')) and (vte_account.emailoptout != 1)) and ';
//				else
//					$concat_qry = '((ltrim(vte_account.email1) != \'\') or (ltrim(vte_account.email2) != \'\')) and ';

				$query = 'select crmid,accountname as entityname,'.$columnlists.' from '.$table_prefix.'_account inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid='.$table_prefix.'_account.accountid left join '.$table_prefix.'_accountscf on '.$table_prefix.'_accountscf.accountid = '.$table_prefix.'_account.accountid where '.$table_prefix.'_crmentity.deleted=0 and '.$table_prefix.'_crmentity.crmid in ('. generateQuestionMarks($crmids) .')';
				break;
			case 'Vendors':
				$query = 'select crmid,vendorname as entityname,'.$columnlists.' from '.$table_prefix.'_vendor inner join '.$table_prefix.'_crmentity on '.$table_prefix.'_crmentity.crmid='.$table_prefix.'_vendor.vendorid left join '.$table_prefix.'_vendorcf on '.$table_prefix.'_vendorcf.vendorid = '.$table_prefix.'_vendor.vendorid where '.$table_prefix.'_crmentity.deleted=0 and '.$table_prefix.'_crmentity.crmid in ('. generateQuestionMarks($crmids) .')';
				break;
		}
		$result = $adb->pquery($query, array($crmids));
		while($row = $adb->fetch_array($result))
		{
			$name = $row['entityname'];
			for($i=0;$i<count($columns);$i++)
			{
				if($row[$columns[$i]] != NULL && $row[$columns[$i]] !='')
				{
					$idlists .= $row['crmid'].'@'.$fieldid[$i].'|';
					$faxids .= $name.'<'.$row[$columns[$i]].'>,';
				}
			}
		}

		$return_data = Array('idlists'=>$idlists,'faxids'=>$faxids);
	}else
	{
		$return_data = Array('idlists'=>"",'faxids'=>"");
	}
	return $return_data;

}

//added for attach the generated pdf with email
function pdfAttachfax($obj,$module,$file_name,$id)
{
	global $log;
	$log->debug("Entering into pdfAttach() method.");

	global $adb, $current_user;
	global $upload_badext;
	global $table_prefix;
	$date_var = date('Y-m-d H:i:s'); //crmv@69690

	$ownerid = $obj->column_fields['assigned_user_id'];
	if(!isset($ownerid) || $ownerid=='')
		$ownerid = $current_user->id;

	$current_id = $adb->getUniqueID($table_prefix."_crmentity");

	$upload_file_path = decideFilePath();

	//Copy the file from temporary directory into storage directory for upload
	$status = copy("storage/".$file_name,$upload_file_path.$current_id."_".$file_name);
	//Check wheather the copy process is completed successfully or not. if failed no need to put entry in attachment table
	if($status)
	{
		// crmv@150773
		$query1 = "insert into ".$table_prefix."_crmentity (crmid,smcreatorid,smownerid,setype,createdtime,modifiedtime) values(?,?,?,?,?,?)";
		$params1 = array($current_id, $current_user->id, $ownerid, $module." Attachment", $adb->formatDate($date_var, true), $adb->formatDate($date_var, true));
		$adb->pquery($query1, $params1);
		// crmv@150773e

		$query2="insert into ".$table_prefix."_attachments(attachmentsid, name, description, type, path) values(?,?,?,?,?)";
		$params2 = array($current_id, $file_name, $obj->column_fields['description'], 'pdf', $upload_file_path);
		$result=$adb->pquery($query2, $params2);

		$query3='insert into '.$table_prefix.'_seattachmentsrel values(?,?)';
		$adb->pquery($query3, array($id, $current_id));

		return true;
	}
	else
	{
		$log->debug("pdf not attached");
		return false;
	}
}
?>