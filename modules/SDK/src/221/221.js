/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
var notaccess = document.getElementById(txtBox);
displayValue = notaccess.options[notaccess.selectedIndex].text;
getObj(dtlView).innerHTML = "<a href=\"index.php?module=Settings&action=RoleDetailView&parenttab=Settings&roleid="+tagValue+"\">"+displayValue+"</a>";