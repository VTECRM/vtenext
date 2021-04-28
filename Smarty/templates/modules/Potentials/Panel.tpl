{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{* crmv@44187 crmv@45699 crmv@53923 crmv@191138 crmv@205899 *}

<div id="potPanelMainDiv" class="vte-card" style="display:none;">

<style type="text/css">
{literal}
	.potPanelContDiv {
		padding:0px;
	}
	.potPanelEditCont {
		display:none;
	}
	.potPaneTabTitle {
		font-weight: bold;
	}
{/literal}
</style>

<script type="text/javascript" src="modules/Charts/Charts.js"></script>
<script type="text/javascript">
{literal}
// crmv@104975
function potPanelClickTab(thistd, showid) {
	
	// hide other potential sub panels
	jQuery('#potPanelContTable .potPanelContDiv').hide();
	
	changeTab(gVTModule, null, showid, thistd);
	
	// show the container
	jQuery('#potPanelMainDiv').show();	
	
	// crmv@82770
	if (showid == 'potPanelCharts' && window.VTECharts) {
		VTECharts.refreshAll();
	}
	// crmv@82770e
}
// crmv@104975e

potPanelOldEditRow = null;
function potPanelEditContactRow(contactid) {
	if (potPanelOldEditRow) potPanelCancelContactRow(potPanelOldEditRow);
	jQuery('#potPanelContRow_'+contactid+' .potPanelEditCont').show();
	jQuery('#potPanelContRow_'+contactid+' .potPanelDisplayValue').hide();
	potPanelOldEditRow = contactid;
}

function potPanelDeleteContactRow(potid, contactid, relmodule, elem) {
	if (confirm(alert_arr.ARE_YOU_SURE)) {
		var surl = 'index.php?module=Potentials&action=PotentialsAjax&file=updateRelations&mode=delete&parentid='+potid+'&idlist='+contactid+'&destination_module='+relmodule+'&no_redirect=true';

		jQuery.ajax({
			url: surl,
			type: 'GET',
			success: function() {
				var tr = jQuery(elem).closest('tr');
				tr.remove();
			}
		});
	}
}

function potPanelCancelContactRow(contactid) {
	jQuery('#potPanelContRow_'+contactid+' .potPanelEditCont').hide();
	jQuery('#potPanelContRow_'+contactid+' .potPanelDisplayValue').show();
	potPanelOldEditRow = null;
}

function potPanelSaveContactRow(potid, contactid, relmodule) {
	var extra_options = {},
		valuesMulti = '';

 	jQuery.each(jQuery('#potPanelContRow_'+contactid+' :input').serializeArray(), function() {
		// for multi select role
		if (this.name in extra_options) {
			extra_options[this.name] += ',' + this.value;
		} else {
			extra_options[this.name] = this.value;
		}
	});
	
	jQuery.extend(extra_options, {
		'no_redirect' : 1,
		'extra_relation_info' : 1,
	});

	linkModules('Potentials', potid, relmodule, contactid, extra_options, function(data) {
		potPanelReload(potid);
	});
}

function potPanelReload(potid,panelTab) {
	if (typeof(panelTab) == 'undefined' || panelTab == '') panelTab = 'potPanelRelations';
	jQuery.ajax({
		url: 'index.php?module=Potentials&action=PotentialsAjax&file=InfoPanel&record='+potid,
		type: 'POST',
		success: function(data) {
			jQuery('#potPanelMainDiv').replaceWith(data);
			jQuery('#potPanelMainDiv').show();
			potPanelClickTab(jQuery('#DetailViewTabs .dvtSelectedCell'),panelTab);
		}
	});
}

function closePopupPotPanel(mode, module, recordid) {
	if (mode == 'pot_select_partners' || mode == 'pot_select_other_contacts' || mode == 'pot_add_partners' || mode == 'pot_add_other_contacts') {
		potPanelReload(recordid, 'potPanelRelations');
	} else if (mode == 'pot_select_competitor' || mode == 'pot_add_competitor') {
		potPanelReload(recordid, 'potPanelRelations');
	}
	closePopup(this);
}
function potPanelRemoveCompetitors(potid) {
	var list = jQuery('#potPanelCompetitors input.linkNoPropagate:checked');
	if (list && list.length > 0 && confirm(alert_arr.ARE_YOU_SURE)) {
		var ids = [];
		list.each(function(index, item) {
			ids.push(parseInt(item.id.replace('list_cbox_', '')));
		});
		var surl = 'index.php?module=Potentials&action=PotentialsAjax&file=updateRelations&mode=delete&parentid='+potid+'&idlist='+ids.join(';')+'&destination_module=Accounts&no_redirect=true';

		jQuery.ajax({
			url: surl,
			type: 'GET',
			success: function(data) {
				list.each(function(index, item) {
					var tr = jQuery(item).closest('tr');
					tr.remove();
				});
			}
		});
	}
}
{/literal}
</script>

<table id="potPanelContTable" border="0" cellspacing="0" cellpadding="0" width="100%"><tr><td>

<div id="potPanelRelations" class="potPanelContDiv">
	{* MAIN CONTACTS *}
	<table class="vtetable">
		<thead>
			<tr>
				<th colspan="7" class="dvInnerHeader potPaneTabTitle">{$MOD.MainAccountContacts}</th>
			</tr>
			<tr>
				<th width="50">&nbsp;</th>
				<th width="20%">{$APP.Name}</th>
				<th width="20%">{$APP.Email}</th>
				<th width="20%">{$APP.Phone}</th>
				<th width="100">{'LBL_MAIN'|getTranslatedString:'Messages'}</th>
				<th>{$APP.LBL_ROLE}</th>
				<th width="100">&nbsp;</th>
			</tr>
		</thead>
		<tbody>
			{foreach item=contact from=$ACCOUNT_CONTACTS}
				<tr id="potPanelContRow_{$contact.contactid}">
					<td class="text-nowrap"><span class="potPanelDisplayValue"><a href="javascript:void(0)" onclick="potPanelEditContactRow('{$contact.contactid}')"><i class="vteicon" title="{$APP.LBL_EDIT_BUTTON}">edit</i></a></span></td>
					<td><a href="index.php?module=Contacts&action=DetailView&record={$contact.contactid}">{$contact.firstname} {$contact.lastname}</a></td>
					<td><a href="javascript:InternalMailer('{$contact.contactid}', '{$contact.email_fieldid}', 'email', 'Contacts', 'record_id')">{$contact.email}</a></td>
					<td><a href="javascript:void(0)" onclick="startCall('{$contact.phone}', '{$contact.contactid}');">{$contact.phone}</a></td>
					<td>
						<span class="potPanelDisplayValue">
							{if $contact.main_contact}
								{$APP.LBL_YES}
							{else}
								{$APP.LBL_NO}
							{/if}
						</span>
						<span class="potPanelEditCont">
							<input id="potPanelContMain_{$contact.contactid}" type="checkbox" name="main_contact" {if $contact.main_contact}checked=""{/if}/>
						</span>
					</td>
					<td>
						{assign var="selected_roles" value=","|explode:$contact.contact_role}
						<span class="potPanelDisplayValue">
							{foreach item="selrole" from=$selected_roles name="roles"}
								{$selrole|getTranslatedString:$MODULE}{if $smarty.foreach.roles.last eq false}, {/if}
							{/foreach}
						</span>
						<span class="potPanelEditCont">
							<select id="potPanelContSelect_{$contact.contactid}" class="detailedViewTextBox" name="contact_role" multiple="">
								{foreach key=plistval item=plistlabel from=$CONTACT_ROLES}
									<option value="{$plistval}" {if $plistval|in_array:$selected_roles}selected=""{/if}>{$plistlabel}</option>
								{/foreach}
							</select>
						</span>
					</td>
					<td align="right">
						<span class="potPanelEditCont">
							<a href="javascript:void(0)" onclick="potPanelSaveContactRow('{$ID}', '{$contact.contactid}', 'Contacts')" class="save">{$APP.LBL_SAVE_LABEL}</a> - <a href="javascript:void(0)" onclick="potPanelCancelContactRow('{$contact.contactid}')" class="cancel">{$APP.LBL_CANCEL_BUTTON_LABEL}</a>
						</span>
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>

	<br>

	{* OTHER CONTACTS *}
	<table class="vtetable">
		<thead>
			<tr>
				<th colspan="7" class="dvInnerHeader potPaneTabTitle">{$MOD.LBL_OTHER_CONTACTS}
					<div style="float:right">
						<button type="button" class="crmbutton save" onclick="LPOP.openPopup('{$MODULE}', '{$ID}', 'pot_select_other_contacts', {ldelim}'show_module':'Contacts', 'modules_list':'Contacts', 'callback_close':'parent.closePopupPotPanel'{rdelim});">{$APP.LBL_SELECT} {$APP.Contact}</button>
						<button type="button" class="crmbutton save" onclick="LPOP.openPopup('{$MODULE}', '{$ID}', 'pot_add_other_contacts', {ldelim}'show_module':'Contacts', 'modules_list':'Contacts', 'show_only':'create', 'callback_close':'parent.closePopupPotPanel'{rdelim});">{$APP.LBL_ADD_ITEM} {$APP.Contact}</button>
					</div>
				</th>
			</tr>
			{if count($OTHER_CONTACTS) > 0}
				<tr>
					<th width="50">&nbsp;</th>
					<th width="20%">{$APP.Name}</th>
					<th width="20%">{$APP.Email}</th>
					<th width="20%">{$APP.Phone}</th>
					<th width="100">{'LBL_MAIN'|getTranslatedString:'Messages'}</th>
					<th>{$APP.LBL_ROLE}</th>
					<th width="100">&nbsp;</th>
				</tr>
			{/if}
		</thead>
		<tbody>
			{foreach item=contact from=$OTHER_CONTACTS}
				<tr id="potPanelContRow_{$contact.contactid}" >
					<td class="text-nowrap"><span class="potPanelDisplayValue"><a href="javascript:void(0)" onclick="potPanelEditContactRow('{$contact.contactid}')"><i class="vteicon" title="{$APP.LBL_EDIT_BUTTON}">edit</i></a> <a href="javascript:void(0)" onclick="potPanelDeleteContactRow('{$ID}', '{$contact.contactid}', 'Contacts', this)"><i class="vteicon" title="{$APP.LBL_DELETE_BUTTON}">delete</i></a></span></td>
					<td><a href="index.php?module=Contacts&action=DetailView&record={$contact.contactid}">{$contact.firstname} {$contact.lastname}</a></td>
					<td><a href="javascript:InternalMailer('{$contact.contactid}', '{$contact.email_fieldid}', 'email', 'Contacts', 'record_id')">{$contact.email}</a></td>
					<td><a href="javascript:void(0)" onclick="startCall('{$contact.phone}', '{$contact.contactid}');">{$contact.phone}</a></td>
					<td>
						<span class="potPanelDisplayValue">
							{if $contact.main_contact}
								{$APP.LBL_YES}
							{else}
								{$APP.LBL_NO}
							{/if}
						</span>
						<span class="potPanelEditCont">
							<input id="potPanelContMain_{$contact.contactid}" type="checkbox" name="main_contact" {if $contact.main_contact}checked=""{/if}/>
						</span>
					</td>
					<td>
						{assign var="selected_roles" value=","|explode:$contact.contact_role}
						<span class="potPanelDisplayValue">
							{foreach item="selrole" from=$selected_roles name="roles"}
								{$selrole|getTranslatedString:$MODULE}{if $smarty.foreach.roles.last eq false}, {/if}
							{/foreach}
						</span>
						<span class="potPanelEditCont">
							<select id="potPanelContSelect_{$contact.contactid}" class="detailedViewTextBox" name="contact_role" multiple="">
								{foreach key=plistval item=plistlabel from=$CONTACT_ROLES}
									<option value="{$plistval}" {if $plistval|in_array:$selected_roles}selected=""{/if}>{$plistlabel}</option>
								{/foreach}
							</select>
						</span>
					</td>
					<td align="right">
						<span class="potPanelEditCont">
							<a href="javascript:void(0)" onclick="potPanelSaveContactRow('{$ID}', '{$contact.contactid}', 'Contacts')" class="save">{$APP.LBL_SAVE_LABEL}</a> - <a href="javascript:void(0)" onclick="potPanelCancelContactRow('{$contact.contactid}')" class="cancel">{$APP.LBL_CANCEL_BUTTON_LABEL}</a>
						</span>
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>

	<br>

	{* PARTNERS *}
	<table class="vtetable">
		<thead>
			<tr>
				<th colspan="7" class="dvInnerHeader potPaneTabTitle">{$MOD.LBL_PARTNERS}
					<div style="float:right">
						<button type="button" class="crmbutton save" onclick="LPOP.openPopup('{$MODULE}', '{$ID}', 'pot_select_partners', {ldelim}'show_module':'Accounts', 'modules_list':'Accounts', 'callback_close':'parent.closePopupPotPanel'{rdelim});">{$APP.LBL_SELECT} {'Partner'|getTranslatedString:'Accounts'}</button>
						<button type="button" class="crmbutton save" onclick="LPOP.openPopup('{$MODULE}', '{$ID}', 'pot_add_partners', {ldelim}'show_module':'Accounts', 'modules_list':'Accounts', 'show_only':'create', 'callback_close':'parent.closePopupPotPanel'{rdelim});">{$APP.LBL_ADD_ITEM} {'Partner'|getTranslatedString:'Accounts'}</button>
					</div>
				</th>
			</tr>
			{if count($PARTNERS) > 0}
				<tr>
					<th width="50">&nbsp;</th>
					<th width="20%">{$APP.Account}</th>
					<th width="20%">{$APP.Email}</th>
					<th width="20%">{$APP.Phone}</th>
					<th width="100">{'LBL_MAIN'|getTranslatedString:'Messages'}</th>
					<th>{$APP.LBL_ROLE}</th>
					<th width="100">&nbsp;</th>
				</tr>
			{/if}
		</thead>
		<tbody>
			{foreach item=partner from=$PARTNERS}
				<tr id="potPanelContRow_{$partner.accountid}">
					<td class="text-nowrap"><span class="potPanelDisplayValue"><a href="javascript:void(0)" onclick="potPanelEditContactRow('{$partner.accountid}')"><i class="vteicon" title="{$APP.LBL_EDIT_BUTTON}">edit</i></a> <a href="javascript:void(0)" onclick="potPanelDeleteContactRow('{$ID}', '{$partner.accountid}', 'Accounts', this)"><i class="vteicon" title="{$APP.LBL_DELETE_BUTTON}">delete</i></a></span></td>
					<td><a href="index.php?module=Accounts&action=DetailView&record={$partner.accountid}">{$partner.accountname}</a></td>
					<td><a href="javascript:InternalMailer('{$partner.accountid}', '{$partner.email_fieldid}', 'email1', 'Accounts', 'record_id')">{$partner.email}</a></td>
					<td><a href="javascript:void(0)" onclick="startCall('{$partner.phone}', '{$partner.accountid}');">{$partner.phone}</a></td>
					<td>
						<span class="potPanelDisplayValue">
							{if $partner.main_account}
								{$APP.LBL_YES}
							{else}
								{$APP.LBL_NO}
							{/if}
						</span>
						<span class="potPanelEditCont">
							<input id="potPanelContMain_{$partner.accountid}" type="checkbox" name="main_account" {if $partner.main_account}checked=""{/if}/>
						</span>
					</td>
					<td>
						{assign var="selected_roles" value=","|explode:$partner.partner_role}
						<span class="potPanelDisplayValue">
							{foreach item="selrole" from=$selected_roles name="roles"}
								{$selrole|getTranslatedString:$MODULE}{if $smarty.foreach.roles.last eq false}, {/if}
							{/foreach}
						</span>
						<span class="potPanelEditCont">
							<select name="partner_role" class="detailedViewTextBox" multiple="">
								{foreach key=plistval item=plistlabel from=$PARTNER_ROLES}
									<option value="{$plistval}" {if $plistval|in_array:$selected_roles}selected=""{/if}>{$plistlabel}</option>
								{/foreach}
							</select>
						</span>
					</td>
					<td align="right">
						<span class="potPanelEditCont">
							<a href="javascript:void(0)" onclick="potPanelSaveContactRow('{$ID}', '{$partner.accountid}', 'Accounts')" class="save">{$APP.LBL_SAVE_LABEL}</a> - <a href="javascript:void(0)" onclick="potPanelCancelContactRow('{$partner.accountid}')" class="cancel">{$APP.LBL_CANCEL_BUTTON_LABEL}</a>
						</span>
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>

	<br>

	{* COMPETITORS *}
	<table class="vtetable">
		<thead>
			<tr>
				<th colspan="7" class="dvInnerHeader potPaneTabTitle">{$APP.Competitors}
					<div style="float:right">
						<button type="button" class="crmbutton save" onclick="LPOP.openPopup('{$MODULE}', '{$ID}', 'pot_select_competitor', {ldelim}'show_module':'Accounts', 'modules_list':'Accounts', 'callback_close':'parent.closePopupPotPanel'{rdelim});">{$APP.LBL_SELECT} {"Competitor"|getTranslatedString:'Accounts'}</button>
						<button type="button" class="crmbutton save" onclick="LPOP.openPopup('{$MODULE}', '{$ID}', 'pot_add_competitor', {ldelim}'show_module':'Accounts', 'modules_list':'Accounts', 'show_only':'create', 'callback_close':'parent.closePopupPotPanel'{rdelim});">{$APP.LBL_ADD_ITEM} {"Competitor"|getTranslatedString:'Accounts'}</button>
					</div>
				</th>
			</tr>
			{if count($COMPETITORS) > 0}
				<tr>
					<th width="50">&nbsp;</th>
					<th width="20%">{$APP.Account}</th>
					<th width="20%">{$APP.Email}</th>
					<th width="20%">{$APP.Phone}</th>
					<th width="100"></th>
					<th>&nbsp;</th>
					<th width="100">&nbsp;</th>
				</tr>
			{/if}
		</thead>
		<tbody>
			{foreach item=compet from=$COMPETITORS}
				<tr id="potPanelContRow_{$compet.accountid}">
					<td class="text-nowrap"><span class="potPanelDisplayValue"><div style="display:inline-block;width:22px">&nbsp;</div><a href="javascript:void(0)" onclick="potPanelDeleteContactRow('{$ID}', '{$compet.accountid}', 'Accounts', this)"><i class="vteicon" title="{$APP.LBL_DELETE_BUTTON}">delete</i></a></span></td>
					<td><a href="index.php?module=Accounts&action=DetailView&record={$compet.accountid}">{$compet.accountname}</a></td>
					<td><a href="javascript:InternalMailer('{$compet.accountid}', '{$compet.email_fieldid}', 'email1', 'Accounts', 'record_id')">{$compet.email}</a></td>
					<td><a href="javascript:void(0)" onclick="startCall('{$compet.phone}', '{$compet.accountid}');">{$compet.phone}</a></td>
					<td>&nbsp;</td>
					<td>&nbsp;</td>
					<td align="right">
						<span class="potPanelEditCont">
							<a href="javascript:void(0)" onclick="potPanelSaveContactRow('{$ID}', '{$compet.accountid}', 'Accounts')" class="save">{$APP.LBL_SAVE_LABEL}</a> - <a href="javascript:void(0)" onclick="potPanelCancelContactRow('{$compet.accountid}')" class="cancel">{$APP.LBL_CANCEL_BUTTON_LABEL}</a>
						</span>
					</td>
				</tr>
			{/foreach}
		</tbody>
	</table>
</div>

<div id="potPanelLines" style="display:none" class="potPanelContDiv">
	{if $ACTIVEQUOTE_COUNT eq 0}
		<br>
		<div style="text-align:center;padding:6px;">{'NoProductLineInfo'|getTranslatedString:'Potentials'}</div>
		<br>
	{elseif $ACTIVEQUOTE_COUNT > 1}
		<br>
		<div style="text-align:center;padding:6px;">{'NoProductLineInfo'|getTranslatedString:'Potentials'}</div>
		<br>
	{else}
		<div style="text-align:center;padding:6px;">{$MOD.LBL_PRODLINES_FOR_QUOTE} <a href="index.php?module=Quotes&action=DetailView&record={$PRODLINES.quote.record_id}">{$PRODLINES.quote.subject}</a></div>
		<table class="vtetable">
			<thead>
				<tr>
					<th>{'SINGLE_ProductLines'|getTranslatedString:'ProductLines'}</th>
					<th width="100">{$APP.Products}</th>
					<th width="100" align="right">{$MOD.Amount}</th>
					<th width="100" align="right">{$APP.LBL_MARGIN}</th>
				</tr>
			</thead>
			<tbody>
				{foreach item=line from=$PRODLINES.list}
					<tr>
						{if $line.productlineid > 0}
							<td><a href="index.php?module=ProductLines&action=DetailView&record={$line.productlineid}">{$line.linename}</a></td>
						{else}
							<td><a href="javascript:void(0)">{$line.linename}</a></td>
						{/if}
						<td>{$line.products|@count}</td>
						<td align="right">{$line.total|formatUserNumber}</td>
						<td align="right">{if $line.margin neq ''}{$line.margin*100|round}%{/if}</td>
					</tr>
				{/foreach}
				<tr>
					<td>{$APP.LBL_TOTAL}</td>
					<td>{$PRODLINES.countprods}</td>
					<td align="right">{$PRODLINES.linestotal|formatUserNumber}</td>
					<td align="right">{if $PRODLINES.linesmargin neq ''}{$PRODLINES.linesmargin*100|round}%{/if}</td>
				</tr>
			</tbody>
		</table>
		<br>
		<p>{$MOD.AmountsWithoutTaxes}</p>
		<br>
	{/if}
</div>

<div id="potPanelCharts" style="display:none" class="potPanelContDiv">
	<table border="0" cellspacing="2" cellpadding="2" width="100%">
		<tr>
			{foreach item=chart from=$CHARTS name=charts}
				<td>{$chart}</td>
				{if $smarty.foreach.charts.iteration % 2 == 0}</tr><tr>{/if}
			{/foreach}
		</tr>
	</table>
	{*if $ACTIVEQUOTE_COUNT eq 0}
		<br>
		<div style="text-align:center;padding:6px;">{'NoActiveQuotes'|getTranslatedString:'Potentials'}</div>
		<br>
	{elseif $ACTIVEQUOTE_COUNT > 1}
		<br>
		<div style="text-align:center;padding:6px;">{'TooManyActiveQuotes'|getTranslatedString:'Potentials'}</div>
		<br>
	{else}
		TODO!
	{/if*}
</div>

</td></tr></table>

<script type="text/javascript">
	jQuery('.slvButtonAdd').hide(); // crmv@SHAK
</script>

</div>

{$PROCESSGRAPH}	{* crmv@149529 *}