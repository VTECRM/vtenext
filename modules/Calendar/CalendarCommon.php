<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//Code Added by Minnie -Starts
/**
 * To get the lists of '.$table_prefix.'_users id who shared their calendar with specified user
 * @param $sharedid -- The shared user id :: Type integer
 * @returns $shared_ids -- a comma seperated '.$table_prefix.'_users id  :: Type string
 */
function getSharedCalendarId($sharedid)
{
	global $adb,$table_prefix;
	$query = "SELECT userid from ".$table_prefix."_sharedcalendar sh inner join ".$table_prefix."_users u ON u.id=sh.userid where sharedid=? and u.status='Active'"; // crmv@187823 crmv@203476
	$result = $adb->pquery($query, array($sharedid));
	if($adb->num_rows($result)!=0)
	{
		for($j=0;$j<$adb->num_rows($result);$j++)
			$userid[] = $adb->query_result_no_html($result,$j,'userid'); // crmv@187823
		$shared_ids = implode (",",$userid);
	}
	return $shared_ids;
}

/**
 * To get hour,minute and format
 * @param $starttime -- The date&time :: Type string
 * @param $endtime -- The date&time :: Type string
 * @param $format -- The format :: Type string
 * @returns $timearr :: Type Array
*/
function getaddEventPopupTime($starttime,$endtime,$format)
{
	$timearr = Array();
	list($sthr,$stmin) = explode(":",$starttime);
	list($edhr,$edmin)  = explode(":",$endtime);
	if($format == 'am/pm')
	{
		$hr = $sthr+0;
		$timearr['startfmt'] = ($hr >= 12) ? "pm" : "am";
		if($hr == 0) $hr = 12;
		$timearr['starthour'] = twoDigit(($hr>12)?($hr-12):$hr);
		$timearr['startmin']  = $stmin;

		$edhr = $edhr+0;
		$timearr['endfmt'] = ($edhr >= 12) ? "pm" : "am";
		if($edhr == 0) $edhr = 12;
		$timearr['endhour'] = twoDigit(($edhr>12)?($edhr-12):$edhr);
		$timearr['endmin']    = $edmin;
		return $timearr;
	}
	if($format == '24')
	{
		$timearr['starthour'] = twoDigit($sthr);
		$timearr['startmin']  = $stmin;
		$timearr['startfmt']  = '';
		$timearr['endhour']   = twoDigit($edhr);
		$timearr['endmin']    = $edmin;
		$timearr['endfmt']    = '';
		return $timearr;
	}
}

/**
 *To construct time select combo box
 *@param $format -- the format :: Type string
 *@param $bimode -- The mode :: Type string
 *constructs html select combo box for time selection
 *and returns it in string format.
 */
