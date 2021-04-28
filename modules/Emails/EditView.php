<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@25356	crmv@44037	crmv@2051m	crmv@49001 crmv@55137 */

global $table_prefix;
global $log;
global $app_strings;
global $app_list_strings;
global $mod_strings;
global $current_user;
global $currentModule;
global $default_charset;

$focus = CRMEntity::getInstance($currentModule);
$smarty = new VteSmarty();
$json = new Zend_Json();

$focus->setSignatureId();
$send_mode = 'single';	//crmv@26639
$send_action = $_REQUEST['message_mode'];	//crmv@2963m
$relation = $_REQUEST['relation'];
$account = $_REQUEST['account'];

if($_REQUEST['upload_error'] == true)
{
	echo '<br><b><font color="red"> The selected file has no data or a invalid file.</font></b><br>';
}

//Email Error handling
if($_REQUEST['mail_error'] != '') {
	require_once("modules/Emails/mail.php");
	echo parseEmailErrorString($_REQUEST['mail_error']);
}
//added to select the module in combobox of compose-popup
if(isset($_REQUEST['par_module']) && $_REQUEST['par_module']!='') {
	$smarty->assign('select_module',vtlib_purify($_REQUEST['par_module']));
}
elseif(isset($_REQUEST['pmodule']) && $_REQUEST['pmodule']!='') {
	$smarty->assign('select_module',vtlib_purify($_REQUEST['pmodule']));
}

// crmv@43147
if ($_REQUEST['mode'] == 'share' && !empty($_REQUEST['record'])) {
	$CU = CRMVUtils::getInstance();
	$data = $CU->getSharedEmailTemplate(intval($_REQUEST['record']));
	$focus->column_fields['subject'] = $data['subject'];
	$focus->column_fields['description'] = $data['body'];
	$smarty->assign('SUBJECT',$data['subject']);
	$smarty->assign('DESCRIPTION',$data['body']);
}
// crmv@43147e

