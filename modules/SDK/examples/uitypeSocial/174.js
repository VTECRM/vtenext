/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
var imgdir = 'modules/SDK/examples/uitypeSocial/img/';
getObj(dtlView).innerHTML = "<img src=\""+resourcever(imgdir+"twico.png")."\" align=\"left\" alt=\"Twitter\" title=\"Twitter\"/>";
if (tagValue != '') {
  getObj(dtlView).innerHTML += "<a target=\"_blank\" href=\"http://twitter.com/"+tagValue+"\">http://twitter.com/"+tagValue+"</a>";
}