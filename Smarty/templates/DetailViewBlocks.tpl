{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@3085m crmv@3086m *}
{if empty($DESTINATION)}
	{assign var=DESTINATION value='DetailViewBlocks'}
{/if}
{if empty($REAL_DESTINATION)}
	{assign var=REAL_DESTINATION value=$DESTINATION}
{/if}
{if empty($EXTRAPARAMSJS)}
	{assign var=EXTRAPARAMSJS value='false'}
{/if}
{if $SHOW_RELATED_BUTTONS}
	<table border="0" cellspacing="0" cellpadding="0" width="100%" class="small" style="padding:5px;">
		<tr>
			<td width="100%" align="right">
				<span style="padding-right:10px">
					<a href="javascript:;" onClick="turnToRelatedList('{'LBL_LIST'|getTranslatedString}','{$REAL_DESTINATION}','{$DESTINATION}');">{'LBL_LIST'|getTranslatedString}</a>
					{* crmv@77702 *}
					{if $DETAIL_PERMISSION neq 'no'}
					- <a href="index.php?module={$MODULE}&action=DetailView&record={$ID}">{'LBL_SHOW_DETAILS'|getTranslatedString}</a>
					{/if}
					{* crmv@77702e *}
				</span>
				{if $privrecord neq ''}
					<img style="cursor:pointer" align="absmiddle" title="{$APP.LNK_LIST_PREVIOUS}" accessKey="{$APP.LNK_LIST_PREVIOUS}" onclick="loadSummary('{'LBL_SHOW_DETAILS'|getTranslatedString}','{$MODULE}','{$privrecord}','{$DESTINATION}','{$RELATION_ID}');" src="{'rec_prev.png'|resourcever}">
				{else}
					<img align="absmiddle" title="{$APP.LNK_LIST_PREVIOUS}" src="{'rec_prev_disabled.png'|resourcever}">
				{/if}
				{if $nextrecord neq ''}
					<img style="cursor:pointer" align="absmiddle" title="{$APP.LNK_LIST_NEXT}" accessKey="{$APP.LNK_LIST_NEXT}" onclick="loadSummary('{'LBL_SHOW_DETAILS'|getTranslatedString}','{$MODULE}','{$nextrecord}','{$DESTINATION}','{$RELATION_ID}');" name="nextrecord" src="{'rec_next.png'|resourcever}">
				{else}
					<img align="absmiddle" title="{$APP.LNK_LIST_NEXT}" src="{'rec_next_disabled.png'|resourcever}">
				{/if}
			</td>
		</tr>
	</table>
{/if}
{* crmv@OPER6288 *}
{if $SHOW_KANBAN_BUTTONS}
	<table border="0" cellspacing="0" cellpadding="0" width="100%" class="small" style="padding:5px;">
		<tr>
			<td width="50%" align="left">
				<a href="javascript:;" onClick="KanbanView.closePreView('{$MODULE}','{$ID}')">{'LBL_CLOSE'|getTranslatedString}</a>
			</td>
			<td width="50%" align="right">
				{if $DETAIL_PERMISSION neq 'no'}
					<a href="index.php?module={$MODULE}&action=DetailView&record={$ID}">{'LBL_SHOW_DETAILS'|getTranslatedString}</a>
				{/if}
			</td>
		</tr>
	</table>
{/if}
{* crmv@OPER6288e *}
{* crmv@77702 *}
{if $DETAIL_PERMISSION eq 'no'}
	{$APP.LBL_PERMISSION}
{else}
{* crmv@77702e *}
	{if $SUMMARY}
		{include file="DetailViewBlock.tpl" detail=$BLOCKS}
		{if $SHOW_DETAILS_BUTTON}
			<table border=0 cellspacing=0 cellpadding=0 width=100% class="small">
				<tr>
					<td colspan="2" align="center">
						<a href="javascript:;" onClick="loadDetailViewBlocks('{$MODULE}','{$ID}','','{$REAL_DESTINATION}',{$EXTRAPARAMSJS});"><img src="{'more.png'|resourcever}" title="{'LBL_SHOW_DETAILS'|getTranslatedString}" border="0" /></a>
					</td>
				</tr>
			</table>
		{/if}
	{else}
		{assign var=BLOCKINITIALSTATUS value=VteSession::get('BLOCKINITIALSTATUS')} {* crmv@181170 *}
		{* crmv@104568 *}
		{foreach item=detail from=$BLOCKS}
			{assign var="header" value=$detail.label}
			{assign var="blockid" value=$detail.blockid}
			<div id="block_{$blockid}" class="vte-card detailBlock" style="{if $PANELID != $detail.panelid}display:none{/if}">
				<table border=0 cellspacing=0 cellpadding=0 width=100% class="small detailBlockHeader">
					<tr>{strip}
						<td>
							<div class="dvInnerHeader">
								<div class="dvInnerHeaderLeft">
									<div class="dvInnerHeaderTitle">{$header}</div>
								</div>
								<div class="dvInnerHeaderRight">
									{if $header eq $MOD.LBL_ADDRESS_INFORMATION && ($MODULE eq 'Accounts' || $MODULE eq 'Contacts' || $MODULE eq 'Leads')}
										{if $MODULE eq 'Leads'}
											<button name="mapbutton" class="crmbutton create" type="button" onclick="VTE.MapLocation.searchMapLocation('{$ID}', 'Main');">{$APP.LBL_LOCATE_MAP}</button> {* crmv@194390 *}
										{else}
											<button name="mapbutton" class="crmbutton create" type="button" onclick="VTE.MapLocation.showAvailableAddresses();">{$APP.LBL_LOCATE_MAP}</button> {* crmv@194390 *}
										{/if}
									{/if}
									{if $BLOCKINITIALSTATUS[$header] eq 1}
										<i class="vteicon md-sm md-link" id="aid{$header|replace:' ':''}" title="Hide" onclick="showHideStatus('tbl{$header|replace:' ':''}','aid{$header|replace:' ':''}','{$IMAGE_PATH}');">video_label</i>
									{else}
										<i class="vteicon md-sm md-link" id="aid{$header|replace:' ':''}" title="Display" style="opacity:0.5" onclick="showHideStatus('tbl{$header|replace:' ':''}','aid{$header|replace:' ':''}','{$IMAGE_PATH}');">video_label</i>
									{/if}
								</div>
							</div>
						</td>{/strip}
					</tr>
				</table>
				
				<div class="detailBlockContent" style="{if $BLOCKINITIALSTATUS[$header] neq 1}display:none;{/if}" id="tbl{$header|replace:' ':''}">
					{include file="DetailViewBlock.tpl" detail=$detail.fields}
				</div>
			</div>
		{/foreach}
		{* crmv@104568e *}
		{if $SHOW_DETAILS_BUTTON}
			<table border=0 cellspacing=0 cellpadding=0 width=100% class="small">
				<tr>
					<td colspan="2" align="center">
						<a href="javascript:;" onClick="loadDetailViewBlocks('{$MODULE}','{$ID}','summary','{$REAL_DESTINATION}',{$EXTRAPARAMSJS});"><img src="{'more.png'|resourcever}" title="{'LBL_SUMMARY'|getTranslatedString}" border="0" /></a>
					</td>
				</tr>
			</table>
		{/if}
	{/if}
{/if}	{* crmv@77702 *}