function getTimeCombo($format,$bimode,$hour='',$min='',$fmt='',$todocheck=false,$quickcreate=false) //crmv@31315
{
	global $mod_strings, $current_user; // crmv@181170
	$combo = '';
	if (empty($min)) $min=0; // crmv@188692
	$min = $min - ($min%5);
	//crmv@17001
	//crmv@31315
	if($bimode == 'start' && !$todocheck && !$quickcreate)
		$jsfn = 'onChange="changeEndtime_StartTime();'.$jsfn1.'"';
	elseif($bimode == 'start' && !$todocheck && $quickcreate)
		$jsfn = 'onChange="parent.calDuedatetimeQC(this.form,\'hour\');"';
	elseif ($jsfn1 != '')
		$jsfn = 'onChange="'.$jsfn1.'"';
	//crmv@17001e
	//crmv@31315e

	// crmv@181170
	if (empty($format)) {
		$format = $current_user->hour_format;
	}
	// crmv@181170e
		
	if($format == 'am/pm')
	{
		$combo .= '<select class=small name="'.$bimode.'hr" id="'.$bimode.'hr" '.$jsfn.'>';
		for($i=0;$i<12;$i++)
		{
			if($i == 0)
			{
				$hrtext= 12;
				$hrvalue = 12;
			}
			else
				$hrvalue = $hrtext = twoDigit($i);
			$hrsel = ($hour == $hrvalue)?'selected':'';
			$combo .= '<option value="'.$hrvalue.'" '.$hrsel.'>'.$hrtext.'</option>';
		}
		$combo .= '</select>&nbsp;';
		$combo .= '<select name="'.$bimode.'min" id="'.$bimode.'min" class=small '.$jsfn.'>';
		for($i=0;$i<12;$i++)
		{
			$value = $i*5;
			$value = twoDigit($value);
			$minsel = ($min == $value)?'selected':'';
			$combo .= '<option value="'.$value.'" '.$minsel.'>'.$value.'</option>';
		}
		$combo .= '</select>&nbsp;';
		$combo .= '<select name="'.$bimode.'fmt" id="'.$bimode.'fmt" class=small '.$jsfn.'>';
		$amselected = ($fmt == 'am')?'selected':'';
		$pmselected = ($fmt == 'pm')?'selected':'';
		$combo .= '<option value="am" '.$amselected.'>AM</option>';
		$combo .= '<option value="pm" '.$pmselected.'>PM</option>';
		$combo .= '</select>';
	}
	else
	{
		// crmv@98866
		//crmv@OPER6317
		$combo .= '<table style="width:100%">';
		$combo .= '<tr><td><label>'.$mod_strings['LBL_HR'].'</label></td><td><select name="'.$bimode.'hr" id="'.$bimode.'hr" class="small detailedViewTextBox" '.$jsfn.'>'; // crmv@167234
		for($i=0;$i<=23;$i++)
		{
			$hrvalue = twoDigit($i);
			$hrsel = ($hour == $hrvalue)?'selected':'';
			$combo .= '<option value="'.$hrvalue.'" '.$hrsel.'>'.$hrvalue.'</option>';
		}
		$combo .= '</select></td><td></td>';
		$combo .= '<td><select name="'.$bimode.'min" id="'.$bimode.'min" class="small detailedViewTextBox" '.$jsfn.'>'; // crmv@167234
		for($i=0;$i<12;$i++)
		{
			$value = $i*5;
			$value= twoDigit($value);
			$minsel = ($min == $value)?'selected':'';
			$combo .= '<option value="'.$value.'" '.$minsel.'>'.$value.'</option>';
		}
		$combo .= '</select><input type="hidden" name="'.$bimode.'fmt" id="'.$bimode.'fmt"></td></tr></table>';
		//crmv@OPER6317e
		// crmv@98866 end
	}
	return $combo;
}

/**
 *Function to construct HTML select combo box
 *@param $fieldname -- the field name :: Type string
 *@param $tablename -- The table name :: Type string
 *constructs html select combo box for combo field
 *and returns it in string format.
 */

function getActFieldCombo($fieldname,$tablename)
{
	global $adb, $mod_strings,$current_user,$table_prefix;
	require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	$combo = '';
	$js_fn = '';
	if($fieldname == 'eventstatus')
		$js_fn = 'onChange = "getSelectedStatus();"';
	$combo .= '<select name="'.$fieldname.'" id="'.$fieldname.'" class="detailedViewTextBox" '.$js_fn.'>';
	$roleid=$current_user->roleid;
	$subrole = getRoleSubordinates($roleid);
	if(count($subrole)> 0)
	{
		$roleids = $subrole;
		array_push($roleids, $roleid);
	}
	else
	{
		$roleids = $roleid;
	}
	$pick_query="select $fieldname from ".$table_prefix."_$fieldname inner join ".$table_prefix."_role2picklist on ".$table_prefix."_role2picklist.picklistvalueid = ".$table_prefix."_$fieldname.picklist_valueid and roleid = ? ";
	$params = array($roleid);
	$pick_query.=" order by sortid asc "; //crmv@32334
	$Res = $adb->pquery($pick_query,$params);
	$noofrows = $adb->num_rows($Res);

	for($i = 0; $i < $noofrows; $i++)
	{
		$value = $adb->query_result($Res,$i,$fieldname);
		$combo .= '<option value="'.$value.'">'.getTranslatedString($value).'</option>';
	}

	$combo .= '</select>';
	return $combo;
}