//crmv@2963m
$focusMessages = CRMEntity::getInstance('Messages');
$message = $_REQUEST['message'];
if(isset($message) && $message != '') {
	$focusMessages->id = $message;
	$focusMessages->retrieve_entity_info($message,'Messages');
	$account = $focusMessages->column_fields['account'];
	$focusMessages->column_fields['mto'] = str_replace('undisclosed-recipients:;','',$focusMessages->column_fields['mto']);
	$recipients = $focusMessages->getRecipientEmails();	//crmv@44429
	switch ($send_action) {
		case 'forward':
			$focus->mode = '';
			$focus->column_fields['subject'] = 'Fwd: '.$focusMessages->column_fields['subject'];
			$focus->addSignature();
			$focus->column_fields['description'] .= '<p></p>'.$focus->getMessageForwardHeader($focusMessages).$focusMessages->column_fields['description'];

			$attachment_links = array();
			$att_ar = $focusMessages->getAttachments();
			foreach ($att_ar as $att) {
				if (!empty($att['target'])) {
					$target = "target='{$att['target']}'";
				}
				//crmv@204525
				$attachment_links[] = [
					"atag"=> "<a href='{$att['link']}' {$target} data-contentid=\"{$att['contentid']}\">{$att['name']}</a>",
					"contentid" => 'cont' . $att['contentid'],
					"url" => $att['link'],
					"name" => $att['name'],
				]; // crmv@121575
				//crmv@204525e
			}
			$smarty->assign('webmail_attachments',$attachment_links);
			break;
		case 'reply_all':
			// crmv@151943 crmv@44429 crmv@191584
			if (is_array($recipients['mcc'])) {
				$cc_mail = array();
				foreach($recipients['mcc'] as $name => $email) {
					($name != $email) ? $cc_mail[] = "\"{$name}\" <{$email}>" : $cc_mail[] = $email;
				}
				$smarty->assign('CC_MAIL', trim(implode(', ',$cc_mail)));
			}
			// crmv@151943e crmv@44429e crmv@191584e
		case 'reply':
			$focus->mode = '';
			$focus->column_fields['subject'] = 'Re: '.$focusMessages->column_fields['subject'];
			$focus->addSignature();
			$focus->column_fields['description'] .= '<p></p>'.$focus->getMessageReplyHeader($focusMessages).'<blockquote type="cite">'.$focusMessages->column_fields['description'].'</blockquote>';
			$specialFolders = $focusMessages->getSpecialFolders(false);
			if (!empty($specialFolders['Sent']) && $focusMessages->column_fields['folder'] == $specialFolders['Sent']) {
				$idlists = $focusMessages->getRecipients('string');
				$to_tmp = $focusMessages->column_fields['mto'];
				if (substr_count($focusMessages->column_fields['mto_f'],',') > substr_count($to_tmp,',')) {
					$to_tmp = trim($focusMessages->column_fields['mto_f']);
				}
				$addressInfo = setAddressInfo($idlists,explode(',',$to_tmp),true);
				$smarty->assign('AUTOSUGGEST',$addressInfo['autosuggest']);
				$smarty->assign('TO_MAIL',$addressInfo['to_mail']);
				$smarty->assign('OTHER_TO_MAIL',$addressInfo['other_to_mail']);
				$smarty->assign('IDLISTS',$idlists);
			} else {
				// crmv@128409 crmv@200330
				if (!empty($focusMessages->column_fields['mreplyto_f']))
					$arr = $focusMessages->parseAddressList($focusMessages->column_fields['mreplyto_f']);
				else
					$arr = $focusMessages->parseAddressList($focusMessages->column_fields['mfrom_f']);
				$smarty->assign('OTHER_TO_MAIL', (!empty($arr[0]['name']))  ? "\"{$arr[0]['name']}\" <{$arr[0]['email']}>" : $arr[0]['email']); // crmv@191584
				// crmv@128409e crmv@200330e
				if ($send_action == 'reply_all') {
					//crmv@44429
					$cc_tmp = $recipients['mto'];
					if (!empty($recipients['mcc'])) {
						$cc_tmp = array_merge($cc_tmp,$recipients['mcc']);
					}
					//crmv@44429e
					$from_email_tmp = $focus->getFromEmailList('',$account);
					foreach($from_email_tmp as $t) {
						if ($t['selected'] == 'selected') {
							$from_email_tmp = $t['email'];
						}
					}
					foreach($cc_tmp as $i => $t) {
						if (empty($t) || trim($t) == $from_email_tmp) {
							unset($cc_tmp[$i]);
						} else {
							trim($cc_tmp[$i]);
						}
					}
				}
				if (!empty($cc_tmp)) {
					// crmv@191584
					$cc_mail = array();
					foreach($cc_tmp as $name => $email) {
						($name != $email) ? $cc_mail[] = "\"{$name}\" <{$email}>" : $cc_mail[] = $email;
					}
					$smarty->assign('CC_MAIL', trim(implode(', ',$cc_mail)));
					// crmv@191584e
				}
			}
			if (!empty($focusMessages->column_fields['send_mode'])) {
				$send_mode = $focusMessages->column_fields['send_mode'];
			}
			break;
		case 'draft':
			$focus->mode = '';
			$focus->column_fields['subject'] = $focusMessages->column_fields['subject'];
			$focus->column_fields['description'] = $focusMessages->column_fields['description'];

			$idlists = $focusMessages->getRecipients('string');
			$to_tmp = $focusMessages->column_fields['mto'];
			if (substr_count($focusMessages->column_fields['mto_f'],',') > substr_count($to_tmp,',')) {
				$to_tmp = trim($focusMessages->column_fields['mto_f']);
			}
			$addressInfo = setAddressInfo($idlists,explode(',',$to_tmp),true);
			$smarty->assign('AUTOSUGGEST',$addressInfo['autosuggest']);
			$smarty->assign('TO_MAIL',$addressInfo['to_mail']);
			$smarty->assign('OTHER_TO_MAIL',$addressInfo['other_to_mail']);
			$smarty->assign('IDLISTS',$idlists);

			$smarty->assign('CC_MAIL', $focusMessages->column_fields['mcc']);
			$smarty->assign('BCC_MAIL', $focusMessages->column_fields['mbcc']);

			$attachment_links = array();
			$att_ar = $focusMessages->getAttachments();
			foreach ($att_ar as $att) {
				if (!empty($att['target'])) {
					$target = "target='{$att['target']}'";
				}
				$attachment_links[] = "<a href='{$att['link']}' {$target}>{$att['name']}</a>";
			}
			$smarty->assign('webmail_attachments',$attachment_links);

			$result_draft = $adb->pquery("SELECT id FROM {$table_prefix}_messages_drafts WHERE userid = ? AND messagehash = ?",array($current_user->id,$focusMessages->column_fields['messagehash']));
			if ($result_draft && $adb->num_rows($result_draft) > 0) {
				$draftid = $adb->query_result($result_draft,0,'id');
			}

			// retrieve related entities
			$rm = RelationManager::getInstance();
			$excludeMods = array('ModComments', 'Campaigns');
			$ids = $rm->getRelatedIds('Messages', $focusMessages->id, null, $excludeMods);
			if (!empty($ids)) {
				$relation = implode('|',$ids);
			}
			if (!empty($focusMessages->column_fields['send_mode'])) {
				$send_mode = $focusMessages->column_fields['send_mode'];
			}

			break;
	}
//crmv@2963me
//crmv@48167
} elseif(isset($_REQUEST['sendmail']) && $_REQUEST['sendmail'] !='') {
	$relation = $_REQUEST['pid'];
	$mailids = get_to_emailids($_REQUEST['pmodule']);
	if (!empty($mailids['mailds'])) {
		$to_add = trim($mailids['mailds'],",").",";
		$addressInfo = setAddressInfo($mailids['idlists'],explode(',',$to_add),true);
		$smarty->assign('AUTOSUGGEST',$addressInfo['autosuggest']);
		$smarty->assign('TO_MAIL',$addressInfo['to_mail']);
		$smarty->assign('OTHER_TO_MAIL',$addressInfo['other_to_mail']);
		$smarty->assign('IDLISTS',$mailids['idlists']);
	} elseif (!empty($relation) && strpos($relation,',') === false) {	//crmv@82025
		$rm = RelationManager::getInstance();
		$relatedPersons = $rm->getRelatedIds($_REQUEST['pmodule'], $relation, array('Contacts','Accounts','Leads','Vendors'));
		if (!empty($relatedPersons)) {
			foreach($relatedPersons as $rp) {
				$relation .= ",$rp";	//crmv@86302
				// I found the first fieldid with not empty value
				$rp_module = getSalesEntityType($rp);
				$rp_focus = CRMEntity::getInstance($rp_module);
				$rp_instance = Vtecrm_Module::getInstance($rp_module);
				$res = $adb->pquery("select fieldid, fieldname, fieldlabel, columnname, tablename from {$table_prefix}_field where tabid=? and uitype=13 and presence in (0,2)", array($rp_instance->id));
				$otherreturnvalue = Array();
				while($row=$adb->fetchByAssoc($res)) {
					$permit = getFieldVisibilityPermission($rp_module, $userid, $row["fieldname"]);
					if($permit == '0')
					{
						$val = getSingleFieldValue($row['tablename'],$row['columnname'],$rp_focus->tab_name_index[$row['tablename']],$rp);
						if (!empty($val)) {
							$fieldid = $row['fieldid'];
							break(2);
						}
					}
				}
			}
			if (!empty($fieldid)) {
				saveListViewCheck($rp_module,$rp);
				$_REQUEST["field_lists"] = $fieldid;
				$mailids = get_to_emailids($rp_module);
				$to_add = trim($mailids['mailds'],",").",";
				$addressInfo = setAddressInfo($mailids['idlists'],explode(',',$to_add),true);
				$smarty->assign('AUTOSUGGEST',$addressInfo['autosuggest']);
				$smarty->assign('TO_MAIL',$addressInfo['to_mail']);
				$smarty->assign('OTHER_TO_MAIL',$addressInfo['other_to_mail']);
				$smarty->assign('IDLISTS',$mailids['idlists']);
			}
		}
	}
	$focus->addSignature();
	//crmv@26111e
	$focus->mode = '';
//crmv@48167e
//crmv@2043m
} elseif(isset($_REQUEST['reply_mail_converter']) && $_REQUEST['reply_mail_converter'] != '') {
	$HelpDeskFocus = CRMEntity::getInstance('HelpDesk');
	$HelpDeskFocus->retrieve_entity_info($_REQUEST['reply_mail_converter_record'],'HelpDesk');
	$_REQUEST["field_lists"] = implode(':',getFieldList(getSalesEntityType($HelpDeskFocus->column_fields['parent_id'])));
	// crmv@140928
	$parentModule = getSalesEntityType($HelpDeskFocus->column_fields['parent_id']);
	if ($parentModule) {
		saveListViewCheck($parentModule,$HelpDeskFocus->column_fields['parent_id']);
	}
	// crmv@140928e
	$mailids = get_to_emailids(getSalesEntityType($HelpDeskFocus->column_fields['parent_id']));
	$addressInfo = setAddressInfo($mailids['idlists'],array(),true);
	$smarty->assign('AUTOSUGGEST',$addressInfo['autosuggest']);
	if ($addressInfo['to_mail'] != '') {
		$smarty->assign('TO_MAIL',$addressInfo['to_mail']);
	} elseif ($mailids['idlists'] != '') {
		$tmp1 = explode('|',$mailids['idlists']);
		if ($tmp1[0] != '') {
			$tmp2 = explode('@',$tmp1[0]);
			if (!empty($tmp2)) {
				$smarty->assign('TO_MAIL',getFieldValue($tmp2[1], $tmp2[0]));
			}
		}
	}
	$smarty->assign('OTHER_TO_MAIL',$addressInfo['other_to_mail']);
	$smarty->assign('IDLISTS',$mailids['idlists']);
	$focus->mode = '';
	$focus->column_fields['subject'] = 'Re: '.$HelpDeskFocus->column_fields['ticket_title'].' - Ticket Id: '.$_REQUEST['reply_mail_converter_record'];
	if ($_REQUEST['reply_mail_user'] != 'mailconverter') {
		$focus->addSignature();
	}
	if(isset($_REQUEST['reply_mail_converter']) && $_REQUEST['reply_mail_converter'] != '') {
		if ($_REQUEST['reply_mail_user'] == 'mailconverter') {
			$from_email = $HelpDeskFocus->column_fields['helpdesk_from'];
		} else {
			$from_email = $current_user->column_fields['email1'];
		}
	}
//crmv@2043me
} else {
	//crmv@78538
	$relation = $_REQUEST['pid'];
	if (!empty($relation) && strpos($relation,',') === false) {	//crmv@82025
		$rm = RelationManager::getInstance();
		$relatedRecords = $rm->getRelatedIds($_REQUEST['pmodule'], $relation, array('Contacts','Accounts','Leads','Vendors'));
		if (!empty($relatedRecords)) {
			foreach($relatedRecords as $rp) {
				$relation .= ",$rp";	//crmv@86302
				// I found the first fieldid with not empty value
				$rp_module = getSalesEntityType($rp);
				$rp_focus = CRMEntity::getInstance($rp_module);
				$rp_instance = Vtecrm_Module::getInstance($rp_module);
				$res = $adb->pquery("select fieldid, fieldname, fieldlabel, columnname, tablename from {$table_prefix}_field where tabid=? and uitype=13 and presence in (0,2)", array($rp_instance->id));
				$otherreturnvalue = Array();
				while($row=$adb->fetchByAssoc($res)) {
					$permit = getFieldVisibilityPermission($rp_module, $userid, $row["fieldname"]);
					if($permit == '0')
					{
						$val = getSingleFieldValue($row['tablename'],$row['columnname'],$rp_focus->tab_name_index[$row['tablename']],$rp);
						if (!empty($val)) {
							$fieldid = $row['fieldid'];
							break(2);
						}
					}
				}
			}
			if (!empty($fieldid)) {
				saveListViewCheck($rp_module,$rp);
				$_REQUEST["field_lists"] = $fieldid;
				$mailids = get_to_emailids($rp_module);
				$to_add = trim($mailids['mailds'],",").",";
				$addressInfo = setAddressInfo($mailids['idlists'],explode(',',$to_add),true);
				$smarty->assign('AUTOSUGGEST',$addressInfo['autosuggest']);
				$smarty->assign('TO_MAIL',$addressInfo['to_mail']);
				$smarty->assign('OTHER_TO_MAIL',$addressInfo['other_to_mail']);
				$smarty->assign('IDLISTS',$mailids['idlists']);
			}
		}
	}
	//crmv@78538e
	$focus->addSignature();
}

