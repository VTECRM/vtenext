{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* picklist collegate - crmv@30528 *}
<script language="javascript" type="text/javascript">
	{literal}
	var plistHasChanges = false;
	var plistLastModule = null;

	function selectModule(sel) {
		// controllo se ci sono modifiche pendenti
		// TODO: rimetti sul vecchio valore
		var mod = jQuery(sel).val();
		if (plistHasChanges) {
			// removed
		}

		// mostro solo righe inerenti
		var cont = jQuery('#div_listconn');
		if (mod == 'All') {
			cont.find('tr').show();
			jQuery('#linkpick_tab_cont').hide();
		} else {
			cont.find('tr[id^=plist_tr]').hide();
			cont.find('tr[id^=plist_tr_'+mod+']').show();
			//jQuery('#linkpick_tab_cont').show();
		}


		// nascondo tutti
		jQuery('#linkpick_tab_cont').find('select[name^=linkpick_selpick]').hide();
		// mostro quelli giusti
		jQuery('#linkpick_selpick1_'+mod).show();
		jQuery('#linkpick_selpick2_'+mod).show();
		jQuery('#div_picklistMatrix').html('');
		plistLastModule = mod;
		plistHasChanges = false;
	}

	function showLinkMatrix() {
		var mod = jQuery('#linkpick_selmod').val();

		var plist1 = jQuery('#linkpick_selpick1_'+mod).val();
		var plist2 = jQuery('#linkpick_selpick2_'+mod).val();

		if (plist1 == plist2) {
			{/literal}
			return window.alert('{$MOD.LBL_SELECT_DIFF_PICKLIST}');
			{literal}
		}

		jQuery('#status').show();
		jQuery.ajax({
			url: 'index.php?module=Settings&action=SettingsAjax&file=LinkedPicklist&subaction=getlinktable&modname='+mod+'&picklist1='+plist1+'&picklist2='+plist2,
			type: 'POST',
			success: function(data) {
				var container = jQuery('#div_picklistMatrix');
				container.html(data).show();
				jQuery('#btn_saveLinkMatrix').show();
				jQuery('#btn_showLinkMatrix').hide();
				jQuery('#linkpick_selpick1_'+mod).attr('disabled', true);
				jQuery('#linkpick_selpick2_'+mod).attr('disabled', true);
				jQuery('#status').hide();
			}
		});

	}

	function editConnection(module, picksrc, pickdest) {
		jQuery('#linkpick_selmod').val(module).change();
		jQuery('#linkpick_selpick1_'+module).val(picksrc);
		jQuery('#linkpick_selpick2_'+module).val(pickdest);
		jQuery('#linkpick_selmod').attr('disabled', true);
		jQuery('#div_listconn').hide();
		jQuery('#linkpick_selpick1_'+module).attr('disabled', true);
		jQuery('#linkpick_selpick2_'+module).attr('disabled', true);
		jQuery('#btn_addConnection').hide();
		jQuery('#linkpick_tab_cont').show();
		jQuery('#btn_saveLinkMatrix').show();
		jQuery('#btn_showLinkMatrix').hide();
		showLinkMatrix();
	}

	function addConnection() {
		var mod = jQuery('#linkpick_selmod').val();

		if (mod == 'All') {
			{/literal}
			return window.alert('{$MOD.LBL_SELECT_MODULE_FIRST}');
			{literal}
		}

		jQuery('#linkpick_selmod').attr('disabled', true);
		jQuery('#div_listconn').hide();
		jQuery('#linkpick_selpick1_'+mod).attr('disabled', false);
		jQuery('#linkpick_selpick2_'+mod).attr('disabled', false);
		jQuery('#linkpick_tab_cont').show();
		jQuery('#btn_addConnection').hide();
		jQuery('#btn_saveLinkMatrix').hide();
		jQuery('#btn_showLinkMatrix').show();
	}

	function cancelConnection() {
		jQuery('#linkpick_selmod').attr('disabled', false);
		jQuery('#div_listconn').show();
		jQuery('#linkpick_tab_cont').hide();
		jQuery('#btn_addConnection').show();
		jQuery('#div_picklistMatrix').hide();
	}

	function saveLinkMatrix() {
		plistHasChanges = false;

		// picklist names
		var plist1 = jQuery('#input_picklist1').val();
		var plist2 = jQuery('#input_picklist2').val();
		var modname = jQuery('#linkpick_selmod').val();

		// retrieve matrix
		var valarray = [];
		jQuery('#div_picklistMatrix input[name^=plistmatrix_]').each(function(el) {
			valarray.push(parseInt(this.value));
		});

		jQuery('#status').show();
		jQuery.ajax({
			//crmv@33511
			url: 'index.php?module=Settings&action=SettingsAjax&file=LinkedPicklist&subaction=savepicklist',
			data: '&modname='+modname+'&picklist1='+plist1+'&picklist2='+plist2+'&matrix='+valarray.join(','),
			type: 'POST',
			//crmv@33511e
			success: function(data) {
				jQuery('#status').hide();
				if (data.match(/ERROR::.*/)) {
					window.alert(data.replace('ERROR::', ''));
				} else {
					location.reload();
				}
			}
		});
	}

	function unlinkPicklist(mod, plist1, plist2) {

		{/literal}
		var question = '{$MOD.LBL_CONFIRM_DELETE}';
		{literal}
		if (!window.confirm(question)) return;

		jQuery('#status').show();
		jQuery.ajax({
			url: 'index.php?module=Settings&action=SettingsAjax&file=LinkedPicklist&subaction=unlinkpicklist&picklist1='+plist1+'&picklist2='+plist2+'&modname='+mod,
			type: 'POST',
			success: function(data) {
				//jQuery('#status').hide();
				location.reload();
			}
		});

	}

	function plistMatrixToggle(tdelem) {

		plistHasChanges = true;

		var jelem = jQuery(tdelem);
		jelem.toggleClass("plist_td_down plist_td_up");

		var ival = jelem.find('input').val();
		jelem.find('input').val(1 - ival);
	}

	function plistEnableAll() {
		var cont = jQuery('#table_matrixvalues');
		cont.find('td[class=plist_td_down]').each(function(index, el) {
			plistMatrixToggle(el);
		});
	}

	function plistDisableAll() {
		var cont = jQuery('#table_matrixvalues');
		cont.find('td[class=plist_td_up]').each(function(index, el) {
			plistMatrixToggle(el);
		});
	}

	{/literal}
