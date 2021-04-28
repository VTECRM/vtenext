<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@182114 */

namespace VteSyncLib\Connector\SalesForce\Model;

class Potential extends GenericSFRecord {

	protected static $staticModule = 'Potentials';
	
	protected static $fieldMap = array(
		// SF => CommonRecord
		'Name' => 'name',
		'AccountId' => 'related_to',
		'CampaignId' => 'campaignid',
		'StageName' => 'sales_stage',
		'CloseDate' => 'closingdate',
		'Amount' => 'amount',
		'Probability' => 'probability',
		'Type' => 'type',
		'NextStep' => 'nextstep',
		'Description' => 'description',
	);

	// if needed, you can override methods and change fields/behaviour
}