<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
function js2PhpTime($jsdate){
  if(preg_match('@(\d+)/(\d+)/(\d+)\s+(\d+):(\d+)@', $jsdate, $matches)==1){
    $ret = mktime($matches[4], $matches[5], 0, $matches[1], $matches[2], $matches[3]);
    //echo $matches[4] ."-". $matches[5] ."-". 0  ."-". $matches[1] ."-". $matches[2] ."-". $matches[3];
  }else if(preg_match('@(\d+)/(\d+)/(\d+)@', $jsdate, $matches)==1){
    $ret = mktime(0, 0, 0, $matches[1], $matches[2], $matches[3]);
    //echo 0 ."-". 0 ."-". 0 ."-". $matches[1] ."-". $matches[2] ."-". $matches[3];
  }
  return $ret;
}

function php2JsTime($phpDate){
    //echo $phpDate;
    //return "/Date(" . $phpDate*1000 . ")/";
    return date("m/d/Y H:i", $phpDate);
}

function php2MySqlTime($phpDate){
    return date("Y-m-d H:i:s", $phpDate);
}

function mySql2PhpTime($sqlDate){
    $arr = date_parse($sqlDate);
    return mktime($arr["hour"],$arr["minute"],$arr["second"],$arr["month"],$arr["day"],$arr["year"]);
}

//crmv@20324	//crmv@20628
function getInvitedIcon($ownerId,$activityid) {
	global $adb,$current_user,$table_prefix;
	$query = "select activitytype from ".$table_prefix."_activity where activityid=?";
	$result = $adb->pquery($query, array($activityid));
	$actType = $adb->query_result($result,0,'activitytype');
	if($actType == 'Task') {
		$icon = "<i class=\"vteicon nohover colorinh align-middle md-sm\">event_note</i>";
	}
	else {
		$isInvited = isCalendarInvited($current_user->id,$activityid);
		if ($isInvited[0] == 'yes') {
			$eventType = strtolower($isInvited[2]);
			if ($eventType != 'meeting' && $eventType != 'call') {
				$eventType = 'default';
			}
			$iconName = '';
			switch ($eventType) {
				case 'meeting':
					switch ($isInvited[1]) {
						case 'yes':
							$iconName = 'people';
							break;
						case 'no':
							$iconName = 'not_interested';
							break;
						default:
							$iconName = 'schedule';
							break;
					}
				break;
				case 'call':
					switch ($isInvited[1]) {
						case 'yes':
							$iconName = 'phone';
							break;
						case 'no':
							$iconName = 'not_interested';
							break;
						default:
							$iconName = 'schedule';
							break;
					}
				break;
				default:
					switch ($isInvited[1]) {
						case 'yes':
							$iconName = 'done';
							break;
						case 'no':
							$iconName = 'not_interested';
							break;
						default:
							$iconName = 'schedule';
							break;
					}
				break;
			}
			$icon = "<i class=\"vteicon nohover colorinh align-middle md-sm\">{$iconName}</i>";
		}
		else {
			$icon = '';
		}
	}
	return $icon;
}
//crm@20324e	//crmv@20628e
?>