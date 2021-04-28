/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@37679 */
// called after ajax save

if (dtlView) {
	if (tagValue) tagValue = tagValue.replace(/&amp;/g, '&').replace(/\n/g, "<br>"); // crmv@81167
	getObj(dtlView).innerHTML = tagValue;
}