<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
if (isset($_REQUEST['day']) && $_REQUEST['day'] != '') {
	VteSession::set('CheckAvailableVersion'.$_REQUEST['day'], true); // crmv@128133
} else {
	VteSession::set('CheckAvailableVersion', true);
}
?>