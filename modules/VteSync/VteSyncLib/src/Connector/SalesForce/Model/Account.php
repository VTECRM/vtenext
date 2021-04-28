<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Connector\SalesForce\Model;

class Account extends GenericSFRecord {

	protected static $staticModule = 'Accounts';
	
	protected static $fieldMap = array(
		// SF => CommonRecord
		'Name' => 'name',
		'Phone' => 'phone',
		'Fax' => 'fax',
		'Website' => 'website',
		'Industry' => 'industry',
		'AnnualRevenue' => 'annualrevenue',
		'NumberOfEmployees' => 'employees',
		'Rating' => 'rating',
		'Description' => 'description',
		
		// billing address
		'BillingStreet' => 'billingstreet',
		'BillingCity' => 'billingcity',
		'BillingPostalCode' => 'billingpostalcode',
		'BillingState' => 'billingstate',
		'BillingCountry' => 'billingcountry',
		// shipping address
		'ShippingStreet' => 'shippingstreet',
		'ShippingCity' => 'shippingcity',
		'ShippingPostalCode' => 'shippingpostalcode',
		'ShippingState' => 'shippingstate',
		'ShippingCountry' => 'shippingcountry',
	);

	// if needed, you can override methods and change fields/behaviour
}

/*
<pre>Array
(


    [AccountNumber] => CC978213
    [PhotoUrl] => /services/images/photo/0011i000004KjOAAA0
    [Sic] => 3712
    [Ownership] => Private
    [TickerSymbol] => 

    [Site] => 
    
    [CleanStatus] => Pending
    [AccountSource] => 
    [DunsNumber] => 
    [Tradestyle] => 
    [NaicsCode] => 
    [NaicsDesc] => 
    [YearStarted] => 
    [SicDesc] => 
    [DandbCompanyId] => 
    [CustomerPriority__c] => Low
    [SLA__c] => Bronze
    [Active__c] => Yes
    [NumberofLocations__c] => 1
    [UpsellOpportunity__c] => Yes
    [SLASerialNumber__c] => 7324
    [SLAExpirationDate__c] => 2019-09-15
)
*/