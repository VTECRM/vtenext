/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
var imgdir = 'modules/SDK/examples/uitypeSocial/img/';
getObj(dtlView).innerHTML = "<img src=\""+resourcever(imgdir+"orico.png")+"\" align=\"left\" alt=\"Orkut\" title=\"Orkut\"/>";
if (tagValue != '') {
  getObj(dtlView).innerHTML += "<a target=\"_blank\" href=\"http://www.orkut.com/Main#Profile?uid="+tagValue+"\">http://www.orkut.com/Main#Profile?uid="+tagValue+"</a>";
}