/*Fuction to get value for Assigned To field
 *returns values of Assigned To field in array format
*/
function getAssignedTo($tabid)
{
	global $current_user,$noof_group_rows,$adb;
	$assigned_user_id = $current_user->id;
	require('user_privileges/sharing_privileges_'.$current_user->id.'.php');
	require('user_privileges/requireUserPrivileges.php'); // crmv@39110
	if($is_admin==false && $profileGlobalPermission[2] == 1 && ($defaultOrgSharingPermission[$tabid] == 3 or $defaultOrgSharingPermission[$tabid] == 0))
	{
		$result=get_current_user_access_groups('Calendar');
	}
	else
	{
		$result = get_group_options();
	}
	if($result) $nameArray = $adb->fetch_array($result);

	if($is_admin==false && $profileGlobalPermission[2] == 1 && ($defaultOrgSharingPermission[$tabid] == 3 or $defaultOrgSharingPermission[$tabid] == 0))
	{
		$users_combo = get_select_options_array(get_user_array(FALSE, "Active", $assigned_user_id,'private'), $assigned_user_id);
	}
	else
	{
		$users_combo = get_select_options_array(get_user_array(FALSE, "Active", $assigned_user_id), $assigned_user_id);
	}
	if($noof_group_rows!=0)
	{
		do
		{
			$groupname=$nameArray["groupname"];
			$group_option[] = array($groupname=>$selected);

		}while($nameArray = $adb->fetch_array($result));
	}
	//crmv@20209
	global $showfullusername;
	$shared_calendar = getSharedCalendarId($current_user->id);
	if ($shared_calendar) {
		$shared_calendar = explode(',',$shared_calendar);
		foreach($shared_calendar as $id) {
			$users_combo[$id] = array(getUserName($id,$showfullusername)=>'');
		}
	}
	//crmv@20209e
	$fieldvalue[]=$users_combo;
	$fieldvalue[] = $group_option;
	return $fieldvalue;
}

//Code Added by Minnie -Ends

function twoDigit( $no ){
	if($no < 10 && strlen(trim($no)) < 2) return "0".$no;
	else return "".$no;
}

function timeString($datetime,$fmt){

	if(is_object($datetime)){
		$hr = $datetime->hour;
		$min = $datetime->minute;
	} else {
		$hr = $datetime['hour'];
		$min = $datetime['minute'];
	}
	$timeStr = "";
	if($fmt != 'am/pm'){
		$timeStr .= twoDigit($hr).":".twoDigit($min);
	}else{
		$am = ($hr >= 12) ? "pm" : "am";
		if($hr == 0) $hr = 12;
		$timeStr .= ($hr>12)?($hr-12):$hr;
		$timeStr .= ":".twoDigit($min);
		$timeStr .= $am;
	}
	return $timeStr;
}

