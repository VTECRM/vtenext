{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@126696 *}

{include file="SmallHeader.tpl"}

<script language="JavaScript" type="text/javascript" src="include/js/vtlib.js"></script>
<script language="JavaScript" type="text/javascript" src="modules/Campaigns/Campaigns.js"></script>
<script language="JavaScript" type="text/javascript" src="modules/Newsletter/Newsletter.js"></script>
<script language="JavaScript" type="text/javascript" src="modules/Settings/ProcessMaker/resources/ActionTaskScript.js"></script>
<script language="JavaScript" type="text/javascript" src="include/js/{$AUTHENTICATED_USER_LANGUAGE}.lang.js"></script> {* crmv@181170 *}
{include file='CachedValues.tpl'}	{* crmv@26316 crmv@55961 *}

<style type="text/css">
{literal}
	.nlWizTargetList {
		border-bottom:1px solid #e0e0e0;
		padding-bottom:4px;
		margin-bottom:10px;
		height: 300px;
		overflow: scroll;
	}
	.addrBubble {
		overflow: visible;
		position: static;
		background-color: #EDF4FD;
		border: 1px solid #DADADA;
	    color: black;
	    display: inline-block;
	    margin-bottom: 2px;
	    margin-left: 3px;
	    padding: 5px 3px;
	    border-radius: 0px;
	}
	.ImgBubbleDelete {
		display:inline-block;
		cursor:pointer;
	    overflow: hidden;
		vertical-align: middle;
	}
	.menuSeparation, .level3Bg {
    	border-bottom: 0px none;
	}
{/literal}
</style>

{* popup status *}
<div id="status" name="status" style="display:none;position:fixed;right:2px;top:45px;z-index:100">
	{include file="LoadingIndicator.tpl"}
</div>


<table class="small" border="0" cellspacing="0" cellpadding="0" width="100%">
	<tr>
		<td align="right">
			<input type="button" class="crmbutton small save" onclick="ActionNewsletterScript.popupSelectRecipients()" value="{$APP.LBL_SAVE_BUTTON_LABEL}">
			<input type="button" class="crmbutton small cancel" onclick="closePopup()" value="{$APP.LBL_CANCEL_BUTTON_LABEL}">
		</td>
	</tr>
</table>

<div id="nlWizStep1" style="margin:10px;">

	{$MOD.WhichRecipientsToAdd}
	
	<div class="dvtCellInfo" style="display:inline-block;width:200px">
		<select class="detailedViewTextBox" id="nlw_targetTypeSel" onchange="nlwChangeTargetSel()">
			<option value="">{$APP.LBL_SELECT}</option>
			{foreach key=TMOD item=TMODINFO from=$TARGET_MODS}
			<option value="{$TMOD}">{$TMOD|getTranslatedString:$TMOD}</option>
			{/foreach}
		</select>
	</div>

	<div class="divider"></div>

	{foreach key=TMOD item=TMODINFO from=$TARGET_MODS}
		{if $TMOD eq 'Targets'}
			{assign var=LISTIDTARGETS value=$TMODINFO.listid}
		{/if}
		<div class="nlWizTargetList" id="nlw_targetList_{$TMOD}" style="display:none">
		{$TMODINFO.list}
		</div>
	{/foreach}

	<div id="nlw_targetsBoxCont">
		<p><b>{$MOD.SelectedRecipients}</b></p>
		<div id="nlWizTargetsBox"></div>
		{if $SEL_TARGETS neq '' && count($SEL_TARGETS) > 0}
		<script type="text/javascript">
			{foreach item=TGT from=$SEL_TARGETS}
				nlwRecordSelect('{$LISTIDTARGETS}', 'Targets', '{$TGT.crmid}', '{$TGT.entityname}');
			{/foreach}
		</script>
		{/if}
	</div>

</div>