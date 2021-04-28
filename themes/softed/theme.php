<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@119414

require_once('include/BaseClasses.php');

class ThemeConfig extends OptionableClass {
	
	protected $options = array(
		'handle_contestual_buttons' => false,
		'lateral_left_menu' => false,
		'lateral_right_menu' => false,
	);
	
}