{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@39110 crmv@104568 *}

<script language="JavaScript" type="text/javascript" src="{"include/js/customview.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/general.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/layouteditor.js"|resourcever}"></script>

<script language="JavaScript" type="text/javascript">
var for_mobile = {if $FORMOBILE}1{else}0{/if};
var gselected_fieldtype = '';
</script>

<div id="layoutblock">

{assign var="FLOAT_TITLE" value=$MOD.LBL_RELATED_LIST}
{assign var="FLOAT_WIDTH" value="300px"}
{assign var="FLOAT_BUTTONS" value=""}
{assign var="FLOAT_CONTENT" value=""}
{include file="FloatingDiv.tpl" FLOAT_ID="RelatedList" FLOAT_HEIGHT="500px"}

{assign var=entries value=$CFENTRIES}
{if $CFENTRIES.0.tabpresence eq '0' }
{include file='SetMenu.tpl'}

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%">
	<tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%">

			<table class="settingsSelUITopLine" border="0" cellpadding="5" cellspacing="0" width="100%">
				<tr>
					<td rowspan="2" valign="top" width="50"><img src="{'orgshar.gif'|resourcever}" alt="Users" title="Users" border="0" height="48" width="48"></td>
					<td class="heading2" valign="bottom">
						<b><a href="index.php?module=Settings&action=ModuleManager&parenttab=Settings">{$MOD.VTLIB_LBL_MODULE_MANAGER}</a>
						&gt;<a href="index.php?module=Settings&action=ModuleManager&module_settings=true&formodule={$MODULE}&parenttab=Settings">{if $APP.$MODULE } {$APP.$MODULE} {elseif $MOD.$MODULE} {$MOD.$MODULE} {else} {$MODULE} {/if}</a> &gt;
						{$MOD.LBL_LAYOUT_EDITOR}{if $FORMOBILE} (Mobile){/if}</b>
					</td>
				</tr>
			</table>

			{if $FORMOBILE neq true || count($MOBILE_PROFILES) > 0}
			<table><tr>
				<td align="left" width="85%">
				&nbsp;&nbsp;&nbsp;<b>{$MOD.LBL_SELECT_MODULE}:</b>&nbsp;
					<select id="sel_mod" onChange="nav(this.value);">
						{foreach item=trad key=mod from=$MODULELIST}
							{if $mod eq $CFENTRIES.0.module}
								<option value="{$mod}" selected/>{$trad}</option>
							{else}
								<option value="{$mod}"/>{$trad}</option>
							{/if}
						{/foreach}
					</select>
				</td>
				{* crmv@146434 *}
				{if $FORMOBILE neq true}
					<td align="left" width="3%" id="layoutBlockVersionContainer" nowrap>
						{include file="Settings/LayoutBlockVersion.tpl"}
					</td>
				{/if}
				{* crmv@146434e *}
				<td align="right" width="10%">
					<input type="button" class="crmButton create small" onclick="LayoutEditor.callRelatedList('{$CFENTRIES.0.module}');showFloatingDiv('RelatedList', this);" title="{$MOD.LBL_MANAGE_RELATEDLIST}" value="{$MOD.LBL_MANAGE_RELATEDLIST}"/>
				</td>
			</tr></table>

			<div id="cfList">
                {include file="Settings/LayoutBlockEntries.tpl"}
            </div>

            {if $FORMOBILE eq true}
            <br><br>
            <div id="layoutMobileInfo" style="margin:10px">
            	<form type="POST" onsubmit="saveMobileInfo('{$CFENTRIES.0.module}'); return false">
            	<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
				<b>{$MOD.LBL_OTHER_SETTINGS}</b><br><br>
            	<table border="0" width="100%" cellpadding="4" cellspacing="1" style="border: 1px solid #cccccc">
            		<tr>
            			<td>{$MOD.LBL_MAIN_FIELD_FOR_LISTS}</td>
            			<td>
            			<select name="mobileEntityName" id="mobileEntityName">
            				<option value="">-- {$MOD.LBL_DEFAULT} --</option>
            				{foreach item=block from=$CFENTRIES}
            					{if is_array($block.field) && count($block.field) > 0}
            					<optgroup label="{$block.blocklabel}">
            						{foreach item=field from=$block.field}
            							{if $field.fieldname eq $MOBILEINFO.entityname}
            								{assign var=selected value='selected="selected"'}
            							{else}
            								{assign var=selected value=''}
            							{/if}
            							<option value="{$field.fieldname}" {$selected}>{$field.label}</option>
            						{/foreach}
            					</optgroup>
            					{/if}
            				{/foreach}
            			</select></td>
            		</tr>
            		<tr>
            			<td>{$MOD.LBL_DEFAULT_MOBILE_TAB}</td>
            			<td>
            			<select name="mobileDefaultTab" id="mobileDefaultTab">
            				<option value="">-- {$APP.LBL_NONE_NO_LINE} --</option>
            				<option value="0" {if $MOBILEINFO.mobiletab eq '0'}selected=""{/if}>{$APP.LBL_RECENTS}</option>
            				<option value="1" {if $MOBILEINFO.mobiletab eq '1'}selected=""{/if}>{$APP.LBL_FAVORITES}</option>
            				<option value="2" {if $MOBILEINFO.mobiletab eq '2'}selected=""{/if}>{$MOD.LBL_FILTERS_LIST}</option>
            				<option value="3" {if $MOBILEINFO.mobiletab eq '3'}selected=""{/if}>{$APP.LBL_FILTER}</option>
            			</select>
            			</td>
            		</tr>
            		<tr>
            			<td>{$MOD.LBL_DEFAULT_FILTER_FOR_LISTS}</td>
            			<td>
            			<select name="mobileFilter" id="mobileFilter">
            				<option value="">-- {$MOD.LBL_DEFAULT} --</option>
            				{foreach item=filter from=$CVLIST}
          						{if $filter.cvid eq $MOBILEINFO.cvid}
          							{assign var=selected value='selected="selected"'}
          						{else}
           							{assign var=selected value=''}
           						{/if}
           						<option value="{$filter.cvid}" {$selected}>{$filter.viewname}</option>
            				{/foreach}
            			</select>
            			&nbsp;{$MOD.LBL_ORDERED_BY|strtolower}&nbsp;
            			<select name="mobileSortField" id="mobileSortField">
            				<option value="">-- {$MOD.LBL_DEFAULT} --</option>
            				{foreach item=block from=$CFENTRIES}
            					{if is_array($block.field) && count($block.field) > 0}
            					<optgroup label="{$block.blocklabel}">
            						{foreach item=field from=$block.field}
            							{if $field.fieldname eq $MOBILEINFO.sortfield}
            								{assign var=selected value='selected="selected"'}
            							{else}
            								{assign var=selected value=''}
            							{/if}
            							<option value="{$field.fieldname}" {$selected}>{$field.label}</option>
            						{/foreach}
            					</optgroup>
            					{/if}
            				{/foreach}
            			</select>
						&nbsp;
            			<select name="mobileSortOrder" id="mobileSortOrder">
            				<option value="ASC" {if $MOBILEINFO.sortorder eq 'ASC'}selected=""{/if}>{$APP.Ascending}</option>
            				<option value="DESC" {if $MOBILEINFO.sortorder eq 'DESC'}selected=""{/if}>{$APP.Descending}</option>
            			</select>

            			</td>
            		</tr>

            		<tr>
            			<td>{$MOD.LBL_EXTRA_FIELDS}</td>
            			<td>
            			{section name=extraloop start=1 loop=4}
            			{assign var=i value=$smarty.section.extraloop.index}
            			<select name="mobileExtraField{$i}" id="mobileEntityName{$i}">
            				<option value="">-- {$APP.LBL_NONE_NO_LINE} --</option>
            				{foreach item=block from=$CFENTRIES}
            					{if is_array($block.field) && count($block.field) > 0}
            					<optgroup label="{$block.blocklabel}">
            						{foreach item=field from=$block.field}
            							{if $field.fieldname eq $MOBILEINFO.extrafields.$i}
            								{assign var=selected value='selected="selected"'}
            							{else}
            								{assign var=selected value=''}
            							{/if}
            							<option value="{$field.fieldname}" {$selected}>{$field.label}</option>
            						{/foreach}
            					</optgroup>
            					{/if}
            				{/foreach}
            			</select>&nbsp;
            			{/section}
            			</td>
            		</tr>

            		<tr>
            			<td colspan="2" align="center" style="border-top: 1px solid #cccccc"><input type="submit" class="crmButton save small" alt="{$APP.LBL_SAVE_LABEL}" title="{$APP.LBL_SAVE_LABEL}" value="{$APP.LBL_SAVE_LABEL}"/></td>
            		</tr>
            	</table>
            	</form>
            </div>
            {/if}

		{else}
		<div style="padding:20px;margin:20px;text-align:center"><b>{$MOD.LBL_ENABLE_ATLEAST_MOBILE_PROF}</b></div>
		{/if}
