<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@182114 */

namespace VteSyncLib\Connector\VTE\Model;

class Campaign extends GenericVTERecord {

	protected static $staticModule = 'Campaigns';
	
	protected static $fieldMap = array(
		// VTE => CommonRecord
		'campaignname' => 'name',
		'campaigntype' => 'type',
		'campaignstatus' => 'status',
		'closingdate' => 'end_date',
		'budgetcost' => 'budgetcost',
		'actualcost' => 'actualcost',
		'expectedrevenue' => 'expectedrevenue',
		'numsent' => 'numsent',
		'actualresponsecount' => 'actualresponsecount',
		'description' => 'description',
	);

	// if needed, you can override methods and change fields/behaviour	
}