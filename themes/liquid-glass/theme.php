<?php
/*************************************
 * SPDX-FileCopyrightText: 2026 Vtenext S.r.l. <info@vtenext.com>
 * SPDX-License-Identifier: AGPL-3.0-only
 ************************************/

require_once('include/BaseClasses.php');

class ThemeConfig extends OptionableClass {

	protected $options = array(
		'handle_contestual_buttons' => true,
		'lateral_left_menu' => true,
		'lateral_right_menu' => true,
	);

}