<!--	</td>
	</tr>
</table> -->
		<!-- End of Display for field -->
{else}

	<link rel='stylesheet' type='text/css' href='themes/$theme/style.css'>
	<table border='0' cellpadding='5' cellspacing='0' width='100%' height='450px'><tr><td align='center'>
	<div style='border: 3px solid rgb(153, 153, 153); background-color: rgb(255, 255, 255); width: 55%; position: relative; z-index: 10000000;'>

		<table border='0' cellpadding='5' cellspacing='0' width='98%'>
		<tbody><tr>
		<td rowspan='2' width='11%'><img src="{'denied.gif'|resourcever}" ></td>
		<td style='border-bottom: 1px solid rgb(204, 204, 204);' nowrap='nowrap' width='70%'><span class='genHeaderSmall'>{$APP.LBL_PERMISSION}</span></td>
		</tr>
		<tr>
		<td class='small' align='right' nowrap='nowrap'>
		<a href='javascript:window.history.back();'>{$MOD.LBL_GO_BACK}</a><br>								   						     </td>
		</tr>
		</tbody></table>
		</div>
		</td></tr></table>
{/if}
</div>

{* crmv@146434 *}
<script type='text/javascript'>
{if $ERROR_STRING neq ''}
	setTimeout(function(){ldelim}
		vtealert('{$ERROR_STRING}');
	{rdelim},500);
{/if}
</script>
{* crmv@146434e *}