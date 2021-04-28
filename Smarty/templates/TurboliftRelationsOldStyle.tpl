{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@57221 crmv@104568 *}

{* crmv@64719 *}
{assign var="RELATION_LIMIT" value="5"}

{foreach item=RELATION from=$RELATIONS name="tl_related_foreach"}
	{assign var=header value=' '|@str_replace:'':$RELATION.header}
	{assign var=related value=$MODULE|cat:"_"|cat:$header}
	{if $RELATION.type eq 'card'}
		{assign var=entity_id value=$RELATION.id}
		{assign var=info value=$RELATION.card}
		{if !empty($info.onclick)}
			{assign var=CARDONCLICK value=$info.onclick}
		{elseif $info.link neq ''}
			{assign var=CARDONCLICK value=$TURBOLIFT_HREF_TARGET_LOCATION|cat:"='"|cat:$info.link|cat:"';"}
		{/if}
		{include file='Card.tpl' TURBOLIFTCARD=true CARDRECORD=$entity_id CARDID="tl_"|cat:$related CARDMODULE=$info.module CARDMODULE_LBL=$info.modulelbl PREFIX=$info.prefix CARDNAME=$info.name CARDDETAILS=$info.details CARDONCLICK=$CARDONCLICK DISPLAY=$RELATION.display IMG=$info.img} {* crmv@152802 *}
	{else}
		{if $smarty.foreach.tl_related_foreach.index eq $RELATION_LIMIT}
			<div id="tl_MoreRelated" class="turboliftEntry turboliftEntryWithImage" onClick="showMoreTurboliftElements();">
				<div style="float:left;padding:5px;">
					{$APP.LBL_MORE}
				</div>
				<div style="float:right;padding:5px;">
					<img border="0" src="{'inactivate.gif'|resourcever}" align="absmiddle">
				</div>
			</div>
			<div id="tl_FewerRelated" class="turboliftEntry turboliftEntryWithImage" onClick="showFewerTurboliftElements({$RELATION_LIMIT});" style="display:none">
				<div style="float:left;padding:5px;">
					{$APP.LBL_FEWER_BUTTON}
				</div>
				<div style="float:right;padding:5px;">
					<img border="0" src="{'activate.gif'|resourcever}" align="absmiddle">
				</div>
			</div>
		{/if}
		{if $RELATION.fixed}
			{assign var=fixedParam value="true"}
		{else}
			{assign var=fixedParam value="false"}
		{/if}
		{assign var=CARDONCLICK value="loadDynamicRelatedList(jQuery(this).parent(),'`$RELATION.relationId`', '`$related`','module=`$MODULE`&action=`$MODULE`Ajax&file=DetailViewAjax&record=`$ID`&ajxaction=LOADRELATEDLIST&header=`$RELATION.header`&relation_id=`$RELATION.relationId`&actions=`$RELATION.actions`&load_header=yes', `$fixedParam`);"}
		<div relation_id="{$RELATION.relationId}" id="tl_{$related}" class="turboliftEntry turboliftEntryWithImage" style="cursor:default;{if $smarty.foreach.tl_related_foreach.index ge $RELATION_LIMIT}display:none{else}display:block{/if}"> {* crmv@62415 *}
			<div style="float:left;padding:5px;cursor:pointer;" onClick="{$CARDONCLICK}">
				{* crmv@43864 *}
				{if $RELATION.header}
					{$RELATION.header|getTranslatedString:$RELATION.module}
				{else}
					{$RELATION.module|getTranslatedString:$RELATION.module}
				{/if}
				{* crmv@43864e *}
				{if $RELATION.count gt 0}
					({$RELATION.count})
				{/if}
			</div>
			<div class="turboliftEntryButtons" style="float:right;">
				{if $RELATION.buttons|stripos:"<form " === false}
					{include file='RelatedListsHidden.tpl' RELATED_LIST_HIDDEN_FORM_NAME="tl_"|cat:$related|cat:"_form"}
				{/if}
				{$RELATION.buttons}
				{if $RELATION.buttons|stripos:"<form " === false}
					</form>
				{/if}
			</div>
		</div>
	{/if}
{/foreach}

