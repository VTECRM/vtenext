/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@82831 */
/* Some Bootstrap plugins, conflicts with other plugins (eg: dropdown)
 * so they need to be changed
 */

if (window.jQuery && typeof jQuery().dropdown == 'function') {
	// rename dropdown
	jQuery.fn.bsDropdown = jQuery.fn.dropdown;
	delete jQuery.fn.dropdown;
}