{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@39110 crmv@104568 *}
<form action="index.php" method="post" name="form" onsubmit="VteJS_DialogBox.block();">
	<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
	<input type="hidden" name="fld_module" value="{$MODULE}">
	<input type="hidden" name="module" value="Settings">
	<input type="hidden" name="parenttab" value="Settings">
	{assign var=entries value=$CFENTRIES}
	<table width="100%" border="0" cellpadding="3" cellspacing="0">
		<tr>
			<td colspan="4"><b>{$MOD.LBL_CONFIGURATION}</b></td>
		</tr>
		<tr>
			<td></td>
			<td>
				<span>{$MOD.LBL_SORT_BY_COUNT}</span>
			</td>
			<td colspan="2" align="center">
				{if $RELATEDLISTCONFIG.sortbycount}
					<i class="vteicon md-link checkok" title="{$MOD.LBL_VISIBLE}" onclick="LayoutEditor.changeRelatedOption('{$MODULE}', 'sortbycount', 0) ">check</i>
				{else}
					<i class="vteicon md-link checkko" title="{$MOD.LBL_NOT_VISIBLE}" onclick="LayoutEditor.changeRelatedOption('{$MODULE}', 'sortbycount', 1) ">clear</i>
				{/if}
			</td>
		</tr>
		<tr>
			<td colspan="4"><br></td>
		</tr>
		<tr>
			<td colspan="4"><b>{$MOD.LBL_PRESENCE_AND_ORDERING}</b></td>
		</tr>
		{foreach item=related from=$RELATEDLIST name=relinfo}
		<tr>
			<td>
				{if $related.presence eq 0}
					<i class="vteicon md-link checkok" title="{$MOD.LBL_VISIBLE}" onclick="LayoutEditor.changeRelatedListVisibility('{$related.tabid}',0,'{$related.id}','{$MODULE}') ">check</i>
				{else}
					<i class="vteicon md-link checkko" title="{$MOD.LBL_NOT_VISIBLE}" onclick="LayoutEditor.changeRelatedListVisibility('{$related.tabid}',1,'{$related.id}','{$MODULE}') ">clear</i>
				{/if}
			</td>
			<td>{$related.label}</td>

			{if $smarty.foreach.relinfo.first}
				<td align="right" >
	 				<img src="{'blank.gif'|resourcever}" style="width:16px;height:16px;" border="0" />&nbsp;
				</td>
			{else}
				<td align="right" valign="middle">
		 	 		<i class="vteicon md-link" onclick="LayoutEditor.changeRelatedListorder('move_up','{$related.tabid}','{$related.sequence}','{$related.id}','{$MODULE}') " title="{$MOD.UP}">arrow_upward</i>&nbsp;
				</td>
			{/if}

			{if $smarty.foreach.relinfo.last}
				<td align="right" >
	 				<img src="{'blank.gif'|resourcever}" style="width:16px;height:16px;" border="0" />&nbsp;
				</td>
			{else}
				<td align="right" valign="middle">
		 	 		<i class="vteicon md-link" onclick="LayoutEditor.changeRelatedListorder('move_down','{$related.tabid}','{$related.sequence}','{$related.id}','{$MODULE}') " title="{$MOD.DOWN}">arrow_downward</i>&nbsp;
				</td>
			{/if}
		</tr>
		{/foreach}
	</table>
</form>