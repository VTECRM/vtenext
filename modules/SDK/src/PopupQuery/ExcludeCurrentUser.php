<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@42247 */
global $table_prefix, $current_user;
$query .= " and {$table_prefix}_users.id <> ".$current_user->id.' ';
?>