if ($from_email == '') {
	$from_email = $focus->column_fields['from_email'];
}
if (empty($account)) {	//crmv@57983
	$main_account = $focusMessages->getMainUserAccount();
	$account = $main_account['id'];
}
$from_email_list = $focus->getFromEmailList($from_email,$account);
foreach($from_email_list as $e) {
	if ($e['selected'] == 'selected') {
		$signature_account = $e['account'];
		break;
	}
}
if (empty($signature_account)) {
	foreach($from_email_list as $e) {
		$signature_account = $e['account'];
		break;
	}
}
$smarty->assign('FROM_EMAIL_LIST',$from_email_list);
$smarty->assign('SIGNATUREID',$focus->signatureId);
$smarty->assign('SIGNATURE',$focusMessages->getAccountSignature($signature_account));

// INTERNAL MAILER
if($_REQUEST["internal_mailer"] == "true") {

	$smarty->assign('INT_MAILER',"true");
	$rec_type = $_REQUEST["type"];
	$rec_id = $_REQUEST["rec_id"];
	$fieldname = $_REQUEST["fieldname"];

	//added for getting list-ids to compose email popup from list view(Accounts,Contacts,Leads)
	if(isset($_REQUEST['field_id']) && strlen($_REQUEST['field_id']) != 0) {
		if($_REQUEST['par_module'] == "Users")
			$id_list = $_REQUEST['rec_id'].'@'.'-1|';
		else
			$id_list = $_REQUEST['rec_id'].'@'.$_REQUEST['field_id'].'|';
		$smarty->assign("IDLISTS", $id_list);
	}
	if($rec_type == "record_id") {
		$type = $_REQUEST['par_module'];
		//check added for email link in user detail view
		$normal_tabs = Array('Users'=>$table_prefix.'_users', 'Leads'=>$table_prefix.'_leaddetails', 'Contacts'=>$table_prefix.'_contactdetails', 'Accounts'=>$table_prefix.'_account', 'Vendors'=>$table_prefix.'_vendor');
		$cf_tabs = Array('Accounts'=>$table_prefix.'_accountscf', 'Campaigns'=>$table_prefix.'_campaignscf', 'Contacts'=>$table_prefix.'_contactscf', 'Invoice'=>$table_prefix.'_invoicecf', 'Leads'=>$table_prefix.'_leadscf', 'Potentials'=>$table_prefix.'_potentialscf', 'Products'=>$table_prefix.'_productcf',  'PurchaseOrder'=>$table_prefix.'_purchaseordercf', 'Quotes'=>$table_prefix.'_quotescf', 'SalesOrder'=>$table_prefix.'_salesordercf', 'HelpDesk'=>$table_prefix.'_ticketcf', 'Vendors'=>$table_prefix.'_vendorcf');
		if(substr($fieldname,0,2)=="cf")
			$tablename = $cf_tabs[$type];
		else
			$tablename = $normal_tabs[$type];
		if($type == "Users")
			$q = "select $fieldname from $tablename where id=?";
		elseif($type == "Leads")
			$q = "select $fieldname from $tablename where leadid=?";
		elseif ($type == "Contacts")
			$q = "select $fieldname from $tablename where contactid=?";
		elseif ($type == "Accounts")
			$q = "select $fieldname from $tablename where accountid=?";
		elseif ($type == "Vendors")
			$q = "select $fieldname from $tablename where vendorid=?";
		else {
			// vtlib customization: Support for email-type custom field for other modules.
			//crmv@21474
			$module_focus = CRMEntity::getInstance($type);
			// crmv@187823
			$finfo = getFieldTableAndColumn($type,$fieldname);
			$tablename = $finfo['tablename'];
			$columnname = $finfo['columnname'];
			$tablename_index = $module_focus->tab_name_index[$tablename];
 			$q = "select $columnname as $fieldname from $tablename where $tablename_index = ?";
 			// crmv@187823e
			//crmv@21474e
			// END
		}
		$email1 = $adb->query_result($adb->pquery($q, array($rec_id)),0,$fieldname);
	} elseif ($rec_type == "email_addy") {
		$email1 = $_REQUEST["email_addy"];
	}
	$addressInfo = setAddressInfo($id_list,explode(',',$email1));
	$smarty->assign('AUTOSUGGEST',$addressInfo['autosuggest']);
	$smarty->assign('TO_MAIL',$addressInfo['to_mail']);
	$smarty->assign('OTHER_TO_MAIL',$addressInfo['other_to_mail']);
	$focus->addSignature();
}

