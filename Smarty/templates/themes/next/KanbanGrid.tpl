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

	{* crmv@168361 *}
	{if $HIDE_CUSTOM_LINKS neq '1'}
		<div class="dropdown vcenter" id="customViewEdit_Ajax" style="display:none">
			<span data-toggle="dropdown">
				<i class="vteicon md-link" id="filter_option_img" data-toggle="tooltip" data-placement="bottom" title="{$APP.LBL_FILTER_OPTIONS}">settings</i>
			</span>
			<ul class="dropdown-menu" id="customLinks">
				{if $ALL eq 'All'}
					<li>
						<a href="index.php?module={$MODULE}&action=CustomView&duplicate=true&record={$VIEWID}&parenttab={$CATEGORY}">{$APP.LNK_CV_DUPLICATE}</a>
					</li>
					<li>
						<a href="index.php?module={$MODULE}&action=CustomView&parenttab={$CATEGORY}">{$APP.LNK_CV_CREATEVIEW}</a>
					</li>
				{else}
					{if $CV_EDIT_PERMIT eq 'yes'}
						<li>
							<a href="index.php?module={$MODULE}&action=CustomView&record={$VIEWID}&parenttab={$CATEGORY}">{$APP.LNK_CV_EDIT}</a>
						</li>
					{/if}
					
					<li>
						<a href="index.php?module={$MODULE}&action=CustomView&duplicate=true&record={$VIEWID}&parenttab={$CATEGORY}">{$APP.LNK_CV_DUPLICATE}</a>
					</li>
					
					{if $CV_DELETE_PERMIT eq 'yes'}
						<li>
							<a href="javascript:confirmdelete('index.php?module=CustomView&action=Delete&dmodule={$MODULE}&record={$VIEWID}&parenttab={$CATEGORY}')">{$APP.LNK_CV_DELETE}</a>
						</li>
					{/if}
					{if $CUSTOMVIEW_PERMISSION.ChangedStatus neq '' && $CUSTOMVIEW_PERMISSION.Label neq ''}
						<li>
							<a href="#" id="customstatus_id" onClick="ChangeCustomViewStatus({$VIEWID},{$CUSTOMVIEW_PERMISSION.Status},{$CUSTOMVIEW_PERMISSION.ChangedStatus},'{$MODULE}','{$CATEGORY}')">{$CUSTOMVIEW_PERMISSION.Label}</a>
						</li>
					{/if}
					<li>
						<a href="index.php?module={$MODULE}&action=CustomView&parenttab={$CATEGORY}">{$APP.LNK_CV_CREATEVIEW}</a>
					</li>
				{/if}
			</ul>
		</div>
	{/if}
	{* crmv@168361e *}
	
	{* crmv@139896 *}
	<table border="0" cellspacing="0" cellpadding="0" width="100%" class="small" align="center">
		<tr valign="top" id="kanban_grid_h" class="kanbanGridHeader">
			{foreach name=kanban_foreach item=KANBAN_COL from=$KANBAN_ARR}
				{math equation="x/y" x=100 y=$smarty.foreach.kanban_foreach.total+1 format="%d" assign=width} {* crmv@181170 *}
				<td width="{$width}%" style="min-width:100px;"><div class="dvInnerHeader"><div class="dvInnerHeaderTitle">{$KANBAN_COL.label|getTranslatedString}</div></div></td> {* crmv@181450 *}
			{/foreach}
			<td width="{$width}%" style="min-width:250px; display:none" id="previewContainer_Summary_h"></td>
		</tr>
		<tr valign="top">
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

<script type="text/javascript" id="init_kanban_script">
{if $smarty.request.ajax eq 'true'}
	KanbanView.init('{$MODULE}','{$VIEWID}');
{else}
	jQuery(document).ready(function(){ldelim}
		KanbanView.init('{$MODULE}','{$VIEWID}');
	{rdelim});
{/if}

// crmv@187842
(function() {ldelim}
	var mainContainer = jQuery('body').get(0);
	var wrapperHeight = parseInt(visibleHeight(mainContainer));

	var buttonList = jQuery('#Buttons_List_HomeMod').get(0);
	var buttonListHeight = parseInt(visibleHeight(buttonList));
	
	jQuery('#ModuleHomeMatrix').css('min-height', (wrapperHeight-buttonListHeight-33) + 'px');
{rdelim})();
// crmv@187842e
</script>