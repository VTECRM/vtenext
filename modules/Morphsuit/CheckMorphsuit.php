<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
// crmv@171581
if (!isset($root_directory)) {
	require_once('../../config.inc.php');
	chdir($root_directory);
}
require_once('include/utils/utils.php'); 
// crmv@171581e

//crmv@35153
$installation_mode = false;
if (empty($_SESSION)) {
	VteSession::start();
}
if (VteSession::get('morph_mode') == 'installation') {
	$installation_mode = true;
}
//crmv@35153e

// first activation
$first_activation = false;
$saved_morphsuit = getSavedMorphsuit();
if (empty($saved_morphsuit)) {
	$first_activation = true;
}

global $adb, $table_prefix, $application_unique_key, $default_language;
$chiave = $_REQUEST['valida_chiave'];

if (isMorphsuitActive($chiave)) {
	
	$adb->query('delete from tbl_s_morphsuit');
	$adb->pquery('insert into tbl_s_morphsuit (morphsuit) values (?)',array($chiave));
	
	//crmv@35153
	if (isFreeVersion() && file_exists('modules/Update/free_changes')) {
		$limits = array(
			'numero_utenti'=>0,
			'roles'=>3,	//Organisation + 2
			'profiles'=>2,
			'pdf'=>1,
			'adv_sharing_rules'=>1,
			'sharing_rules_user'=>1,
		);
		
		$result = $adb->query("select * from {$table_prefix}_role");
		if ($result && $adb->num_rows($result) > 0) {
			if ($adb->num_rows($result) > $limits['roles']) {
				$limits['roles'] = $adb->num_rows($result);
			}
		}
		
		$result = $adb->query("select * from {$table_prefix}_profile");
		if ($result && $adb->num_rows($result) > 0) {
			if ($adb->num_rows($result) > $limits['profiles']) {
				$limits['profiles'] = $adb->num_rows($result);
			}
		}
		
		$result = $adb->query("SELECT COUNT(*) as count FROM {$table_prefix}_pdfmaker GROUP BY module");
		if ($result && $adb->num_rows($result) > 0) {
			$count = array();
			while($row=$adb->fetchByAssoc($result)) {
				$count[] = $row['count'];
			}
			if (!empty($count) && max($count) > $limits['pdf']) {
				$limits['pdf'] = max($count);
			}
		}
		
		$othermodules = getSharingModuleList();
		if(!empty($othermodules)) {
			$count = array();
			foreach($othermodules as $moduleresname) {
				$tmp = getAdvSharingRuleList($moduleresname);
				$count[] = count($tmp);
			}
			if (!empty($count) && max($count) > $limits['adv_sharing_rules']) {
				$limits['adv_sharing_rules'] = max($count);
			}
		}
					
		$othermodules = getSharingModuleList(Array('Contacts'));
		if(!empty($othermodules)) {
			$result = $adb->query("SELECT id FROM {$table_prefix}_users WHERE status = 'Active' AND user_name <> 'admin'");
			if ($result) {
				$count = array();
				while($row=$adb->fetchByAssoc($result)) {
					foreach($othermodules as $moduleresname) {
						$tmp = getSharingRuleListUser($moduleresname,$row['id']);
						$count[] = count($tmp);
					}
				}
				if (!empty($count) && max($count) > $limits['sharing_rules_user']) {
					$limits['sharing_rules_user'] = max($count);
				}
			}
		}
		
		$saved_morphsuit = $chiave;
		$saved_morphsuit = urldecode(trim($saved_morphsuit));
		$private_key = substr($saved_morphsuit,0,strpos($saved_morphsuit,'-----'));
		$enc_text = substr($saved_morphsuit,strpos($saved_morphsuit,'-----')+5);
		$saved_morphsuit = @decrypt_morphsuit($private_key,$enc_text);
		$saved_morphsuit = Zend_Json::decode($saved_morphsuit);
		foreach($limits as $key => $limit) {
			$saved_morphsuit[$key] = $limit;
		}
		$new_key = generate_key_pair_morphsuit();
		$new_enc_text = encrypt_morphsuit($new_key['public_key'],Zend_Json::encode($saved_morphsuit));
		$chiave = urlencode($new_key['private_key']."-----$new_enc_text");
		
		$adb->query('delete from tbl_s_morphsuit');
		$adb->pquery('insert into tbl_s_morphsuit (morphsuit) values (?)',array($chiave));
	}
	//crmv@35153e
	
	itIsTimeToCheck('clear');

	if (checkUsersMorphsuit()) {
		
		//reset expiration date and zombie mode
		VteSession::remove("checkDataMorphsuit");
		VteSession::remove('alertDataMorphsuit');
		VteSession::remove('MorphsuitZombie');

		//crmv@35153
		if (isFreeVersion() && !empty($_REQUEST['user_info'])) {
			
			$user_info = Zend_Json::decode($_REQUEST['user_info']);
			
			$new_password = $user_info['password'];
			$user = CRMEntity::getInstance('Users');
			$user->retrieve_entity_info(1,'Users');
			$user->column_fields["user_name"] = $_REQUEST['user_name']; //crmv@61502
			$user_hash = strtolower(md5($new_password));
			$crypt_type = $user->DEFAULT_PASSWORD_CRYPT_TYPE;
			$encrypted_new_password = $user->encrypt_password($new_password, $crypt_type);
			
			// modifiche utente
			$query = "update {$table_prefix}_users set user_name = ?, last_name = ?, email1 = ?, user_password = ?, confirm_password = ?, user_hash=?, crypt_type=?";
			$params =  array($user_info['username'], $user_info['name'], $user_info['email'], $encrypted_new_password, $encrypted_new_password, $user_hash, $crypt_type);
			$query .= " where id = 1";
			$res = $adb->pquery($query,$params);
			//if ($adb->getAffectedRowCount($res) <= 0) die('Error updating admin user');
			if ($adb->isOracle()) {
				$adb->pquery("UPDATE {$table_prefix}_check_pwd SET last_login = NULL, last_change_pwd = ? WHERE userid = ?", array(date('Y-m-d H:i:s'),1));
			} else {
				$adb->pquery("UPDATE {$table_prefix}_check_pwd SET last_login = ?, last_change_pwd = ? WHERE userid = ?", array('0000-00-00 00:00:00',date('Y-m-d H:i:s'),1));
			}

			// file dei privilegi
			$priv_file = $root_directory.'user_privileges/user_privileges_1.php';
			$userfile = file_get_contents($priv_file);
			$userfile = preg_replace("/'user_name'\s*=>\s*[^,]+,/", "'user_name'=>'{$user_info['username']}',", $userfile);
			$userfile = preg_replace("/'user_password'\s*=>\s*[^,]+,/", "'user_password'=>'{$encrypted_new_password}',", $userfile);
			$userfile = preg_replace("/'confirm_password'\s*=>\s*[^,]+,/", "'confirm_password'=>'{$encrypted_new_password}',", $userfile);
			$userfile = preg_replace("/'user_hash'\s*=>\s*[^,]+,/", "'user_hash'=>'{$user_hash}',", $userfile);
			$userfile = preg_replace("/'last_name'\s*=>\s*[^,]+,/", "'last_name'=>'{$user_info['name']}',", $userfile);
			$userfile = preg_replace("/'email1'\s*=>\s*[^,]+,/", "'email1'=>'{$user_info['email']}',", $userfile);
			if (!file_put_contents($priv_file, $userfile)) die('Error updating user_privileges file');
			
		} elseif ($installation_mode && isset($_REQUEST['user_name'])) {
			
			$new_password = $_REQUEST['user_password'];
			$user = CRMEntity::getInstance('Users');
			$user->retrieve_entity_info(1,'Users');
			$user->column_fields["user_name"] = $_REQUEST['user_name']; //crmv@61502
			$user_hash = strtolower(md5($new_password));
			$crypt_type = $user->DEFAULT_PASSWORD_CRYPT_TYPE;
			$encrypted_new_password = $user->encrypt_password($new_password, $crypt_type);
			
			// modifiche utente
			$query = "update {$table_prefix}_users set user_name = ?, first_name = ?, last_name = ?, email1 = ?, user_password = ?, confirm_password = ?, user_hash=?, crypt_type=?";
			$params = array($_REQUEST['user_name'], $_REQUEST['first_name'], $_REQUEST['last_name'], $_REQUEST['email1'], $encrypted_new_password, $encrypted_new_password, $user_hash, $crypt_type);
			$query .= " where id = 1";
			$res = $adb->pquery($query,$params);
			//if ($adb->getAffectedRowCount($res) <= 0) die('Error updating admin user');
			if ($adb->isOracle() || $adb->isMssql()) { // crmv@155585
				$adb->pquery("UPDATE {$table_prefix}_check_pwd SET last_login = NULL, last_change_pwd = ? WHERE userid = ?", array(date('Y-m-d H:i:s'),1));
			} else {
				$adb->pquery("UPDATE {$table_prefix}_check_pwd SET last_login = ?, last_change_pwd = ? WHERE userid = ?", array('0000-00-00 00:00:00',date('Y-m-d H:i:s'),1));
			}

			// file dei privilegi
			$priv_file = $root_directory.'user_privileges/user_privileges_1.php';
			$userfile = file_get_contents($priv_file);
			$userfile = preg_replace("/'user_name'\s*=>\s*[^,]+,/", "'user_name'=>'{$_REQUEST['user_name']}',", $userfile);
			$userfile = preg_replace("/'user_password'\s*=>\s*[^,]+,/", "'user_password'=>'{$encrypted_new_password}',", $userfile);
			$userfile = preg_replace("/'confirm_password'\s*=>\s*[^,]+,/", "'confirm_password'=>'{$encrypted_new_password}',", $userfile);
			$userfile = preg_replace("/'user_hash'\s*=>\s*[^,]+,/", "'user_hash'=>'{$user_hash}',", $userfile);
			$userfile = preg_replace("/'first_name'\s*=>\s*[^,]+,/", "'first_name'=>'{$_REQUEST['first_name']}',", $userfile);
			$userfile = preg_replace("/'last_name'\s*=>\s*[^,]+,/", "'last_name'=>'{$_REQUEST['last_name']}',", $userfile);
			$userfile = preg_replace("/'email1'\s*=>\s*[^,]+,/", "'email1'=>'{$_REQUEST['email']}',", $userfile);
			if (!file_put_contents($priv_file, $userfile)) die('Error updating user_privileges file');
		}
		if ($installation_mode) {
			//autologin
			VteSession::set("authenticated_user_id", 1);
			VteSession::set("app_unique_key", $application_unique_key);
			VteSession::set('authenticated_user_language', $default_language);
		}
		//crmv@35153e
		
		CRMVUtils::writeCFPrefix(); // crmv@195213
		
		die('yes');
	}
}
die('no');
?>