{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* This file is used to display the fields based on the ui type in detailview *}
{* crmv@54072 crmv@57221 *}

{* crmv@sdk-18509 *}
{if $SDK->isUitype($keyid) eq 'true'}
	{assign var="sdk_mode" value="detail"}
	{assign var="sdk_file" value=$SDK->getUitypeFile('tpl',$sdk_mode,$keyid)}
	{if $sdk_file neq ''}
		{if $SDK->isOldUitype($keyid)}
			{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label}
			<div><table cellpadding="0" cellspacing="0" width="100%"><tr>
				{include file=$sdk_file}
			</tr></table></div>
		{else}
			{include file=$sdk_file}
		{/if}
	{/if}
{* crmv@sdk-18509e *}
{elseif $keyid eq '1' || $keyid eq '7' || $keyid eq '9' || $keyid eq '55' || $keyid eq '71' || $keyid eq '72' || $keyid eq '103' || $keyid eq '255' || $keyid eq 1112} <!--TextBox-->
	{if $keyid eq 1112 && ($keyval neq '' && $keyval neq '--None--')}
		{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label}
		<div class="{$DIVCLASSOTHER}dvtCellInfoOff detailCellInfo ">
	{else}
		{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
		<div class="{$DIVCLASS} detailCellInfo" id="fieldCont_{$keyfldid}" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
	{/if}                             		
	{if $keyid eq '55'}<!--SalutationSymbol-->
		{if $keyaccess eq $APP.LBL_NOT_ACCESSIBLE}
			<font color='red'>{$APP.LBL_NOT_ACCESSIBLE}</font>	
		{else}
			{$keysalut}
		{/if}
	{/if}
	<span id="dtlview_{$label}">{$keyval}</span>
	<div id="editarea_{$label}" style="display:none;">
		<input class="detailedViewTextBox" type="text" id="txtbox_{$label}" name="{$keyfldname}" maxlength='100' value="{$keyval}" />
	</div>
	</div>
	{if $keyid eq '71' && $keyfldname eq 'unit_price' && is_array($PRICE_DETAILS) && $PRICE_DETAILS|@count > 0} {* crmv@177658 *}
		<div id="multiple_currencies" width="38%" style="align:right; float:right;">
			<a href="javascript:void(0);" onclick="toggleShowHide('currency_class','multiple_currencies');">{$APP.LBL_MORE_CURRENCIES} &raquo;</a>
		</div>
		<div id="currency_class" class="crmvDiv" style="display: none; position: absolute; padding:5px; z-index: 1000000002;">
			<div class="closebutton" onclick="toggleShowHide('multiple_currencies','currency_class');"></div>
			<table width="100%" height="100%" class="small" cellpadding="5">
			<tr class="detailedViewHeader">
				<th>{$APP.LBL_CURRENCY}</th>
				<th colspan="2">{$APP.LBL_PRICE}</th>
			</tr>
			{foreach item=price key=count from=$PRICE_DETAILS}
				<tr>
					{*if $price.check_value eq 1*}
					<td class="dvtCellLabel" width="40%">
						{$price.currencylabel|@getTranslatedCurrencyString} ({$price.currencysymbol})
					</td>
					<td class="{$DIVCLASSOTHER}dvtCellInfoOff detailCellInfo" width="60%" colspan="2">
						{$price.curvalue}
					</td>
				</tr>
			{/foreach}
			</table>
		</div>
	{/if}
{elseif $keyid eq '13' || $keyid eq '104'} <!--Email-->
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
	<div class="{$DIVCLASS} detailCellInfo" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
		<span id="dtlview_{$label}">
			{if $smarty.session.internal_mailer eq 1}
				<a href="javascript:InternalMailer({$ID},{$keyfldid},'{$keyfldname}','{$MODULE}','record_id');">{$keyval}</a>
			{else}
				<a href="mailto:{$keyval}" target="_blank" >{$keyval}</a>
			{/if}
		</span>
		<div id="editarea_{$label}" style="display:none;">
			<input class="detailedViewTextBox"  type="text" id="txtbox_{$label}" name="{$keyfldname}" maxlength='100' value="{$keyval}" />
		</div>
		<div id="internal_mailer_{$keyfldname}" style="display: none;">{$keyfldid}####{$smarty.session.internal_mailer}</div>
	</div>
{elseif $keyid eq '1013'}	{* Fax *}	{* crmv@7216 *}	
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
	<div class="{$DIVCLASS} detailCellInfo" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
		<span id="dtlview_{$label}">
			<a href="javascript:InternalFax({$ID},{$keyfldid},'{$keyfldname}','{$MODULE}','record_id');">{$keyval}</a>
		</span>
        <div id="editarea_{$label}" style="display:none;">
			<input class="detailedViewTextBox"  type="text" id="txtbox_{$label}" name="{$keyfldname}" maxlength='100' value="{$keyval}" />
		</div>
		<div id="internal_mailer_{$keyfldname}" style="display: none;">{$keyfldid}####{$smarty.session.internal_mailer}</div>
	</div>
{elseif $keyid eq '1014' or $keyid eq '11'}	{* telephone numbers *} {* crmv@7220 *} {* crmv@36559 *}
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
	<div class="{$DIVCLASS} detailCellInfo" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
		<span id="dtlview_{$label}">
			{if ''|@get_use_asterisk eq 'true'}
				<a href='javascript:;' onclick='startCall("{$keyval}", "{$ID}")'>{$keyval}</a>
			{else}
				{$keyval}								
			{/if}
		</span>
		<div id="editarea_{$label}" style="display:none;">
			<input class="detailedViewTextBox"  type="text" id="txtbox_{$label}" name="{$keyfldname}" maxlength='100' value="{$keyval}" />
		</div>
		<div id="internal_mailer_{$keyfldname}" style="display: none;">{$keyfldid}####{$smarty.session.internal_mailer}</div>
	</div>
{elseif $keyid eq '15' || $keyid eq '16' || $keyid eq '1015'} <!--ComboBox-->	<!-- crmv@8982 --> 
	{foreach item=arr from=$keyoptions}
		{if $arr[0] eq $APP.LBL_NOT_ACCESSIBLE && $arr[2] eq 'selected'}
			{assign var=keyval value=$APP.LBL_NOT_ACCESSIBLE}
			{assign var=fontval value='red'}
		{else}
			{assign var=fontval value=''}
		{/if}
	{/foreach}
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
	<div class="{$DIVCLASS} detailCellInfo" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
		<span id="dtlview_{$label}"><font color="{$fontval}">{$keyval|@getTranslatedString:$MODULE}</font></span>
		<div id="editarea_{$label}" style="display:none;">
			<select id="txtbox_{$label}" name="{$keyfldname}" class="detailedViewTextBox">
				{foreach item=arr from=$keyoptions}
					{if $arr[0] eq $APP.LBL_NOT_ACCESSIBLE}
						<option value="{$arr[0]}" {$arr[2]}>{$arr[0]}</option>
					{else}
						<option value="{$arr[1]}" {$arr[2]}>{$arr[0]}</option>
					{/if}
				{/foreach}
			</select>
		</div>
	</div>
{elseif $keyid eq '33'}	<!--Multi Select Combo box-->
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
	<div class="{$DIVCLASS} detailCellInfo" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
		<span id="dtlview_{$label}">
			{foreach item=sel_val from=$keyoptions }
				{if $sel_val[2] eq 'selected'}
					{if $selected_val neq ''}
						{assign var=selected_val value=$selected_val|cat:', '}
					{/if}
					{assign var=selected_val value=$selected_val|cat:$sel_val[0]}
				{/if}
			{/foreach}
			{$selected_val|replace:"\n":"<br>"}
		</span>
		<div id="editarea_{$label}" style="display:none;">
			<select MULTIPLE id="txtbox_{$label}" name="{$keyfldname}" size="4" class="detailedViewTextBox">
				{foreach item=arr from=$keyoptions}
					<option value="{$arr[1]}" {$arr[2]}>{$arr[0]}</option>
				{/foreach}
			</select>
		</div>
	</div>
{elseif $keyid eq '115'} <!--ComboBox Status edit only for admin Users-->
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label}
	<div class="{$DIVCLASSOTHER}dvtCellInfoOff detailCellInfo ">{$keyval}</div>
{elseif $keyid eq '116'} <!--ComboBox currency id edit only for admin Users-->
	{if $keyadmin eq 1}
		{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
		<div class="{$DIVCLASS} detailCellInfo" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
			<span id="dtlview_{$label}">{$keyval}</span>
			<div id="editarea_{$label}" style="display:none;">
				<select id="txtbox_{$label}" name="{$keyfldname}" class="detailedViewTextBox">
					{foreach item=arr key=uivalueid from=$keyoptions}
						{foreach key=sel_value item=value from=$arr}
							<option value="{$uivalueid}" {$value}>{$sel_value}</option>	
						{/foreach}
					{/foreach}
				</select>
			</div>
	{else}
		{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label}
		<div class="{$DIVCLASSOTHER}dvtCellInfoOff detailCellInfo">{$keyval}
	{/if}	
	</div>
{elseif $keyid eq '17'} <!--WebSite-->
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
	<div class="{$DIVCLASS} detailCellInfo" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
		{* crmv@159970 *}
		{assign var=url_scheme value=$keyval|parse_url:$smarty.const.PHP_URL_SCHEME}
		{if $url_scheme neq ''}
			{assign var=scheme value=''}
		{else}
			{assign var=scheme value='http://'}
		{/if}
		<span id="dtlview_{$label}"><a href="{$scheme}{$keyval}" target="_blank">{$keyval}</a></span>
		{* crmv@159970e *}
		<div id="editarea_{$label}" style="display:none;">
			<input class="detailedViewTextBox"  onkeyup="validateUrl('{$keyfldname}');" type="text" id="txtbox_{$label}" name="{$keyfldname}" maxlength='100' value="{$keyval}" />
		</div>
	</div>
{elseif $keyid eq '85'}<!--Skype-->
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
	<div class="{$DIVCLASS} detailCellInfo" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
		<img src="{'skype.gif'|resourcever}" alt="{$APP.LBL_SKYPE}" title="{$APP.LBL_SKYPE}" LANGUAGE=javascript align="absmiddle"></img>
		<span id="dtlview_{$label}"><a href="skype:{$keyval}?call">{$keyval}</a></span>
		<div id="editarea_{$label}" style="display:none;">
			<input class="detailedViewTextBox" type="text" id="txtbox_{$label}" name="{$keyfldname}" maxlength='100' value="{$keyval}" />
		</div>
	</div>	
{elseif $keyid eq '19'} <!--TextArea/Description-->
	<!-- we will empty the value of ticket and faq comment -->
	{if $label eq $MOD.LBL_ADD_COMMENT}
		{assign var=keyval value=''}
		{include file="modules/HelpDesk/ConfidentialInfoPopups.tpl" editmode="detailview"} {* crmv@160733 *}
	{/if}
	{if $MODULE eq 'Documents' or $MODULE eq 'Timecards'}
		<!--To give hyperlink to URL-->
		{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label}
		<div class="{$DIVCLASSOTHER}dvtCellInfoOff detailCellInfo">{$keyval|regex_replace:"/(^|[\n ])([\w]+?:\/\/.*?[^ \"\n\r\t<]*)/":"\\1<a href=\"\\2\" target=\"_blank\">\\2</a>"|regex_replace:"/(^|[\n ])((www|ftp)\.[\w\-]+\.[\w\-.\~]+(?:\/[^ \"\t\n\r<]*)?)/":"\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>"|regex_replace:"/(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)/i":"\\1<a href=\"javascript:InternalMailer('\\2@\\3','','','','email_addy');\">\\2@\\3</a>"|regex_replace:"/,\"|\.\"|\)\"|\)\.\"|\.\)\"/":"\""|replace:"\n":"<br>"}</div>	{* crmv@27617 *}
	{else}
		{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
		<div class="{$DIVCLASS} detailCellInfo" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
			<span class="wrap-content" id="dtlview_{$label}"> {* crmv@104459 *}
				{$keyval|regex_replace:"/(^|[\n ])([\w]+?:\/\/.*?[^ \"\n\r\t<]*)/":"\\1<a href=\"\\2\" target=\"_blank\">\\2</a>"|regex_replace:"/(^|[\n ])((www|ftp)\.[\w\-]+\.[\w\-.\~]+(?:\/[^ \"\t\n\r<]*)?)/":"\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>"|regex_replace:"/(^|[\n ])([a-z0-9&\-_.]+?)@([\w\-]+\.([\w\-\.]+\.)*[\w]+)/i":"\\1<a href=\"javascript:InternalMailer('\\2@\\3','','','','email_addy');\">\\2@\\3</a>"|regex_replace:"/,\"|\.\"|\)\"|\)\.\"|\.\)\"/":"\""|replace:"\n":"<br>"}	{* crmv@27617 *}
			</span>
			<div id="editarea_{$label}" style="display:none;">
				<textarea id="txtbox_{$label}" name="{$keyfldname}" class="detailedViewTextBox" cols="90" rows="8">{$keyval|replace:"<br>":"\n"}</textarea>
			</div>
		</div>
	{/if}
{elseif $keyid eq '21'} <!--TextArea/Street-->
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
	<div class="{$DIVCLASS} detailCellInfo" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
		<span class="wrap-content" id="dtlview_{$label}">{$keyval}</span> {* crmv@104459 *}
		<div id="editarea_{$label}" style="display:none;">
			<textarea id="txtbox_{$label}" name="{$keyfldname}" class="detailedViewTextBox" rows="2">{$keyval|regex_replace:"/<br\s*\/>/":""}</textarea>                                            		  
		</div>
	</div>
{elseif $keyid eq '52' || $keyid eq '77' || $keyid eq '54'}	{* crmv@101683 *}
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
	<div class="{$DIVCLASS} detailCellInfo" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
		<span id="dtlview_{$label}">{$keyval}</span>
		<div id="editarea_{$label}" style="display:none;">
			<select id="txtbox_{$label}" name="{$keyfldname}" class="detailedViewTextBox">
				{foreach item=arr key=uid from=$keyoptions}
					{foreach key=sel_value item=value from=$arr}
						<option value="{$uid}" {$value}>{if $APP.$sel_value}{$APP.$sel_value}{else}{$sel_value}{/if}</option>
					{/foreach}
				{/foreach}
			</select>
		</div>
	</div>	
{elseif $keyid eq '53'} <!--Assigned To-->	{* crmv@31171 *}
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
	{* crmv@47567 *}
	{assign var=fld_value value=''}
	{foreach item=arr key=id from=$keyoptions.1}
		{foreach key=sel_value item=value from=$arr}
			{if $value eq 'selected'}
				{assign var=fld_value value=$id}
				{assign var=fld_displayvalue value=$sel_value}
			{/if}
		{/foreach}
	{/foreach}
	{assign var=fld_secondvalue value=''}
	{foreach item=arr key=id from=$keyoptions.2}
		{foreach key=sel_value item=value from=$arr}
			{if $value eq 'selected'}
				{assign var=fld_secondvalue value=$id}
				{assign var=fld_displaysecondvalue value=$sel_value}
			{/if}
		{/foreach}
	{/foreach}
	{* crmv@47567e *}
	<div class="{$DIVCLASS} detailCellInfo" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
		<span id="dtlview_{$label}">
			{* crmv@47567 *}
			{if $keyoptions.0 eq 'User'}
				{if $keyadmin eq 1}
					<a href="{$keyseclink.0}">{$keyval}</a>         
				{else}	
					{$keyval}
				{/if}
			{else}
				{if $keyadmin eq 1}
					<a href="{$keyseclink.0}">{$fld_displaysecondvalue}</a>         
				{else}	
					{$fld_displaysecondvalue}
				{/if}
			{/if}
			{* crmv@47567e *}
		</span>
		<div id="editarea_{$label}" style="display:none;">
			{* crmv@120899 *}
			{if empty($fldgroupname)}
				{assign var=fldgroupname value="assigned_group_id"}
			{/if}
			{if empty($fldothername)}
				{assign var=fldothername value="other_assigned_user_id"}
			{/if}
			{if empty($assign_user_div)}
				{assign var="assign_user_div" value="assign_user"}
			{/if}
			{if empty($assign_team_div)}
				{assign var="assign_team_div" value="assign_team"}
			{/if}
			{if empty($assign_other_div)}
				{assign var="assign_other_div" value="assign_other"}
			{/if}
			{if empty($assigntypename)}
				{assign var="assigntypename" value="assigntype"}
			{/if}
			{* crmv@120899e *}
			<table cellspacing="0" cellpadding="0" width="100%">
			<tr>
				<td width="20%" style="padding-right:5px;">
					<input type="hidden" id="hdtxt_{$label}" value="{$keyval}" />
					{if $keyoptions.2 neq ''}
						<select id="assigntype" name="assigntype" class="detailedViewTextBox" onChange='toggleAssignType(this.value,"{$assign_user_div}","{$assign_team_div}","{$assign_other_div}"); document.DetailView.{$keyfldname}_display.value=""; document.DetailView.{$keyfldname}.value=""; enableReferenceField(document.DetailView.{$keyfldname}_display); document.DetailView.assigned_group_id_display.value=""; document.DetailView.assigned_group_id.value=""; enableReferenceField(document.DetailView.assigned_group_id_display); closeAutocompleteList("{$keyfldname}_display"); closeAutocompleteList("assigned_group_id_display");'>	{* crmv@29190 crmv@120899 *}
							<option value="U" {if $keyoptions.0 eq 'User'}selected{/if}>{$APP.LBL_USER}</option>
							<option value="T" {if $keyoptions.0 eq 'Group'}selected{/if}>{$APP.LBL_GROUP}</option>
						</select>
					{else}
					{*//crmv@36944*}
						<div style="display:none">
							<select id="assigntype" name="assigntype" class="detailedViewTextBox" onChange='toggleAssignType(this.value,"{$assign_user_div}","{$assign_team_div}","{$assign_other_div}"); document.DetailView.{$keyfldname}_display.value=""; document.DetailView.{$keyfldname}.value=""; enableReferenceField(document.DetailView.{$keyfldname}_display); document.DetailView.assigned_group_id_display.value=""; document.DetailView.assigned_group_id.value=""; enableReferenceField(document.DetailView.assigned_group_id_display); closeAutocompleteList("{$keyfldname}_display"); closeAutocompleteList("assigned_group_id_display");'>	{* crmv@29190 crmv@120899 *}
								<option value="U" selected>{$APP.LBL_USER}</option>
								<option value="T">{$APP.LBL_GROUP}</option>
							</select>
						</div>						
					{/if}
					{*//crmv@36944 e*}
				</td>
				<td width="80%" style="position:relative">
					{if $keyoptions.0 eq 'User'}
						<span id="assign_user" style="display: block;">
					{else}
						<span id="assign_user" style="display: none;">
					{/if}
					<input id="txtbox_U{$label}" name="{$keyfldname}" type="hidden" value="{$fld_value}">
					{assign var=fld_style value='class="detailedViewTextBox" readonly'}
					{if $fld_displayvalue|trim eq ''}
						{assign var=fld_displayvalue value='LBL_SEARCH_STRING'|getTranslatedString}
						{assign var=fld_style value='class="detailedViewTextBox"'}
					{/if}
					<input id="{$keyfldname}_display" name="{$keyfldname}_display" type="text" value="{$fld_displayvalue}" {$fld_style} autocomplete="off"> {* crmv@113776 *}
					<script type="text/javascript">
						initAutocompleteUG('Users','{$keyfldname}','{$keyfldname}_display','{$JSON->encode($keyoptions.1)|addslashes}','{$label}',document.DetailView);	{* crmv@52561 *}
					</script>
					<div class="dvtCellInfoImgRx">
						<i class="vteicon md-link" tabindex="{$vt_tab}" title="{$APP.LBL_SELECT}" onclick='toggleAutocompleteList("{$keyfldname}_display");'>view_list</i>
						<i class="vteicon md-link" title="{$APP.LBL_CLEAR}" onClick="jQuery(this).closest('form').get(0).{$keyfldname}.value=''; jQuery('#{$keyfldname}_display').val(''); enableReferenceField(jQuery('#{$keyfldname}_display').get(0));">clear</i> {* crmv@81167 *}
					</div>
					</span>
					{if $keyoptions.0 eq 'Group'}
						<span id="assign_team" style="display: block;">
					{else}
						<span id="assign_team" style="display: none;">
					{/if}
                    <input id="txtbox_G{$label}" name="assigned_group_id" type="hidden" value="{$fld_secondvalue}">
					{assign var=fld_style value='class="detailedViewTextBox" readonly'}
					{if $fld_displaysecondvalue|trim eq ''}
						{assign var=fld_displaysecondvalue value='LBL_SEARCH_STRING'|getTranslatedString}
						{assign var=fld_style value='class="detailedViewTextBox"'}
					{/if}
					<input id="assigned_group_id_display" name="assigned_group_id_display" type="text" value="{$fld_displaysecondvalue}" {$fld_style} autocomplete="off"> {* crmv@113776 *}
					<script type="text/javascript">
						initAutocompleteUG('Groups','assigned_group_id','assigned_group_id_display','{$JSON->encode($keyoptions.2)|addslashes}','{$label}',document.DetailView);	{* crmv@52561 *}
					</script>
					<div class="dvtCellInfoImgRx">
						<i class="vteicon md-link" tabindex="{$vt_tab}" title="{$APP.LBL_SELECT}" onclick='toggleAutocompleteList("assigned_group_id_display");'>view_list</i>
						<i class="vteicon md-link" title="{$APP.LBL_CLEAR}" onClick="jQuery(this).closest('form').get(0).assigned_group_id.value=''; jQuery('#assigned_group_id_display').val(''); enableReferenceField(jQuery('#assigned_group_id_display').get(0));">clear</i> {* crmv@81167 *}
					</div>
				</span>
				</td>
			</tr>
			</table>
		</div>
	</div>
{elseif $keyid eq '99'}<!-- Password Field-->
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label}
	<div class="{$DIVCLASSOTHER}dvtCellInfoOff detailCellInfo">
		{$CHANGE_PW_BUTTON}
	</div>  
{elseif $keyid eq '56'} <!--CheckBox-->
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
	<div class="{$DIVCLASS} detailCellInfo" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
		<span id="dtlview_{$label}">{$keyval}</span>
		<div id="editarea_{$label}" style="display:none;" class="checkbox">
			<label>
			{if $keyval eq $APP.yes}	{* crmv@34700 *}
				<input id="txtbox_{$label}" type="checkbox" name="{$keyfldname}" value="1" checked>
			{else}
				<input id="txtbox_{$label}" type="checkbox" name="{$keyfldname}" value="0">
			{/if}
			</label>
		</div>
	</div>    
{elseif $keyid eq '156'} <!--CheckBox for is admin-->
	{if $smarty.request.record neq $CURRENT_USERID && $keyadmin eq 1}
		{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
		<div class="{$DIVCLASS} detailCellInfo" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
			<span id="dtlview_{$label}">{if $APP.$keyval!=''}{$APP.$keyval}{elseif $MOD.$keyval!=''}{$MOD.$keyval}{else}{$keyval}{/if}</span>
			<div id="editarea_{$label}" style="display:none;">
				{if $keyval eq 'on'}                                              		  
					<input id="txtbox_{$label}" name="{$keyfldname}" type="checkbox" style="border:1px solid #bababa;" checked value="1">
				{else}
					<input id="txtbox_{$label}" type="checkbox" name="{$keyfldname}" style="border:1px solid #bababa;" value="0">
				{/if}
			</div>
	{else}
		{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label}
		<div class="{$DIVCLASSOTHER}dvtCellInfoOff detailCellInfo">{$keyval}
	{/if}
	</div>    
{elseif $keyid eq 83} <!-- Handle the Tax in Inventory -->
	{if !empty($TAX_DETAILS)}
		{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label}
		<table border="0" cellspacing="2" cellpadding="5" width="100%" class="small">
			{foreach item=tax key=count from=$TAX_DETAILS}
				<tr style="height:25px">
					<td width="20%">{$tax.taxlabel} {$APP.COVERED_PERCENTAGE}</td>
					<td class="{$DIVCLASSOTHER}dvtCellInfoOff detailCellInfo" width="80%">{$tax.percentage_fmt}</td> {* crmv@118512 *}
				</tr>
			{/foreach}
		</table>
	{/if}
{elseif $keyid eq 5}
	{* Initialize the date format if not present *}
	{if empty($dateFormat)}
		{assign var="dateFormat" value=$APP.NTC_DATE_FORMAT|@parse_calendardate}
	{/if}
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
	<div class="{$DIVCLASS} detailCellInfo" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
		<span id="dtlview_{$label}">
			{if $keysecid eq 'ok'}<font color='green'>{elseif $keysecid eq 'ko'}<font color='red'>{/if}{$keyval}{if $keysecid eq 'ok'}</font><img src="{'ok.gif'|resourcever}">{elseif $keysecid eq 'ko'}</font><img src="{'no.gif'|resourcever}">{/if}
		</span>
		<div id="editarea_{$label}" style="display:none;">
			{* crmv@82419 crmv@100585 *}
			<table border="0" cellpadding="0" cellspacing="0">
				<tr>
					<td>
						<input class="detailedViewTextBox" maxlength="10" type="text" id="txtbox_{$label}" name="{$keyfldname}" maxlength='100' value="{$keyval|regex_replace:'/[^-]*(--)[^-]*$/':''}" />
					</td>
					<td>
						<i class="vteicon md-link" id="jscal_trigger_{$keyfldname}">event</i>
					</td>
				</tr>
				<tr>
					<td colspan=2>
						<font size=1><em old="(yyyy-mm-dd)">({$DATE_FORMAT})</em></font> {* crmv@181170 *}
					</td>	
				</tr>										
			</table>
			<script type="text/javascript">
				(function() {ldelim}
					setupDatePicker('txtbox_{$label}', {ldelim}
						trigger: 'jscal_trigger_{$keyfldname}',
						date_format: "{$dateFormat|strtoupper}".replace('%Y', 'YYYY').replace('%M', 'MM').replace('%D', 'DD'),
						language: "{$APP.LBL_JSCALENDAR_LANG}",
					{rdelim});
				{rdelim})();
			</script>
			{* crmv@82419e crmv@100585e *}
		</div>
	</div>
{elseif $keyid eq 69}<!-- for Image Reflection -->
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label}
	<div>{$keyval}</div>
{* crmv@16265 crmv@43764 *}
{elseif $keyid eq '199'}
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
	<div class="{$DIVCLASS} detailCellInfo" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
		<span id="dtlview_{$label}">{$keyval}</span>
		<div id="editarea_{$label}" style="display:none;">
			<input class="detailedViewTextBox" type="password" id="txtbox_{$label}" name="{$keyfldname}" maxlength='100' value="" />
		</div>
	</div>
{* crmv@16265e crmv@43764e *}
{elseif $keyid eq 1020}	<!-- crmv@18338 -->
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
	<div class="{$DIVCLASS} detailCellInfo" {if $AJAXEDITTABLEPERM}onclick="hndMobileClick(this);" ondblclick="{if !empty($AJAXONCLICKFUNCT)}{$AJAXONCLICKFUNCT}{else}hndMouseClick{/if}({$keyid},'{$label}','{$keyfldname}',this);"{/if}> {* crmv@63001 *}
		<span id="dtlview_{$label}">{$keyoptions}</span>
		<div id="editarea_{$label}" style="display:none;">
			<input class="detailedViewTextBox"  type="input" id="txtbox_{$label}" name="{$keyfldname}" maxlength='100' value="{$keyval}" />
		</div>
	</div>
{elseif !empty($keyid)}
	{include file="FieldHeader.tpl" uitype=$keyid mandatory=$keymandatory label=$label}
	<div class="{$DIVCLASSOTHER}dvtCellInfoOff detailCellInfo">{$keyval}</div>
{/if}
{if !empty($keyid) && $OLD_STYLE eq true}
	{include file="FieldButtons.tpl" uitype=$keyid mandatory=$keymandatory label=$label ajaxEditablePerm=$AJAXEDITTABLEPERM}
{/if}