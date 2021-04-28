<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@173271 */


class ProductsModule extends PortalModule {

	public $list_function = 'get_product_list_values';
	
	protected function processListResult($result) {
		return getblock_fieldlistview_product($result,$this->moduleName);
	}
}
 