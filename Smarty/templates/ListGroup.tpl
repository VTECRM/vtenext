{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<script language="JAVASCRIPT" type="text/javascript" src="include/js/smoothscroll.js"></script>
<script type="text/javascript" src="{"modules/Settings/resources/Group.js"|resourcever}"></script>


<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody><tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->


	<div align=center>
			{include file='SetMenu.tpl'}
			{include file='Buttons_List.tpl'} {* crmv@30683 *} 
				<!-- DISPLAY -->
				<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
				<form action="index.php" method="post" name="new" id="form" onsubmit="VteJS_DialogBox.block();">
				<input type="hidden" name="module" value="Settings">
				<input type="hidden" name="action" value="createnewgroup">
				<input type="hidden" name="mode" value="create">
				<input type="hidden" name="parenttab" value="Settings">
				<tr>
					<td width=50 rowspan=2 valign=top><img src="{'ico-groups.gif'|resourcever}" alt="{$MOD.LBL_GROUPS}" width="48" height="48" border=0 title="{$MOD.LBL_GROUPS}"></td>
					<td class=heading2 valign=bottom><b>{$MOD.LBL_SETTINGS} > {$CMOD.LBL_GROUPS}</b></td> <!-- crmv@30683 -->
				</tr>
				<tr>
					<td valign=top class="small">{$MOD.LBL_GROUP_DESC}</td>
				</tr>
				</table>
				<br>
				<table border=0 cellspacing=0 cellpadding=10 width=100% >
				<tr>

				<td>
				
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
					<tr>
						<td class="big"><strong>{$MOD.LBL_GROUP_LIST}</strong></td>
						<td class="small" align=right>{$CMOD.LBL_TOTAL} {$GRPCNT} {$CMOD.LBL_GROUPS} </td>
					</tr>
					</table>
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="listTableTopButtons">

					<tr>
					     <td class=small align=right>
						<input title="{$CMOD.LBL_NEW_GROUP}" class="crmButton create small" type="submit" name="New" value="{$CMOD.LBL_NEW_GROUP}"/>
					     </td>
					</tr>
					</table>
						
					<table border=0 cellspacing=0 cellpadding=5 width=100% class="listTable">
					<tr>
						<td class="colHeader small" valign=top width=2%>#</td>
						<td class="colHeader small" valign=top width=8%>{$LIST_HEADER.0}</td>
						<td class="colHeader small" valign=top width=30%>{$LIST_HEADER.1}</td>
						<td class="colHeader small" valign=top width=60%>{$LIST_HEADER.2}</td>
					  </tr>
						{foreach name=grouplist item=groupvalues from=$LIST_ENTRIES}
					  <tr>
						<td class="listTableRow small" valign=top>{$smarty.foreach.grouplist.iteration}</td>
						<td class="listTableRow small" valign=top nowrap>
							<a href="index.php?module=Settings&action=createnewgroup&returnaction=listgroups&parenttab=Settings&mode=edit&groupId={$groupvalues.groupid}">
								<i class="vteicon md-sm" title="{$APP.LNK_EDIT}">create</i>
							</a>&nbsp;
							<a href="javascript:;" onClick="VTE.Settings.Group.deletegroup(this,'{$groupvalues.groupid}')">
								<i class="vteicon md-sm" title="{$APP.LBL_DELETE}">delete</i>
							</a>
						</td>
						<td class="listTableRow small" valign=top><strong>
							<a href="index.php?module=Settings&action=GroupDetailView&parenttab=Settings&groupId={$groupvalues.groupid}">{$groupvalues.groupname}</a></strong>
						</td>
						<td class="listTableRow small" valign=top>{$groupvalues.description}</td>
					  </tr>
					{/foreach}
					</table>
					{include file="Settings/ScrollTop.tpl"}
					
					
				</td>
				</tr>
				</table>
			
			
			
			</td>
			</tr>
			</table>
		</td>
	</tr>
	</form>
	</table>
		
	</div>
</td>
        <td valign="top"></td>
   </tr>
</tbody>
</table>

<div id="tempdiv"></div>