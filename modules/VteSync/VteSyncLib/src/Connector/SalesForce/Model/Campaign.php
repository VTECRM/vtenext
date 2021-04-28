<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@182114 */

namespace VteSyncLib\Connector\SalesForce\Model;

class Campaign extends GenericSFRecord {

	protected static $staticModule = 'Campaigns';
	
	protected static $fieldMap = array(
		// SF => CommonRecord
		'Name' => 'name',
		'Type' => 'type',
		'Status' => 'status',
		'EndDate' => 'end_date',
		'BudgetedCost' => 'budgetcost',
		'ActualCost' => 'actualcost',
		'ExpectedRevenue' => 'expectedrevenue',
		'NumberSent' => 'numsent',
		'NumberOfResponses' => 'actualresponsecount',
		'Description' => 'description',
	);

	// if needed, you can override methods and change fields/behaviour
}