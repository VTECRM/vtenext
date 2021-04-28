/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
var imgdir = 'modules/SDK/examples/uitypeSocial/img/';
getObj(dtlView).innerHTML = "<img src=\""+resourcever(imgdir+"liico.png")+"\" align=\"left\" alt=\"Linkedin\" title=\"Linkedin\"/>";
if (tagValue != '') {
  getObj(dtlView).innerHTML += "<a target=\"_blank\" href=\"http://www.linkedin.com/in/"+tagValue+"\">http://www.linkedin.com/in/"+tagValue+"</a>";
}