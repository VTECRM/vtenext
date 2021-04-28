<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@119414

require_once('include/BaseClasses.php');

class ThemeConfig extends OptionableClass {
	
	protected $options = array(
		'handle_contestual_buttons' => true, // Use this flag if the theme uses contextual buttons (theme without header buttons) // crmv@190519
		'lateral_left_menu' => true,
		'lateral_right_menu' => true,
	);
	
}