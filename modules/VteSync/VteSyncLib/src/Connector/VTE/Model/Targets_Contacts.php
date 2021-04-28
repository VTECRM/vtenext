<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@195073*/

namespace VteSyncLib\Connector\VTE\Model;

class Targets_Contacts extends GenericVTERecord {

	protected static $staticModule = 'Targets_Contacts';
	
	protected static $fieldMap = array(
		'targetid' => 'targetid',
		'contactid' => 'contactid',
	);

	// if needed, you can override methods and change fields/behaviour
}