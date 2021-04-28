<?php 
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@152713

global $theme, $default_theme;

if (empty($theme)) $theme = $default_theme;

$TU = ThemeUtils::getInstance($theme);

if ($TU->shouldChangeLoginBackground()) {
	$nextLoginBackgroundImage = $TU->getNextLoginBackgroundImage();
	$TU->setLoginBackgroundImage($nextLoginBackgroundImage);
}