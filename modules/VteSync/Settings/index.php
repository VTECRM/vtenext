<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 
/* crmv@176547 */

if ($_REQUEST['ajax'] === '1') {
	require('ajax.php');
} elseif ($_REQUEST['mode'] === 'create') {
	require('Create.php');
} elseif ($_REQUEST['mode'] === 'edit') {
	require('Edit.php');
} elseif ($_REQUEST['mode'] === 'save') {
	require('Save.php');
} else {
	require('List.php');
}