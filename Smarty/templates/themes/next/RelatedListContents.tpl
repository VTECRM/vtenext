{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@104568 *}

<script type='text/javascript' src="{"include/js/Mail.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/ListView.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/RelatedList.js"|resourcever}"></script>	{* crmv@25809 *}

{* crmv@9434 crmv@26161 crmv@64792 *}
{foreach key=oldheader item=detail from=$RELATEDLISTS}
{assign var=header value=$detail.header}
{if empty($header)}
	{assign var=header value=$oldheader}
{/if}
{assign var=related_module value=$detail.related_tabid|@getTabModuleName}
{assign var="HEADERLABEL" value=$header|@getTranslatedString:$related_module}
{* crmv@9434e crmv@26161e crmv@64792e *}
<div relation_id="{$detail.relationId}" class="relatedListDataContainer" id="container_{$MODULE}_{$header|replace:' ':''}" data-relationid="{$detail.relationId}" {if $detail.fixed}data-isfixed="1"{/if}>
<table width="100%" cellspacing="0" cellpadding="0" border="0">	{* crmv@26896 *} {* crmv@62415 *}
	<tr>
		<td>
			<div class="dvInnerHeader">
				<div class="dvInnerHeaderLeft">
					<div class="dvInnerHeaderTitle">
						{$HEADERLABEL}
						<span id="cnt_{$MODULE}_{$header|replace:' ':''}"></span> {* crmv@25809 *}
						- <span id="dtl_{$MODULE}_{$header|replace:' ':''}" style="font-weight:normal">{'LBL_LIST'|@getTranslatedString}</span>	{* crmv@3086m *}
						&nbsp;{include file="LoadingIndicator.tpl" LIID="indicator_"|cat:$MODULE|cat:"_"|cat:$HEADER|replace:' ':'' LIEXTRASTYLE="display:none;"}
					</div>
				</div>
				{if $smarty.request.action eq 'CallRelatedList' || $STATISTICSTAB} {* crmv@51688 crmv@152532 *}
					<div class="dvInnerHeaderRight">
						{* crmv@16312 *} {* crmv@172994 *}
						<img class="related-show-icon" id="show_{$MODULE}_{$header|replace:' ':''}" src="{'windowMinMax.gif'|resourcever}" style="border: 0px solid #000000;" alt="Display" title="Display" onclick="javascript:loadRelatedListBlock('module={$MODULE}&action={$MODULE}Ajax&file=DetailViewAjax&record={$ID}&ajxaction=LOADRELATEDLIST&header={$header}&relation_id={$detail.relationId}&actions={$detail.actions}&parenttab={$CATEGORY}','tbl_{$MODULE}_{$header|replace:' ':''}','{$MODULE}_{$header|replace:' ':''}');" />
						<img class="related-hide-icon" id="hide_{$MODULE}_{$header|replace:' ':''}" src="{'windowMinMax-off.gif'|resourcever}" style="border: 0px solid #000000;display:none;" alt="Display" title="Display" onclick="javascript:hideRelatedListBlock('tbl_{$MODULE}_{$header|replace:' ':''}','{$MODULE}_{$header|replace:' ':''}');" />
						{* crmv@16312e *} {* crmv@172994e *}
					</div>
				{else}
					<div class="dvInnerHeaderRight">
						{if !$detail.fixed}
							<i class="vteicon2 fa-thumb-tack md-link" id="pin_{$MODULE}_{$header|replace:' ':''}" style="display:none;" onClick="pinRelated('{$MODULE}_{$header|replace:' ':''}','{$MODULE}','{$related_module}');"></i>
							<i class="vteicon2 fa-thumb-tack md-link" id="unPin_{$MODULE}_{$header|replace:' ':''}" style="{if $PIN eq true}display:block;{else}display:none;{/if}opacity:0.5" onClick="unPinRelated('{$MODULE}_{$header|replace:' ':''}','{$MODULE}','{$related_module}');"></i>
							<i class="vteicon md-link valign-bottom" id="hideDynamic_{$MODULE}_{$header|replace:' ':''}" style="display:none" onClick="hideDynamicRelatedList(jQuery('#tl_{$MODULE}_{$header|replace:' ':''}'));">clear</i>
						{/if}
					</div>
				{/if}
			</div>
		</td>
	</tr>
	<tr>
		<td>
			<div relation_id="{$detail.relationId}" id="tbl_{$MODULE}_{$header|replace:' ':''}"></div> {* crmv@62415 *}
		</td>
	</tr>
</table>
</div>
{if $SELECTEDHEADERS neq '' && $header|in_array:$SELECTEDHEADERS}
<script type='text/javascript'>
//crmv@16312
{if $smarty.request.ajax neq 'true' && $FASTLOADRELATEDLIST neq true}	{* crmv@152532 *}
	jQuery(document).ready(function() {ldelim}
		loadRelatedListBlock('module={$MODULE}&action={$MODULE}Ajax&file=DetailViewAjax&record={$ID}&ajxaction=LOADRELATEDLIST&header={$header}&relation_id={$detail.relationId}&actions={$detail.actions}&parenttab={$CATEGORY}','tbl_{$MODULE}_{$header|replace:' ':''}','{$MODULE}_{$header|replace:' ':''}');
	{rdelim});
{else}
	loadRelatedListBlock('module={$MODULE}&action={$MODULE}Ajax&file=DetailViewAjax&record={$ID}&ajxaction=LOADRELATEDLIST&header={$header}&relation_id={$detail.relationId}&actions={$detail.actions}&parenttab={$CATEGORY}','tbl_{$MODULE}_{$header|replace:' ':''}','{$MODULE}_{$header|replace:' ':''}');
{/if}
//crmv@16312 end
</script>
{* crmv@25809 *}
{elseif $PERFORMANCE_CONFIG.RELATED_LIST_COUNT eq true && $SinglePane_View neq true}{* crmv@203484 *}
<script type='text/javascript'>
{if $smarty.request.ajax neq 'true'}
	jQuery(document).ready(function() {ldelim}
		loadRelatedListBlockCount('module={$MODULE}&action={$MODULE}Ajax&file=DetailViewAjax&record={$ID}&ajxaction=LOADRELATEDLIST&onlycount=true&header={$header}&relation_id={$detail.relationId}&actions={$detail.actions}&parenttab={$CATEGORY}','cnt_{$MODULE}_{$header|replace:' ':''}','{$MODULE}_{$header|replace:' ':''}','module={$MODULE}&action={$MODULE}Ajax&file=DetailViewAjax&record={$ID}&ajxaction=LOADRELATEDLIST&header={$header}&relation_id={$detail.relationId}&actions={$detail.actions}&parenttab={$CATEGORY}','tbl_{$MODULE}_{$header|replace:' ':''}');
	{rdelim});
{else}
	loadRelatedListBlockCount('module={$MODULE}&action={$MODULE}Ajax&file=DetailViewAjax&record={$ID}&ajxaction=LOADRELATEDLIST&onlycount=true&header={$header}&relation_id={$detail.relationId}&actions={$detail.actions}&parenttab={$CATEGORY}','cnt_{$MODULE}_{$header|replace:' ':''}','{$MODULE}_{$header|replace:' ':''}','module={$MODULE}&action={$MODULE}Ajax&file=DetailViewAjax&record={$ID}&ajxaction=LOADRELATEDLIST&header={$header}&relation_id={$detail.relationId}&actions={$detail.actions}&parenttab={$CATEGORY}','tbl_{$MODULE}_{$header|replace:' ':''}');
{/if}
</script>
{* crmv@25809e *}
{/if}
</script>
{/foreach}