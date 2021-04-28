{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@181161 crmv@182073 *}

<script language="JavaScript" type="text/javascript" src="{"include/js/menu.js"|resourcever}"></script>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
<tbody><tr>
	<td class="showPanelBg" style="padding: 5px;" valign="top" width="100%">

	<form action="index.php" method="post" name="Update" id="form" onsubmit="VteJS_DialogBox.block();">
    <input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
	<input type="hidden" name="module" value="Update">
	<input type="hidden" name="action">
	<input type="hidden" name="parenttab" value="Settings">
	
	<div align=center>
			
		{include file="SetMenu.tpl"}

		<!-- DISPLAY -->
		<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
		<tr>
			{* <td width=50 rowspan=2 valign=top><img src="{'workflow.gif'|resourcever}" alt="{$MOD.LBL_UPDATE}" width="48" height="48" border=0 title="{$MOD.LBL_UPDATE}"></td> *}
			<td class=heading2 valign=bottom><b>{$SMOD.LBL_SETTINGS}</a> > {$MOD.LBL_UPDATE} </b></td> <!-- crmv@30683 -->
		</tr>
		<tr>
			<td valign=top class="small">{$MOD.LBL_UPDATE_DESC} </td>
		</tr>
		</table>
		
		<br>
		<table border=0 cellspacing=0 cellpadding=10 width=100% >
		<tr>
		<td>
		
			<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">

			{if $ERROR_MSG neq ''}
			<tr>
				{$ERROR_MSG}
			</tr>
			{/if}
			</table>
					
			<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
			<tr>
			<td class="small" valign=top ><table width="100%"  border="0" cellspacing="0" cellpadding="5">
				<tr>
					<td width="20%" nowrap class="small cellLabel">{$MOD.LBL_CURRENT_VERSION}</td>
					<td width="80%">
						<input type="text" class="dvtCellInfo" style="width:10%;" value="{$CURRENT_VERSION}" name="current_version" {if $FREEZE_VERSION}readonly{/if}>
					</td>
				</tr>
				</table>
			</td>
			</tr>
			</table>
			{*
			<br />
			<table border=0 cellspacing=0 cellpadding=5 width=100% class="tableHeading">
			<tr>
				<td class="big"><strong>{$MOD.LBL_UPDATE_DETAILS}</strong></td>
				<td class="small" align=right></td>
			</tr>
			</table>
			*}
			<table border=0 cellspacing=0 cellpadding=0 width=100% class="listRow">
			<tr>
			<td class="small" valign=top ><table width="100%"  border="0" cellspacing="0" cellpadding="5">
            	<tr>
					<td width="20%" nowrap class="small cellLabel">{$MOD.LBL_UPDATE_TO}</td>
					<td width="80%" class="small cellText">
						<input type="hidden" value="specific_version" name="type_update">
						<input type="text" class="dvtCellInfo" style="width:10%;" value="{if $DEST_VERSION}{$DEST_VERSION}{/if}" name="specificied_version" id="specificied_version" {if $FREEZE_VERSION}readonly{/if}>

				    </td>
				</tr>
				</table>
			</td>
			</tr>
			</table>
			
			<table border=0 cellspacing=0 cellpadding=5 width=100%>
			<tr>
				<td class="small" align=right>
					<input title="{$MOD.LBL_UPDATE_BUTTON}" accessKey="{$MOD.LBL_UPDATE_BUTTON}" class="crmButton small save" onclick="this.form.action.value='DoUpdate';" type="submit" name="button" value="{$MOD.LBL_UPDATE_BUTTON}">&nbsp;&nbsp;
				</td>
			</tr>
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
	</form>
	
</td>
</tr>
</tbody>
</table>