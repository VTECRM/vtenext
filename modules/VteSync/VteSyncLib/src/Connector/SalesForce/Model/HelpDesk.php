<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@182114 */

namespace VteSyncLib\Connector\SalesForce\Model;

class HelpDesk extends GenericSFRecord {

	protected static $staticModule = 'HelpDesk';
	
	protected static $fieldMap = array(
		// SF => CommonRecord
		'Subject' => 'subject',
		'ContactId' => 'contactid',
		'AccountId' => 'accountid',
		'Status' => 'status',
		'Type' => 'category',
		'Priority' => 'priority',
		'Description' => 'description',
	);

	// if needed, you can override methods and change fields/behaviour
}