<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@182114 */

namespace VteSyncLib\Connector\SalesForce\Model;

class Asset extends GenericSFRecord {

	protected static $staticModule = 'Assets';
	
	protected static $fieldMap = array(
		// SF => CommonRecord
		'Name' => 'name',
		'AccountId' => 'accountid',
		'Product2Id' => 'productid',
		'SerialNumber' => 'serial_number',
		'InstallDate' => 'install_date',
		'PurchaseDate' => 'purchase_date',
		'Status' => 'status',
		'Description' => 'description',
	);

	// if needed, you can override methods and change fields/behaviour
}