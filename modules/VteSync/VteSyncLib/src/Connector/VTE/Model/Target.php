<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@182114 */

namespace VteSyncLib\Connector\VTE\Model;

class Target extends GenericVTERecord {

	protected static $staticModule = 'Targets';
	
	protected static $fieldMap = array(
		// VTE => CommonRecord
		'targetname' => 'name',	
	);

	// if needed, you can override methods and change fields/behaviour	
}