//crmv@26807
function getEmailInvitationDescription($description,$user_id,$record='',$answer='',$from='')
{
	global $adb,$log,$current_user,$site_URL,$application_unique_key,$table_prefix;
	$status = getTranslatedString($description['eventstatus'],'Calendar');
	$desc = getTranslatedString('LBL_MAIL_LBL_ANSWER','Calendar').' "<b>'.$answer.'</b>" '.getTranslatedString('LBL_TO_INVITATION','Calendar');

	// crmv@25610
	$tzinfo = '';
	if (!empty($current_user->column_fields['user_timezone'])) {
		$tzinfo = " ({$current_user->column_fields['user_timezone']})";
	}
	// crmv@25610e

	$list = '';
	$list .= '<br> '.$desc.'.<br>';
	$list .= '<br> '.getTranslatedString('LBL_DETAILS_STRING','Calendar').':';
	$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("LBL_SUBJECT",'Calendar').' '.$description['subject'];
	$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("Start date and time",'Calendar').' : '.$description['date_start'].' '.$description['time_start'].$tzinfo; // crmv@25610
	$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString('End date and time','Calendar').' : '.$description['due_date'].' '.$description['time_end'].$tzinfo; // crmv@25610
	$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("LBL_STATUS",'Calendar').': '.$status;
	$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("Priority",'Calendar').': '.getTranslatedString($description['taskpriority']);
	$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("Location",'Calendar').': '.getTranslatedString($description['location']);
	$list .= '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("LBL_APP_DESCRIPTION",'Calendar').': '.$description['description'];

	$confirm_url = $site_URL."/hub/cinv.php?from=$from&app_key=$application_unique_key&record=$record&userid=".$user_id; // crmv@192078
	$link_answer = '';
	if ($answer == getTranslatedString('LBL_NO','Calendar')) {
		$link_answer = "<a href='".$confirm_url."&partecipation=2'><b>".getTranslatedString('LBL_YES','Calendar')."</b></a>";
	}
	else if ($answer == getTranslatedString('LBL_YES','Calendar')) {
		$link_answer = "<a href='".$confirm_url."&partecipation=1'><b>".getTranslatedString('LBL_NO','Calendar')."</b></a>";
	}

	$invitees = '<br>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.getTranslatedString("LBL_CAL_INVITATION",'Calendar').':  ';
	$query = '	SELECT inviteeid, partecipation, last_name, first_name
				FROM '.$table_prefix.'_invitees
				LEFT JOIN '.$table_prefix.'_users ON '.$table_prefix.'_users.id = '.$table_prefix.'_invitees.inviteeid
				WHERE activityid = ?
				';
	$res = $adb->pquery($query, array($record));
	if ($res && $adb->num_rows($res)>0) {
		while($row = $adb->fetchByAssoc($res)) {
			if ($row['partecipation'] == 2) {
				$answer = getTranslatedString('LBL_YES','Calendar');
			}
			elseif ($row['partecipation'] == 1) {
				$answer = getTranslatedString('LBL_NO','Calendar');
			}
			else {
				$answer = '?';
			}
			$invitees .= $row['last_name'].' '.$row['first_name'].' ('.$answer.'), ';
		}
	}
	$query = 'SELECT inviteeid,partecipation FROM '.$table_prefix.'_invitees_con WHERE activityid = ? ';
	$res = $adb->pquery($query, array($record));
	if ($res && $adb->num_rows($res)>0) {
		while($row = $adb->fetchByAssoc($res)) {
			if ($row['partecipation'] == 2) {
				$answer = getTranslatedString('LBL_YES','Calendar');
			}
			elseif ($row['partecipation'] == 1) {
				$answer = getTranslatedString('LBL_NO','Calendar');
			}
			else {
				$answer = '?';
			}
			$invitees .= getContactName($row['inviteeid']).' ('.$answer.'), ';
		}
	}
	$list .= substr($invitees,0,strlen($invitees)-2);

	$list .= '<br><br>'.getTranslatedString('LBL_CHANGE_ANSWER','Calendar').' '.$link_answer.'.<br>';

	$link = "<a href='".$site_URL."/index.php?module=Calendar&action=DetailView&record=$record'>".getTranslatedString('LBL_HERE','Calendar')."</a>";
	$list .= getTranslatedString('LBL_MAIL_INVITATION_1','Calendar').' <b>'.$link.'</b> '.getTranslatedString('LBL_MAIL_INVITATION_3','Calendar').'.<br><br>';
// 	$list .= '<br><br>'.getTranslatedString("LBL_REGARDS_STRING",'Calendar').' ,';
// 	$list .= '<br>'.$current_username.'.';
	return $list;
}
//crmv@26807e

/**
 * Function to construct HTML code for Assigned To field
 * @param $assignedto -- Assigned To values :: Type array
 * @param $toggletype -- String to different event and task :: Type string
 * return $htmlStr -- HTML code in string forat :: Type string
 */
