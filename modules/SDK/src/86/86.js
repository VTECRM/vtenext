/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
var imgdir = 'modules/SDK/src/86/img/';
getObj(dtlView).innerHTML = "<img src=\""+resourcever(imgdir+"waico.png")+"\" align=\"left\" alt=\"Whatsapp\" title=\"Whatsapp\"/>";
if (tagValue != '') {
  getObj(dtlView).innerHTML += "<a target=\"_blank\" href=\"https://wa.me/39"+tagValue+"\">https://wa.me/39"+tagValue+"</a>";
}