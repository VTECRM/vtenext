<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@197423 */

namespace VteSyncLib\Connector\VTE\Model;

class Services extends GenericVTERecord {

	protected static $staticModule = 'Services';
	
	protected static $fieldMap = array(
		'servicename' => 'servicename',
		'servicecategory' => 'servicecategory',
		'qty_per_unit' => 'qty_per_unit',
		'unit_price' => 'unit_price',
		'description' => 'description',
		// VTE => CommonRecord
	);

	// if needed, you can override methods and change fields/behaviour
}