//Added to set the cc when click reply all
if(isset($_REQUEST['msg_cc']) && $_REQUEST['msg_cc'] != '')
{
	$smarty->assign("MAIL_MSG_CC", $_REQUEST['msg_cc']);
}

if (!empty($relation)) {
	$links = array();
	//crmv@60186
	$ids = null;
	if (strpos($relation,'|') !== false) {
		$ids = array_filter(explode('|', $relation));
	} elseif (strpos($relation,',') !== false) {
		$ids = array_filter(explode(',', $relation));
	}
	if (is_array($ids)) {
	//crmv@60186e
		foreach ($ids as $relid) {
			list($elid, $fieldid) = explode('@', $relid, 2);
			if (strpos($elid,'x') !== false) {
				$elid = explode('x',$elid);
				$elid = $elid[1];
			}
			$l = $focusMessages->getEntityPreview($elid);
			if ($l && (isPermitted($l['module'], 'DetailView', $l['id']) == 'yes' || $l['module'] != 'Documents')) $links[$elid] = $l; //crmv@193042
		}
	} else {
		$l = $focusMessages->getEntityPreview($relation);
		if ($l && (isPermitted($l['module'], 'DetailView', $l['id']) == 'yes' || $l['module'] != 'Documents')) $links[$relation] = $l; //crmv@193042
	}
	$smarty->assign('LINKS_STR',implode('|',array_keys($links)));
	$smarty->assign('LINKS',$links);
}

