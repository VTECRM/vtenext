<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

/* crmv@192078 */

class LoginWhiteList {

	public function process(&$request) {
		global $adb, $table_prefix;
		
		$mailkey = vtlib_purify($request['k']);
		
		$error = false;
		if (empty($mailkey)) {
			$error = true;
		} else {
			$focus = CRMEntity::getInstance('Users');
			$result = $adb->pquery("SELECT * FROM {$focus->track_login_table} WHERE mailkey = ?",array($mailkey));
			if ($result && $adb->num_rows($result) > 0) {
				$id = $adb->query_result_no_html($result,0,'id');
				$ip = $adb->query_result_no_html($result,0,'ip');
				$userid = $adb->query_result_no_html($result,0,'userid');
				$status = $adb->query_result_no_html($result,0,'status');
				if ($status == 'W') {
					$error = true;
				}
			} else {
				$error = true;
			}
		}
		
		if ($error) {
			$this->displayForbidden();
		} else {
			$this->setWhiteList($id, $userid);
			$this->displayResult($ip);
		}
		
	}
	
	public function displayForbidden() {
		header('HTTP/1.0 403 Forbidden');
		include('modules/Users/403error.html');
	}
	
	public function setWhiteList($id, $userid) {
		global $adb, $table_prefix;
		
		$focus = CRMEntity::getInstance('Users');
		
		$result = $adb->pquery("select default_language from {$table_prefix}_users where id = ?",array($userid));
		if ($result && $adb->num_rows($result) > 0) {
			$language = $adb->query_result($result,0,'default_language');
			if (!empty($language)) $current_language = $language;
		}
		
		$adb->pquery("UPDATE {$focus->track_login_table} SET status = ?, date_whitelist = ? WHERE id = ?",array('W',date('Y-m-d H:i:s'),$id));
	}
	
	public function displayResult($ip) {
		global $default_charset, $current_language, $default_language;
		
		
		$description = '
		<table border="0" cellpadding="20" cellspacing="0" width="100%" align="center" class="small">
		<tr><td colspan="2">'.sprintf(getTranslatedString('LBL_LOCKED_LOGIN_RESTORED','Users'),$ip).'</td></tr>
		</table>';
		
		header('Content-Type: text/html; charset='. $default_charset);
		$smarty = new VteSmarty();
		$smarty->assign('PATH','../');
		$smarty->assign('CURRENT_LANGUAGE',$current_language ?: $default_language);
		$smarty->assign('BODY',$description);
		$smarty->display('NoLoginMsg.tpl');
	}
	
}