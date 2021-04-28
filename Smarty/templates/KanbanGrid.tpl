{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@OPER6288 crmv@102334 *}
{if $smarty.request.ajax neq ''}
&#&#&#{$ERROR}&#&#&#
{/if}
{if $KANBAN_NOT_AVAILABLE}
	{$APP.LBL_KANBAN_NOT_AVAILABLE}
{else}
	{* crmv@139896 *}
	<table border="0" cellspacing="0" cellpadding="0" width="100%" class="small" align="center">
		<tr bgcolor="#FFFFFF" class="level3Bg" valign="top" id="kanban_grid_h">
			{foreach name=kanban_foreach item=KANBAN_COL from=$KANBAN_ARR}
				{math equation="x/y" x=100 y=$smarty.foreach.kanban_foreach.total+1 format="%d" assign=width} {* crmv@181170 *}
				<td class="small" width="{$width}%" style="min-width:100px;">{$KANBAN_COL.label|getTranslatedString}</td> {* crmv@181450 *}
			{/foreach}
			<td class="level3Bg" width="{$width}%" style="min-width:250px; display:none" id="previewContainer_Summary_h"></td>
		</tr>
		<tr bgcolor="#FFFFFF" valign="top">
			{foreach name=kanban_foreach key=KANBAN_ID item=KANBAN_COL from=$KANBAN_ARR}
				<td>
					<ul id="{$KANBAN_ID}" lastpageapppended="{$LAST_PAGE_APPENDED}" class="kanbanSortableList" style="list-style:none; margin:0px;">
					{include file='KanbanColumn.tpl'}
					</ul>
				</td>
			{/foreach}
			<td id="previewContainer_Summary" style="display:none"><div id="previewContainer_Summary_scroll"></div></td>
		</tr>
	</table>
	{* crmv@139896e *}
{/if}
{if $HIDE_CUSTOM_LINKS neq '1'}
	<div class="drop_mnu" id="customLinks" onmouseover="fnShowDrop('customLinks');" onmouseout="fnHideDrop('customLinks');" style="width:150px;">
		<table cellspacing="0" cellpadding="0" border="0" width="100%">
			{* crmv@22259 *}
			{if $ALL eq 'All'}
				<tr>
					<td><a class="drop_down" href="index.php?module={$MODULE}&action=CustomView&duplicate=true&record={$VIEWID}&parenttab={$CATEGORY}&return_action=index">{$APP.LNK_CV_DUPLICATE}</a></td>
				</tr>
				<tr>
					<td><a class="drop_down" href="index.php?module={$MODULE}&action=CustomView&parenttab={$CATEGORY}&return_action=index">{$APP.LNK_CV_CREATEVIEW}</a></td>
				</tr>
		    {else}
				{if $CV_EDIT_PERMIT eq 'yes'}
					<tr>
						<td><a class="drop_down" href="index.php?module={$MODULE}&action=CustomView&record={$VIEWID}&parenttab={$CATEGORY}&return_action=index">{$APP.LNK_CV_EDIT}</a></td>
					</tr>
				{/if}
				<tr>
					<td><a class="drop_down" href="index.php?module={$MODULE}&action=CustomView&duplicate=true&record={$VIEWID}&parenttab={$CATEGORY}&return_action=index">{$APP.LNK_CV_DUPLICATE}</a></td>
				</tr>
				{if $CV_DELETE_PERMIT eq 'yes'}
					<tr>
						<td><a class="drop_down" href="javascript:confirmdelete('index.php?module=CustomView&action=Delete&dmodule={$MODULE}&record={$VIEWID}&parenttab={$CATEGORY}&return_action=index')">{$APP.LNK_CV_DELETE}</a></td>
					</tr>
				{/if}
				{if $CUSTOMVIEW_PERMISSION.ChangedStatus neq '' && $CUSTOMVIEW_PERMISSION.Label neq ''}
					<tr>
				   		<td><a class="drop_down" href="#" id="customstatus_id" onClick="ChangeCustomViewStatus({$VIEWID},{$CUSTOMVIEW_PERMISSION.Status},{$CUSTOMVIEW_PERMISSION.ChangedStatus},'{$MODULE}','{$CATEGORY}')">{$CUSTOMVIEW_PERMISSION.Label}</a></td>
				   	</tr>
				{/if}
				<tr>
					<td><a class="drop_down" href="index.php?module={$MODULE}&action=CustomView&parenttab={$CATEGORY}&return_action=index">{$APP.LNK_CV_CREATEVIEW}</a></td>
				</tr>
		    {/if}
		    {* crmv@22259e *}
		</table>
	</div>
{/if}
<script type="text/javascript" id="init_kanban_script">
{if $smarty.request.ajax eq 'true'}
	KanbanView.init('{$MODULE}','{$VIEWID}');
{else}
	jQuery(document).ready(function(){ldelim}
		KanbanView.init('{$MODULE}','{$VIEWID}');
	{rdelim});
{/if}
</script>