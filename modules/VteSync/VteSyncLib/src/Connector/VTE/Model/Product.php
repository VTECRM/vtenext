<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@182114 */

namespace VteSyncLib\Connector\VTE\Model;

class Product extends GenericVTERecord {

	protected static $staticModule = 'Products';
	
	protected static $fieldMap = array(
		// VTE => CommonRecord
		'productname' => 'name',
		'productcode' => 'code',
		'discontinued' => 'is_active',
		'productcategory' => 'category',
		'description' => 'description',
	);

	public static function fromRawData($data) {
		$data['discontinued'] = ($data['discontinued'] == '1');
		return parent::fromRawData($data);
	}
}