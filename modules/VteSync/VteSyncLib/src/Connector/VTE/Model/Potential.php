<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@182114 */

namespace VteSyncLib\Connector\VTE\Model;

class Potential extends GenericVTERecord {

	protected static $staticModule = 'Potentials';
	
	protected static $fieldMap = array(
		// VTE => CommonRecord
		'potentialname' => 'name',
		'related_to' => 'related_to',
		'campaignid' => 'campaignid',
		'sales_stage' => 'sales_stage',
		'closingdate' => 'closingdate',
		'amount' => 'amount',
		'probability' => 'probability',
		'opportunity_type' => 'type',
		'nextstep' => 'nextstep',
		'description' => 'description',
	);

	// if needed, you can override methods and change fields/behaviour	
}