//crmv@25391	crmv@80155
$smarty->assign('USE_SIGNATURE',1);

$result = $adb->pquery('SELECT templateid,subject,body,use_signature,overwrite_message FROM '.$table_prefix.'_emailtemplates WHERE templatename = ?',array($_REQUEST['templatename']));
if ($result && $adb->num_rows($result)>0) {
	$subject = $adb->query_result_no_html($result,0,'subject');
	$body = $adb->query_result_no_html($result,0,'body');
	$overwrite_message = $adb->query_result_no_html($result,0,'overwrite_message');
	$use_signature = $adb->query_result_no_html($result,0,'use_signature');
	if (intval($overwrite_message) == 0) {
		$body = "<div id='template{$focus->signatureId}'>{$body}</div>".$focus->column_fields['description'];
	} else {
		$body = "<div id='template{$focus->signatureId}'>{$body}</div>";
	}
	$focus->column_fields['subject'] = $subject;
	$focus->column_fields['description'] = $body;
	if (strpos($body,"<div id=\"signature{$focus->signatureId}\">") === false) {
		$focus->signatureStatus = false;
		$focus->addSignature();
	}
	$smarty->assign('SUBJECT',$subject);
	$smarty->assign('DESCRIPTION',$body);
	$smarty->assign('USE_SIGNATURE',(intval($use_signature)));
	$send_mode = 'multiple';
}
//crmv@25391e	crmv@80155e
//crmv@26639
if (isset($_REQUEST['send_mode']) && in_array($_REQUEST['send_mode'],array('single','multiple'))) {
	$send_mode = $_REQUEST['send_mode'];
}
$smarty->assign('SEND_MODE', $send_mode);
//crmv@26639e

