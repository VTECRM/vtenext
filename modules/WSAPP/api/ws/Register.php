<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once 'modules/WSAPP/SyncServer.php';

function wsapp_register ($type,$syncType, $user) {
	$instance = new SyncServer();
	return $instance->register($type,$syncType,$user);
}