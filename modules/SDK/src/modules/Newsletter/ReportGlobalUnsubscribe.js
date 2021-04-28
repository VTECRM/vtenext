/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
/* crmv@55961 */
function FilterUnsubReport(id) {
	var params = "";
	var filterbox = getObj('filterbox');
	if (filterbox) {
		params += "&filterbox="+filterbox.value;
	}

	return params;
}