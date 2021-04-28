/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
var imgdir = 'modules/SDK/examples/uitypeSocial/img/';
getObj(dtlView).innerHTM = "<img src=\""+resourcever(imgdir+"ytico.png")+"\" align=\"left\" alt=\"YouTube\" title=\"YouTube\"/>";
if (tagValue != '') {
  getObj(dtlView).innerHTML += "<a target=\"_blank\" href=\"http://www.youtube.com/user/"+tagValue+"\">http://www.youtube.com/user/"+tagValue+"</a>";
}