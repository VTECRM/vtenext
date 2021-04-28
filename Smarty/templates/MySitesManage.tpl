{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<table class="small" border="0" cellpadding="0" cellspacing="0" width="100%">
<tr>
	<td colwidth=90% align=left class=small>
		<table border=0 cellspacing=0 cellpadding=5>
		<tr>
			<td align=left><a href="#" onclick="fetchContents('data');"><img src="{'webmail_settings.gif'|resourcever}" align="absmiddle" border=0 /></a></td>
			<td class=small align=left><a href="#" onclick="fetchContents('data');">{$MOD.LBL_MY_SITES}</a></td>
		</tr>
		</table>
			
	</td>
	<td align=right width=10%>
		<table border=0 cellspacing=0 cellpadding=0>
		<tr><td nowrap class="componentName">{$MOD.LBL_MY_SITES}</td></tr>
		</table>
	</td>
</tr>
</table>

<table border="0" cellpadding="5" cellspacing="0" width="100%">
<tr>
<td colspan="3" class="genHeaderSmall" align="left">{$MOD.LBL_MY_BOOKMARKS} <hr></td>
</tr>
<tr>
<td colspan="3" align="left"><input name="bookmark" value=" {$MOD.LBL_NEW_BOOKMARK} " class="crmbutton small create" onclick="fetchAddSite('', this);" type="button"></td>
</tr>
</table>
<table border="0" cellpadding="5" cellspacing="0" width="100%" class="listTable bgwhite"> 
<tr>
<td class="colHeader small" align="left" width="5%"><b>{$MOD.LBL_SNO}</b></td>
<td class="colHeader small" align="left" width="75%"><b>{$MOD.LBL_BOOKMARK_NAME_URL}</b></td>

<td class="colHeader small" align="left" width="20%"><b>{$MOD.LBL_TOOLS}</b></td>
</tr>

{foreach name=portallists item=portaldetails key=sno from=$PORTALS}
<tr><td class="listTableRow small" align="left">{$smarty.foreach.portallists.iteration}</td>
<td class="listTableRow small" align="left">
<b>{$portaldetails.portalname}</b><br>
<span class="big">{$portaldetails.portaldisplayurl}</span>
</td>
<td class="listTableRow small" align="left">
<a href="javascript:;" onclick="fetchAddSite('{$portaldetails.portalid}', this);" class="webMnu">{$APP.LBL_EDIT}</a>&nbsp;|&nbsp;
<a href="javascript:;" onclick="DeleteSite('{$portaldetails.portalid}');"class="webMnu">{$APP.LBL_MASS_DELETE}</a>
</td>
</tr>
{/foreach}
</table>