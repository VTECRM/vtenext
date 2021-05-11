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

/* crmv@130421 */
vtlib_toggleModuleAccess('Morphsuit',false);

$user_info = Zend_Json::decode($_REQUEST['user_info']);

$new_password = $user_info['password'];
$user = CRMEntity::getInstance('Users');
$user->retrieve_entity_info(1,'Users');
$user_hash = strtolower(md5($new_password));
$crypt_type = $user->DEFAULT_PASSWORD_CRYPT_TYPE;
$encrypted_new_password = $user->encrypt_password($new_password, $crypt_type);

// modifiche utente
$res = $adb->pquery("update {$table_prefix}_users set user_name = ?, last_name = ?, first_name = ?, email1 = ?, user_password = ?, confirm_password = ?, user_hash=?, crypt_type=? where id = 1", array($user_info['username'], $user_info['last_name'], $user_info['first_name'], $user_info['email'], $encrypted_new_password, $encrypted_new_password, $user_hash, $crypt_type));
if ($adb->getAffectedRowCount($res) <= 0) die('Error updating admin user');
if ($adb->isOracle()) {
	$adb->pquery("UPDATE {$table_prefix}_check_pwd SET last_login = NULL, last_change_pwd = ? WHERE userid = ?", array(date('Y-m-d H:i:s'),1));
} else {
	$adb->pquery("UPDATE {$table_prefix}_check_pwd SET last_login = ?, last_change_pwd = ? WHERE userid = ?", array('0000-00-00 00:00:00',date('Y-m-d H:i:s'),1));
}

// file dei privilegi
$priv_file = $root_directory.'user_privileges/user_privileges_1.php';
$userfile = file_get_contents($priv_file);
$userfile = preg_replace("/'user_password'\s*=>\s*[^,]+,/", "'user_password'=>'{$encrypted_new_password}',", $userfile);
$userfile = preg_replace("/'confirm_password'\s*=>\s*[^,]+,/", "'confirm_password'=>'{$encrypted_new_password}',", $userfile);
$userfile = preg_replace("/'user_hash'\s*=>\s*[^,]+,/", "'user_hash'=>'{$user_hash}',", $userfile);
$userfile = preg_replace("/'last_name'\s*=>\s*[^,]+,/", "'last_name'=>'{$user_info['last_name']}',", $userfile);
$userfile = preg_replace("/'first_name'\s*=>\s*[^,]+,/", "'first_name'=>'{$user_info['first_name']}',", $userfile);
$userfile = preg_replace("/'email1'\s*=>\s*[^,]+,/", "'email1'=>'{$user_info['email']}',", $userfile);
if (!file_put_contents($priv_file, $userfile)) die('Error updating user_privileges file');

die('yes');
