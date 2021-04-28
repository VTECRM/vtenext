/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//crmv@182677
function mypopup(params) {
	if (params != undefined) {
		var sessionValidatorCheck = true;
		var url = "copyright.php"+params;
	} else {
		var sessionValidatorCheck = false;
		var url = "copyright.php";
	}
	mywindow = openPopup(url,"mywindow","width=900, height=400",'',900,400,'','',sessionValidatorCheck);//crmv@22106
}
//crmv@182677e

function newpopup(str) {
	openPopup(str,"mywinw","menubar=1,resizable=1,scrollbars=yes");//crmv@22106
}