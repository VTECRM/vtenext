<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Connector\VTE\Model;


class Lead extends GenericVTERecord {

	protected static $staticModule = 'Leads';
	
	protected static $fieldMap = array(
		// VTE => CommonRecord
		'designation' => 'title',
		'salutationtype' => 'salutation',	// TODO: sync picklist?
		'lastname' => 'lastname',
		'firstname' => 'firstname',
		'company' => 'company',
		'email' => 'email',
		'phone' => 'phone',
		'mobile' => 'mobile',
		'fax' => 'fax',
		'leadstatus' => 'status',
		'website' => 'website',
		'leadsource' => 'source',
		'industry' => 'industry',
		'rating' => 'rating',
		'annualrevenue' => 'revenue',
		'noofemployees' => 'employees',
		'description' => 'description',
		// address
		'lane' => 'street',
		'city' => 'city',
		'code' => 'postalcode',
		'state' => 'state',
		'country' => 'country',
	);

	// if needed, you can override methods and change fields/behaviour	
}