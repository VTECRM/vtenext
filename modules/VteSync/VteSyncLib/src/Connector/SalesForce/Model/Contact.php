<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Connector\SalesForce\Model;

class Contact extends GenericSFRecord {

	protected static $staticModule = 'Contacts';
	
	protected static $fieldMap = array(
		// SF => CommonRecord
		'Salutation' => 'salutationtype',
		'LastName' => 'lastname',
		'FirstName' => 'firstname',
		'AccountId' => 'accountid',
		'Phone' => 'phone',
		'Fax' => 'fax',
		'MobilePhone' => 'mobile',
		'HomePhone' => 'homephone',
		'OtherPhone' => 'otherphone',
		'AssistantPhone' => 'assistantphone',
		'Email' => 'email',
		'Title' => 'title',
		'Department' => 'department',
		'AssistantName' => 'assistant',
		'Birthdate' => 'birthday',
		'LeadSource' => 'leadsource',
		'Description' => 'description',
		
		// mailing address
		'MailingStreet' => 'mailingstreet',
		'MailingCity' => 'mailingcity',
		'MailingPostalCode' => 'mailingpostalcode',
		'MailingState' => 'mailingstate',
		'MailingCountry' => 'mailingcountry',
		// other address
		'OtherStreet' => 'otherstreet',
		'OtherCity' => 'othercity',
		'OtherPostalCode' => 'otherpostalcode',
		'OtherState' => 'otherstate',
		'OtherCountry' => 'othercountry',
	);

	// if needed, you can override methods and change fields/behaviour
}

/*
<pre>Array
(
    [AccountId] => 0011i000004KjO0AAK -> TODO how to handle ? 
    [ReportsToId] =>  // TODO

*/