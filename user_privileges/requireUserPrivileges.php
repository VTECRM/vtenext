<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@39110 */
/* include the user_privileges or the user_privileges_m if calling from mobile
 * use this file whenever mobile profile loading is required
 * uses the $userid variable if set, otherwise try with current user */

global $current_user;

if (isset($userid) && $userid > 0) {
	$upriv_userid = intval($userid);
} elseif (isset($current_user) && $current_user->id > 0) {
	$upriv_userid = $current_user->id;
} else {
	// since it's a require, fail horribly
	die('No valid user ID found.');
}

if ($upriv_userid > 0) {
	$upriv_basedir = dirname(__FILE__);
	if (is_readable($upriv_basedir.'/user_privileges_m_'.$upriv_userid.'.php') && function_exists('requestFromMobile') && requestFromMobile()) {
		$upriv_file = $upriv_basedir.'/user_privileges_m_'.$upriv_userid.'.php';
	} else {
		$upriv_file = $upriv_basedir.'/user_privileges_'.$upriv_userid.'.php';
	}
	unset($upriv_basedir, $upriv_userid);
	require($upriv_file);
}
?>