// crmv@31171 // crmv@98866
function getAssignedToHTML($assignedto, $toggletype) {
	global $app_strings, $table_prefix, $theme;
	$userlist = $assignedto[0];
	if (isset($assignedto[1]) && $assignedto[1] != null) $grouplist = $assignedto[1];
	$htmlStr1 = $htmlStr2 = '';
	$check = 1;
	foreach ($userlist as $key_one => $arr) {
		foreach ($arr as $sel_value => $value) {
			if ($value != '') $check = $check * 0;
			else $check = $check * 1;
		}
	}
	if ($check == 0) {
		$select_user = 'selected="selected"';
		$select_group = '';
		$style_user = 'display:block;position:relative;';
		$style_group = 'display:none;position:relative;';
	} else {
		$select_user = '';
		$select_group = 'selected="selected"';
		$style_user = 'display:none;position:relative;';
		$style_group = 'display:block;position:relative;';
	}
	if ($grouplist != '') {
		if ($toggletype == 'task') $htmlStr1 .= '<select name="task_assigntype" class="detailedViewTextBox" onChange=\'toggleTaskAssignType(this.value); document.createTodo.task_assigned_user_id_display.value=""; document.createTodo.task_assigned_user_id.value=""; enableReferenceField(document.createTodo.task_assigned_user_id_display); document.createTodo.task_assigned_group_id_display.value=""; document.createTodo.task_assigned_group_id.value=""; enableReferenceField(document.createTodo.task_assigned_group_id_display); closeAutocompleteList("task_assigned_user_id_display"); closeAutocompleteList("task_assigned_group_id_display");\'>';
		else $htmlStr1 .= '<select name="assigntype" class="detailedViewTextBox" onChange=\'toggleAssignType(this.value); document.EditView.assigned_user_id_display.value=""; document.EditView.assigned_user_id.value=""; enableReferenceField(document.EditView.assigned_user_id_display); document.EditView.assigned_group_id_display.value=""; document.EditView.assigned_group_id.value=""; enableReferenceField(document.EditView.assigned_group_id_display); closeAutocompleteList("assigned_user_id_display"); closeAutocompleteList("assigned_group_id_display");\'>';
		$htmlStr1 .= '<option value="U" ' . $select_user . '>' . $app_strings['LBL_USER'] . '</option>
					<option value="T" ' . $select_group . '>' . $app_strings['LBL_GROUP'] . '</option>
					</select>';
	} else {
		if ($toggletype == 'task') $htmlStr1 .= '<input type="hidden" name="task_assigntype" value="U">';
		else $htmlStr1 .= '<input type="hidden" name="assigntype" value="U">';
	}

	$fldvalue = getUserslist(true, true);
	foreach ($fldvalue as $key_one => $arr) {
		foreach ($arr as $sel_value => $value) {
			if ($value == 'selected') {
				$fld_value = $key_one;
				$fld_displayvalue = $sel_value;
			}
		}
	}
	$div_style = 'class="dvtCellInfoOff"';
	$fld_style = 'class="detailedViewTextBox" readonly';
	if (trim($fld_displayvalue) == '') {
		$fld_displayvalue = getTranslatedString('LBL_SEARCH_STRING');
		$div_style = 'class="dvtCellInfo"';
		$fld_style = 'class="detailedViewTextBox"';
	}
	if ($toggletype == 'task') {
		$htmlStr2 .= '<div id="task_assign_user" ' . $div_style . ' style="' . $style_user . '">';
		$fldname = "task_assigned_user_id";
		$form = 'document.createTodo';
		$form_name = 'document.forms.createTodo';
	} else {
		$htmlStr2 .= '<div id="assign_user" ' . $div_style . ' style="' . $style_user . '">';
		$fldname = "assigned_user_id";
		$form_name = 'document.forms.EditView';
	}
	$htmlStr2 .= '<input id="' . $fldname . '" name="' . $fldname . '" type="hidden" value="' . $fld_value . '">';
	$htmlStr2 .= '<input id="' . $fldname . '_display" name="' . $fldname . '_display" type="text" value="' . $fld_displayvalue . '" ' . $fld_style . '>
	<script type="text/javascript">';
	if ($form != '') $htmlStr2 .= 'initAutocompleteUG("Users","' . $fldname . '","' . $fldname . '_display",\'' . str_replace("'", "&#39;", Zend_Json::encode($fldvalue)) . '\',undefined,' . $form . ');';
	else $htmlStr2 .= 'initAutocompleteUG("Users","' . $fldname . '","' . $fldname . '_display",\'' . str_replace("'", "&#39;", Zend_Json::encode($fldvalue)) . '\');';
	$htmlStr2 .= '</script>
		<div class="dvtCellInfoImgRx">
			<i class="vteicon" onclick="toggleAutocompleteList(\'' . $fldname . '_display\');" style="cursor:hand;cursor:pointer">view_list</i>
			<i class="vteicon" onClick="' . $form_name . '.' . $fldname . '.value=\'\'; ' . $form_name . '.' . $fldname . '_display.value=\'\'; enableReferenceField(' . $form_name . '.' . $fldname . '_display); return false;" style="cursor:hand;cursor:pointer">highlight_off</i>
		</div>
	</div>';

	if ($grouplist != '') {
		$secondvalue = getGroupslist(true);
		foreach ($secondvalue as $key_one => $arr) {
			foreach ($arr as $sel_value => $value) {
				if ($value == 'selected') {
					$fld_secondvalue = $key_one;
					$fld_displaysecondvalue = $sel_value;
				}
			}
		}
		$div_style = 'class="dvtCellInfoOff"';
		$fld_style = 'class="detailedViewTextBox" readonly';
		if (trim($fld_displaysecondvalue) == '') {
			$fld_displaysecondvalue = getTranslatedString('LBL_SEARCH_STRING');
			$div_style = 'class="dvtCellInfo"';
			$fld_style = 'class="detailedViewTextBox"';
		}
		if ($toggletype == 'task') {
			$htmlStr2 .= '<div id="task_assign_team" ' . $div_style . ' style="' . $style_group . '">';
			$fldname = "task_assigned_group_id";
			$form = 'document.createTodo';
			$form_name = 'document.forms.createTodo';
		} else {
			$htmlStr2 .= '<div id="assign_team" ' . $div_style . ' style="' . $style_group . '">';
			$fldname = "assigned_group_id";
			$form_name = 'document.forms.EditView';
		}
		$htmlStr2 .= '<input id="' . $fldname . '" name="' . $fldname . '" type="hidden" value="' . $fld_secondvalue . '">';
		$htmlStr2 .= '<input id="' . $fldname . '_display" name="' . $fldname . '_display" type="text" value="' . $fld_displaysecondvalue . '" ' . $fld_style . '>
		<script type="text/javascript">';
		if ($form != '') $htmlStr2 .= 'initAutocompleteUG("Groups","' . $fldname . '","' . $fldname . '_display",\'' . Zend_Json::encode($secondvalue) . '\',undefined,' . $form . ');';
		else $htmlStr2 .= 'initAutocompleteUG("Groups","' . $fldname . '","' . $fldname . '_display",\'' . Zend_Json::encode($secondvalue) . '\');';
		$htmlStr2 .= '</script>
			<div class="dvtCellInfoImgRx">
				<i class="vteicon" onclick="toggleAutocompleteList(\'' . $fldname . '_display\');" style="cursor:hand;cursor:pointer">view_list</i>
				<i class="vteicon" onClick="' . $form_name . '.' . $fldname . '.value=\'\'; ' . $form_name . '.' . $fldname . '_display.value=\'\'; enableReferenceField(' . $form_name . '.' . $fldname . '_display); return false;" style="cursor:hand;cursor:pointer">highlight_off</i>
			</div>
		</div>';
	}  // crmv@32334
	else {
		$div_style = 'class="dvtCellInfoOff"';
		$fld_style = 'class="detailedViewTextBox" readonly';
		if (trim($fld_displaysecondvalue) == '') {
			$fld_displaysecondvalue = getTranslatedString('LBL_SEARCH_STRING');
			$div_style = 'class="dvtCellInfo"';
			$fld_style = 'class="detailedViewTextBox"';
		}
		if ($toggletype == 'task') {
			$htmlStr2 .= '<div id="task_assign_team" ' . $div_style . ' style="' . $style_group . '">';
			$fldname = "task_assigned_group_id";
			$form = 'document.createTodo';
		} else {
			$htmlStr2 .= '<div id="assign_team" ' . $div_style . ' style="' . $style_group . '">';
			$fldname = "assigned_group_id";
		}
		$htmlStr2 .= '<input id="' . $fldname . '" name="' . $fldname . '" type="hidden" value="">';
		$htmlStr2 .= '<input id="' . $fldname . '_display" name="' . $fldname . '_display" type="text" value="' . $fld_displaysecondvalue . '" ' . $fld_style . '>
		</div>';
	}
	// crmv@32334 e
	return array($htmlStr1, $htmlStr2);
}
// crmv@31171e // crmv@98866 end

?>