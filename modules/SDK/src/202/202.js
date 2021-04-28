/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
 var notaccess =document.getElementById(txtBox);
tagValue = notaccess.options[notaccess.selectedIndex].text;
if(tagValue == alert_arr.LBL_NOT_ACCESSIBLE)
	getObj(dtlView).innerHTML = "<font color='red'>"+get_converted_html(tagValue)+"</font>";
else
	getObj(dtlView).innerHTML = get_converted_html(tagValue);