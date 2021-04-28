<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Connector\SalesForce\Model;

class Lead extends GenericSFRecord {

	protected static $staticModule = 'Leads';
	
	protected static $fieldMap = array(
		// SF => CommonRecord
		'Title' => 'title',
		'Salutation' => 'salutation',
		'LastName' => 'lastname',
		'FirstName' => 'firstname',
		'Company' => 'company',
		'Email' => 'email',
		'Phone' => 'phone',
		'MobilePhone' => 'mobile',
		'Fax' => 'fax',
		'Status' => 'status',
		'Website' => 'website',
		'LeadSource' => 'source',
		'Industry' => 'industry',
		'Rating' => 'rating',
		'AnnualRevenue' => 'revenue',
		'NumberOfEmployees' => 'employees',
		'Description' => 'description',
		// address
		'Street' => 'street',
		'City' => 'city',
		'PostalCode' => 'postalcode',
		'State' => 'state',
		'Country' => 'country',
	);

	// if needed, you can override methods and change fields/behaviour
}