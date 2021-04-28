/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
var imgdir = 'modules/SDK/examples/uitypeSocial/img/';
getObj(dtlView).innerHTML = "<img src=\""+resourcever(imgdir+"qzico.png")+"\" align=\"left\" alt=\"QZone\" title=\"QZone\"/>";
if (tagValue != '') {
  getObj(dtlView).innerHTML += "<a target=\"_blank\" href=\"http://"+tagValue+".qzone.qq.com\">http://"+tagValue+".qzone.qq.com</a>";
}