<div id="detailViewActionsContainer2">
	{include file='CustomLinks.tpl' CUSTOM_LINK_TYPE="DETAILVIEWWIDGET"}
</div>

<script type="text/javascript">
{literal}
var turboliftOpen = false;

function fixTurbolift(obj) {
	return;
}

function showMoreTurboliftElements() {
	turboliftOpen = true;
	jQuery('#turboLiftRelationsContainer').find('.turboliftEntry').show();
	jQuery('#tl_MoreRelated').hide();
}

function showFewerTurboliftElements(limit) {
	turboliftOpen = false;
	var i = 0;
	jQuery('#turboLiftRelationsContainer').find('.turboliftEntry').each(function(){
		if (i >= limit) jQuery(this).hide();
		i++;
	});
	jQuery('#tl_MoreRelated').show();
}
{/literal}

{* crmv@54375 *}
{if !empty($OPENRELATEDLIST)}
	if (jQuery("#{$OPENRELATEDLIST}").length > 0) jQuery("#{$OPENRELATEDLIST}").click();
{/if}
{* crmv@54375e *}

{* jquery manipulation *}
//jQuery('#turboLiftContainer').css('width','20%');
jQuery('#turboLiftContainer').children('div').css('position','relative');
jQuery('#turboLiftContainer').children('div').css('width','100%');
if (jQuery('.loadDetailViewWidget').length > 0) jQuery('.loadDetailViewWidget').click();

jQuery('.turboliftEntryButtons input[type="button"], .turboliftEntryButtons input[type="submit"]').each(function(){ldelim}
	var value = '';
	if (jQuery(this).hasClass('customicon') && jQuery(this).attr('icon') != ''){ldelim}
		jQuery(this).css('background',"url('"+jQuery(this).attr('icon')+"') no-repeat");
	{rdelim} else if (jQuery(this).attr('title').indexOf('{'LBL_SELECT'|getTranslatedString}') > -1){ldelim}
		jQuery(this).css('background',"url('{'select.gif'|resourcever}') no-repeat");
	{rdelim}else if (jQuery(this).attr('title').indexOf('{'LBL_ADD_NEW'|getTranslatedString}') > -1 || jQuery(this).attr('title').indexOf('{'LBL_NEW'|getTranslatedString}') > -1){ldelim}
		jQuery(this).css('background',"url('{'btnL3Add_min.png'|resourcever}') no-repeat");
	{rdelim}else if (jQuery(this).attr('title').indexOf('{'LBL_LOAD_LIST'|getTranslatedString}') > -1 || jQuery(this).attr('title').indexOf('{'LBL_ViewTC'|getTranslatedString:'Timecards'}') > -1){ldelim}
		jQuery(this).hide();
	{rdelim}else{ldelim}
		value = jQuery(this).val();
	{rdelim}
	jQuery(this).val(value);
	if (value == '') {ldelim}
		jQuery(this).css('border',0);
		//if (browser_ie != true) {ldelim}
		//	jQuery(this).css('border-left','1px solid #DEDEDE');
		//{rdelim}
		jQuery(this).css('padding-left','12px');
		jQuery(this).css('cursor','pointer');
		jQuery(this).css('background-position','center');
		//jQuery(this).parent('td').width(jQuery(this).parent('td').width()+24);
	{rdelim}
{rdelim});
//jQuery('[id^="turbolift_foreach_label_"]').width(jQuery('[id^="turbolift_foreach_"]').outerWidth(true)-jQuery('[id^="turbolift_foreach_buttons_"]').outerWidth(true));
jQuery('.turboliftEntryButtons').show();
{* jquery manipulation end *}
</script>