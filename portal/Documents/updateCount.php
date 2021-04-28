<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/*
 * Created on Feb 10, 2009
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 $id = $_REQUEST['file_id'];
 global $client;
 $res = $client->call('updateCount',array($id),$Server_Path,$Server_Path);
 
?>