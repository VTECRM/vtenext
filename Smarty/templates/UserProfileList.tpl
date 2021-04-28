{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@150592 *}

<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script language="JAVASCRIPT" type="text/javascript" src="include/js/ProfileUtils.js"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody><tr>
	<td valign="top"></td>
	<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->

	<div align=center>
				{include file='SetMenu.tpl'}
				{include file='Buttons_List.tpl'} {* crmv@30683 *}
				<!-- DISPLAY -->
				{* crmv@30683 *}
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
				<tr>
					<td width=50 rowspan=2 valign=top><img src="{'ico-profile.gif'|resourcever}" alt="{$MOD.LBL_PROFILES}" width="48" height="48" border=0 title="{$MOD.LBL_PROFILES}"></td>
					<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} > {$MOD.LBL_PROFILES} </b></td> <!-- crmv@30683 -->
				</tr>
				<tr>
					<td valign=top class="small">{$MOD.LBL_PROFILE_DESCRIPTION}</td>
				</tr>
				</table>
				{* crmv@30683e *}
				<br>
				<table border=0 cellspacing=0 cellpadding=10 width=100% >
				<tr>
				<td valign=top>

					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
					<tr>
						<td class="big"><strong>{$MOD.LBL_PROFILES_LIST}</strong></td>
					</tr>
					</table>

					<table border=0 cellspacing=0 cellpadding=5 width=100% class="listTableTopButtons">
					<tr>
						<td class="small" align="left" width="85%">{$CMOD.LBL_TOTAL} {$COUNT} {$MOD.LBL_PROFILES}</td>
						<td align="right" width="5%" id="profileListVersion" nowrap>
							{include file='UserProfileListVersion.tpl'}
						</td>
						<td class=small align="right" width="10%">
							<form action="index.php" method="post" name="new" id="form" onsubmit="VteJS_DialogBox.block();">
								<input type="hidden" name="module" value="Users">
								<input type="hidden" name="mode" value="create">
								<input type="hidden" name="action" value="CreateProfile">
								<input type="hidden" name="parenttab" value="Settings">
								<input type="submit" value="{$CMOD.LBL_NEW_PROFILE}" title="{$CMOD.LBL_NEW_PROFILE}" class="crmButton create small">
							</form>
						</td>
					</tr>
					</table>

					<table border=0 cellspacing=0 cellpadding=5 width=100% class="listTable">
					<tr>
						<td class="colHeader small" valign="top" width="2%">{$LIST_HEADER.0}</td>
						<td class="colHeader small" valign="top" width="8%"">{$LIST_HEADER.1}</td>
						<td class="colHeader small" valign="top" width="30%">{$LIST_HEADER.2} </td>
						<td class="colHeader small" valign="top" width="50%">{$LIST_HEADER.3}</td>
						<td class="colHeader small" valign="top">{$LIST_HEADER.4}</td> {* crmv@39110 *}
					  </tr>
					 {foreach name=profilelist item=listvalues from=$LIST_ENTRIES}
					<tr>
						<td class="listTableRow small" valign=top>{$smarty.foreach.profilelist.iteration}</td>
						<td class="listTableRow small" valign=top nowrap>
							<a href="index.php?module=Settings&action=profilePrivileges&return_action=ListProfiles&parenttab=Settings&mode=edit&profileid={$listvalues.profileid}">
								<i class="vteicon md-sm" title="{$APP.LBL_EDIT}">create</i>
							</a>
							{if $listvalues.del_permission eq 'yes'}
							&nbsp;
							<a href="javascript:void(0);" onclick="ProfileUtils.DeleteProfile(this,'{$listvalues.profileid}')">
								<i class="vteicon md-sm" title="{$APP.LBL_DELETE}">delete</i>
							</a>
							{/if}
						</td>
						<td class="listTableRow small" valign=top><a href="index.php?module=Settings&action=profilePrivileges&mode=view&parenttab=Settings&profileid={$listvalues.profileid}"><b>{$listvalues.profilename}</b></a></td>
						<td class="listTableRow small" valign=top>{$listvalues.description}</td>
						<td class="listTableRow small" valign=top>{if $listvalues.mobile eq true}{$APP.LBL_YES}{else}{/if}</td> {* crmv@39110 *}
					  </tr>
					{/foreach}

					</table>
				</td>
				</tr>
				</table>

			</td>
			</tr>
			</table>
		</td>
	</tr>
	</table>

	</div>
</td>
        <td valign="top"></td>
   </tr>
</tbody>
</table>
<div id="tempdiv" style=""></div>
<script>
{if $ERROR_STRING neq ''}
	setTimeout(function(){ldelim}
		vtealert('{$ERROR_STRING}');
	{rdelim},500);
{/if}
</script>