global $theme;
$theme_path="themes/".$theme."/";
$image_path=$theme_path."images/";

$disp_view = getView($focus->mode);
//crmv@9434
$mode = $focus->mode;
//crmv@9434 end
$details = getBlocks($currentModule,$disp_view,$mode,$focus->column_fields);
//changed this below line to view description in all language - bharath
$smarty->assign("BLOCKS",$details[$mod_strings['LBL_EMAIL_INFORMATION']]);
$smarty->assign("MODULE",$currentModule);
$smarty->assign("SINGLE_MOD",$app_strings['Email']);
//id list of attachments while forwarding
$smarty->assign("ATT_ID_LIST",$att_id_list);

//needed when creating a new email with default values passed in
if (isset($_REQUEST['contact_name']) && is_null($focus->contact_name))
{
	$focus->contact_name = $_REQUEST['contact_name'];
}
if (isset($_REQUEST['contact_id']) && is_null($focus->contact_id))
{
	$focus->contact_id = $_REQUEST['contact_id'];
}
if (isset($_REQUEST['parent_name']) && is_null($focus->parent_name))
{
	$focus->parent_name = $_REQUEST['parent_name'];
}
if (isset($_REQUEST['parent_id']) && is_null($focus->parent_id))
{
	$focus->parent_id = $_REQUEST['parent_id'];
	//crmv@22512
	$smarty->assign("IDLISTS",$focus->parent_id);
	//crmv@22512e
}
if (isset($_REQUEST['parent_type']))
{
	$focus->parent_type = $_REQUEST['parent_type'];
}
elseif (is_null($focus->parent_type))
{
	$focus->parent_type = $app_list_strings['record_type_default_key'];
}

