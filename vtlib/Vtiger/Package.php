<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
require_once('vtlib/Vtiger/PackageUpdate.php');

/**
 * Package Manager class for vtiger Modules.
 * @package vtlib
 */
class Vtiger_Package extends Vtiger_PackageUpdate {

	/**
	 * Constructor
	 */
	function __construct() {
		parent::__construct();
	}
}