</script>

{* TODO: move away *}
<style>
{literal}
.plist_td_down {
	background-color: #f0f0f0;
	color: #c0c0c0;
	cursor: pointer;
	padding: 1px;
}

.plist_td_down:hover {
	background-color: #d0d0d0;
	color: #404040;
}

.plist_td_up {
	background-color: #f0f0f0;
	cursor: pointer;
	padding: 1px;
}

.plist_td_up:hover {
	background-color: #d0d0d0;
}

.table_plistmatrix {
	
}

.td_plistmatrix_head {
	font-weight: bold;
	padding: 2px;
	padding-bottom: 4px;
}
{/literal}
</style>

<table align="center" border="0" cellpadding="0" cellspacing="0"
	width="100%">
	<tbody>
		<tr>
			<td valign="top"></td>
			<td class="showPanelBg" style="padding: 5px;" valign="top"
				width="100%">
				<div align="center">
					{include file='SetMenu.tpl'} 
					{include file='Buttons_List.tpl'} {*crmv@30683 *}
					{* crmv@30683 *}
					<table border=0 cellspacing=0 cellpadding=5 width=100%
						class="settingsSelUITopLine">
						<tr>
							<td width=50 rowspan=2 valign=top><img
								src="{'linkedpicklist.png'|resourcever}"
								alt="{$MOD.LBL_EDIT_LINKED_PICKLIST}" width="48" height="48"
								border=0 title="{$MOD.LBL_PROFILES}"></td>
							{if $smarty.request.module_manager eq 'yes'}
							<td class="heading2" valign="bottom">
								<b><a href="index.php?module=Settings&action=ModuleManager&parenttab=Settings">{$MOD.VTLIB_LBL_MODULE_MANAGER}</a>
								&gt;<a href="index.php?module=Settings&action=ModuleManager&module_settings=true&formodule={$MODULE}&parenttab=Settings">{if $APP.$MODULE } {$APP.$MODULE} {elseif $MOD.$MODULE} {$MOD.$MODULE} {else} {$MODULE} {/if}</a> &gt;
								{$MOD.LBL_EDIT_LINKED_PICKLIST}</b>
							</td>
							{else}
							<td class=heading2 valign=bottom><b> {$MOD.LBL_SETTINGS} >{$MOD.LBL_EDIT_LINKED_PICKLIST} </b></td>	<!-- crmv@30683 -->
							{/if}
						</tr>
						<tr>
							<td valign=top class="small">{$MOD.LBL_EDIT_LINKED_PICKLIST_DESC}</td>
						</tr>
					</table>
					{* crmv@30683e *}
					<br> {* modules list *}

					<table width="100%" cellpadding="0" cellspacing="0">
						<tr>
							<td>
								{if $smarty.request.module_manager eq 'yes'}
									<input type="hidden" name="linkpick_selmod" id="linkpick_selmod" value="{$MODULE}">
								{else}
									{$MOD.LBL_SELECT_MODULE}
									<select id="linkpick_selmod" name="linkpick_selmod" onchange="selectModule(this);">
										<option value="All" {if $MODULE eq ''}selected{/if}>{"LBL_ALL"|getTranslatedString:"APP_STRINGS"}</option>
										{foreach key=mod item=label from=$MODULES}
										<option value="{$mod}" {if $MODULE eq $mod}selected{/if}>{$label}</option>
										{/foreach}
									</select>
								{/if}
							</td>
							<td align="right"><input id="btn_addConnection" type="button"
								class="small crmbutton create"
								value="{'LBL_ADD_BUTTON'|getTranslatedString:'APP_STRINGS'}"
								onclick="addConnection()" />
							</td>
						</tr>

					</table>

					<br /> <br />

					<div id="div_listconn">
						{if count($PLIST_CONNECTIONS) > 0}
						<table class="listTable" width="100%" cellspacing="0"
							cellpadding="5">
							<tr>
								<td class="small colHeader">{"LBL_MODULE"|getTranslatedString:"APP_STRINGS"}</td>
								<td class="small colHeader">{"LBL_FIRST_PICKLIST"|getTranslatedString:"Settings"}</td>
								<td class="small colHeader">{"LBL_SECOND_PICKLIST"|getTranslatedString:"Settings"}</td>
								<td class="small colHeader" align="right">{"LBL_ACTIONS"|getTranslatedString:"APP_STRINGS"}</td>
							</tr>
							{foreach item=conn from=$PLIST_CONNECTIONS}
							<tr id="plist_tr_{$conn.modulename}_{counter}">
								<td class="small listTableRow">{$conn.modulelabel}</td>
								<td class="small listTableRow">{$conn.label1}</td>
								<td class="small listTableRow">{$conn.label2}</td>
								<td class="small listTableRow" align="right"><a
									href="javascript:editConnection('{$conn.modulename}','{$conn.picksrc}','{$conn.pickdest}')"><img
										border="0" title="{'LBL_EDIT'|getTranslatedString}"
										alt="{'LBL_EDIT'|getTranslatedString}"
										style="cursor: pointer;"
										id="expressionlist_editlink_{$workflow->id}"
										src="{'editfield.gif'|resourcever}" /> </a> <a
									href="javascript:unlinkPicklist('{$conn.modulename}','{$conn.picksrc}','{$conn.pickdest}');"><img
										border="0" title="{'LBL_DELETE'|getTranslatedString}"
										alt="{'LBL_DELETE'|getTranslatedString}"
										style="cursor: pointer;"
										id="expressionlist_editlink_{$workflow->id}"
										src="{'delete.gif'|resourcever}" /> </a>
								</td>
							</tr>
							{/foreach}
						</table>
						{/if}

					</div>

					<br />

					<table id="linkpick_tab_cont" class="small listTable" width="100%"
						cellpadding="5" cellspacing="0">
						<tr>
							<td class="colHeader">{$MOD.LBL_FIRST_PICKLIST}</td>
							<td class="colHeader">{* picklist selection *} {foreach key=mod
								item=cont from=$PLISTS} <select id="linkpick_selpick1_{$mod}"
								name="linkpick_selpick1_{$mod}" style="display: none"> {foreach
									item=plistdata from=$cont} {if $plistdata.uitype neq 33} {*
									remove multiselection picklist *}
									<option value="{$plistdata.fieldname}">{$plistdata.fieldlabel}</option>
									{/if} {/foreach}
							</select> {/foreach}
							</td>
							<td class="colHeader">{$MOD.LBL_SECOND_PICKLIST}</td>
							<td class="colHeader">{foreach key=mod item=cont from=$PLISTS} <select
								id="linkpick_selpick2_{$mod}" name="linkpick_selpick2_{$mod}"
								style="display: none"> {foreach item=plistdata from=$cont}
									<option value="{$plistdata.fieldname}">{$plistdata.fieldlabel}</option>
									{/foreach}
							</select> {/foreach}
							</td>

							<td class="colHeader" align="right"><input
								id="btn_saveLinkMatrix" type="button"
								class="small crmbutton save" style="display: none"
								value="{$APP.LBL_SAVE_LABEL}" onclick="saveLinkMatrix();" /> <input
								id="btn_showLinkMatrix" type="button"
								class="small crmbutton save"
								value="{'LNK_LIST_NEXT'|getTranslatedString} &gt;"
								onclick="showLinkMatrix();" /> <input type="button"
								class="small crmbutton cancel"
								value="{'LBL_CANCEL_BUTTON_LABEL'|getTranslatedString}"
								onclick="cancelConnection();" />
							</td>

						</tr>

					</table>

					<br />

					<div id="div_picklistMatrix"></div>

				</div>
			</td>
		</tr>
	</tbody>
</table>
<script language="javascript" type="text/javascript">
	selectModule('#linkpick_selmod');
</script>