$log->info("Email detail view");

$smarty->assign("MOD", $mod_strings);
$smarty->assign("APP", $app_strings);
if (isset($focus->name)) $smarty->assign("NAME", $focus->name);
else $smarty->assign("NAME", "");

if($focus->mode == 'edit')
{
	$smarty->assign("UPDATEINFO",updateInfo($focus->id));
	$smarty->assign("MODE", $focus->mode);
}

// Unimplemented until jscalendar language vte_files are fixed

$smarty->assign("CALENDAR_LANG", $app_strings['LBL_JSCALENDAR_LANG']);
$smarty->assign("CALENDAR_DATEFORMAT", parse_calendardate($app_strings['NTC_DATE_FORMAT']));

if(isset($_REQUEST['return_module'])) $smarty->assign("RETURN_MODULE", vtlib_purify($_REQUEST['return_module']));
else $smarty->assign("RETURN_MODULE",'Emails');
if(isset($_REQUEST['return_action'])) $smarty->assign("RETURN_ACTION", vtlib_purify($_REQUEST['return_action']));
else $smarty->assign("RETURN_ACTION",'index');
if(isset($_REQUEST['return_id'])) $smarty->assign("RETURN_ID", vtlib_purify($_REQUEST['return_id']));
if (isset($_REQUEST['return_viewname'])) $smarty->assign("RETURN_VIEWNAME", vtlib_purify($_REQUEST['return_viewname']));

$smarty->assign("THEME", $theme);
$smarty->assign("IMAGE_PATH", $image_path);
$smarty->assign("ID", $focus->id);
$smarty->assign("ENTITY_ID", vtlib_purify($_REQUEST["record"]));
$smarty->assign("ENTITY_TYPE",vtlib_purify($_REQUEST["email_directing_module"]));
$smarty->assign("OLD_ID", $old_id );
//Display the FCKEditor or not? -- configure $FCKEDITOR_DISPLAY in config.php
$smarty->assign("FCKEDITOR_DISPLAY",$FCKEDITOR_DISPLAY);

$check_button = Button_Check($module);
$smarty->assign("CHECK", $check_button);

//crmv@sdk-18501
include_once('vtlib/Vtecrm/Link.php');//crmv@207871
$hdrcustomlink_params = Array('MODULE'=>$currentModule);
$COMMONHDRLINKS = Vtecrm_Link::getAllByType(Vtecrm_Link::IGNORE_MODULE, Array('HEADERLINK','HEADERSCRIPT', 'HEADERCSS'), $hdrcustomlink_params);
$smarty->assign('HEADERLINKS', $COMMONHDRLINKS['HEADERLINK']);
$smarty->assign('HEADERSCRIPTS', $COMMONHDRLINKS['HEADERSCRIPT']);
$smarty->assign('HEADERCSS', $COMMONHDRLINKS['HEADERCSS']);
//crmv@sdk-18501 e

// crmv@42024 crmv@167238 - pass global JS vars to template
$JSGlobals = ( function_exists('getJSGlobalVars') ? getJSGlobalVars() : array() );
$smarty->assign('JS_GLOBAL_VARS', Zend_Json::encode($JSGlobals));
// crmv@42024e crmv@167238e

// Gather the custom link information to display
$customlink_params = Array('MODULE'=>$currentModule);
$smarty->assign('CUSTOM_LINKS', Vtecrm_Link::getAllByType(getTabid('Messages'), Array('DETAILVIEWWIDGET'), $customlink_params));
// END

include('modules/VteCore/Turbolift.php'); // crmv@43864

//crmv@2963m
$uploaddir = $current_user->id.'_'.date("YmdHis");
$smarty->assign('UPLOADIR', $uploaddir);
if (empty($draftid)) {
	$draftid = md5(uniqid($current_user->id));
}
$smarty->assign('DRAFTID', $draftid);
//crmv@2963me
$smarty->assign('FOCUS', $focus); //crmv@58893
$smarty->display("ComposeEmail.tpl");
?>