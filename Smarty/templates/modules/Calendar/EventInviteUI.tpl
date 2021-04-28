{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@98866 *}
{* crmv@103922 *}

<table border="0" cellspacing="0" cellpadding="2" width="100%" bgcolor="#FFFFFF">
	<tr>
		<td valign="top">
			<table border="0" cellspacing="0" cellpadding="2" width="100%">
				<tr>
					<td>
						<table style="height: 80%; width: 98%; margin: 0px auto" border="0" cellspacing="0" class="small">
							<tr style="height: 10%; width: 100%">
								<td align="left" colspan="3" style="width: 100%;" nowrap>
									<table width="100%" border="0" cellspacing="0" cellpadding="2">
										<tr>
											<td width="30%">
												<div class="dvtCellInfo">
													<select id="quick_parent_type" name="Users" class="detailedViewTextBox" onchange="resetSearch('bbit-cal-txtSearch');">
														{foreach from=$QUICKPARENTTYPE key=module item=transmodule} 
														{if $module eq 'Users'} 
															{assign var="selected" value="selected"} 
														{else} 
															{assign var="selected" value=""} 
														{/if}
														<option value="{$module}"{$selected}>{$transmodule|getTranslatedString}</option>
														{/foreach}
													</select>
												</div>
											</td>
											<td width="65%">
												<div class="dvtCellInfo">
													<input id="bbit-cal-txtSearch" name="bbit-cal-txtSearch" class="detailedViewTextBox textbox-fill-input" value="{'LBL_SEARCH_STRING'|getTranslatedString}">
												</div>
											</td>
											<td width="5%" align="right">
												{* crmv@118184 *}
												<i class="vteicon md-link" onclick="inviteesPopup('selectedTable','quick_parent_type')">view_list</i>
												<i class="vteicon md-link" id="imgSearch" onclick="jQuery('#bbit-cal-txtSearch').val('');getObj('bbit-cal-txtSearch').focus();">highlight_off</i>
												{* crmv@118184e *}
											</td>
										</tr>
									</table>
								</td>
							</tr>
							<tr style="height: 10px"></tr>
							<tr>
								<td align="center" style="width:100%" valign="middle">
									<input type="hidden" id="CalendarUsers_idlist" value="{$INVITEDUSERS_LIST}" />
									<input type="hidden" id="CalendarContacts_idlist" value="{$INVITEDCONTACTS_LIST}" />
									<div id="CalendarAll_list" class="txtBox1">
										{foreach item=arr key=userid from=$INVITEDUSERS}
											<span id="CalendarUsers_list_{$userid}" class="invBubble">
												<table cellpadding="3" cellspacing="0" class="small">
													<tr>
														<td rowspan="2" valign="middle"><img src="{$arr.img}" class="userAvatar" /></td>
														<td>{$arr.full_name}</td>
														<td rowspan="2" align="right" valign="top">
															<div class="invRemoveIcon" id="CalendarUsers_list_{$userid}_remove">
																<i class="vteicon small" title="Delete" onclick="removeInvite('{$userid}', 'Users');">clear</i>
															</div>
														</td>
													</tr>
													<tr>
														<td>{$arr.username}</td>
													</tr>
												</table>
											</span>
										{/foreach}
										{foreach item=contactname key=contactid from=$INVITEDCONTACTS}
											<span id="CalendarContacts_list_{$contactid}" class="invBubble">
												<table cellpadding="3" cellspacing="0" class="small">
													<tr>
														<td rowspan="2" valign="middle">
															<i class="icon-module icon-contacts" data-first-letter="C"></i>
														</td>
														<td rowspan="2" valign="middle">{$contactname}</td>
														<td align="right" valign="top">
															<div class="invRemoveIcon" id="CalendarUsers_list_{$contactid}_remove">
																<i class="vteicon small" title="Delete" onclick="removeInvite('{$contactid}', 'Contacts');">clear</i>
															</div>
														</td>
													</tr>
													<tr><td>&nbsp;</td></tr>
												</table>
											</span>
										{/foreach}
									</div>
								</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

{literal}
<script type="text/javascript">

	jQuery(document).ready(function() {
		var empty_str = '{/literal}{"LBL_SEARCH_STRING"|getTranslatedString}{literal}';
		
		var appendTo = jQuery("#bbit-cal-txtSearch").parent(); // crmv@140781
	
		jQuery("#bbit-cal-txtSearch")
		.focus(function(){
			var term = this.value;
			if ( term.length == 0 || this.value == empty_str) {
				this.value = '';
			}
		})
		// don't navigate away from the field on tab when selecting an item
		.bind("keydown", function(event) {
			if (event.keyCode === jQuery.ui.keyCode.TAB && jQuery(this).data("autocomplete").menu.active) {
				event.preventDefault();
			}
		}).autocomplete({
			appendTo : appendTo, // crmv@140781
			source : function(request, response) {
				//crmv@91082
				if (!SessionValidator.check()) {
					SessionValidator.showLogin();
					return false;
				}
				//crmv@91082e
				var uextraids = jQuery('#CalendarUsers_idlist').val();
				var cextraids = jQuery('#CalendarContacts_idlist').val();
				var mode = jQuery('#quick_parent_type').val();
				
				jQuery.getJSON("index.php?module=Calendar&action=CalendarAjax&file=AutocompleteInvite&mode=" + mode + "&uidlist=" + encodeURIComponent(uextraids) + "&cidlist=" + encodeURIComponent(cextraids), {
					term : extractLast(request.term)
				}, response);
			},
			search : function() {
				// custom minLength
				var term = extractLast(this.value);
				if (term.length < 3) {
					return false;
				}
			},
			focus : function() {
				// prevent value inserted on focus
				return false;
			},
			select : function(event, ui) {
				var terms = split(this.value);
				// remove the current input
				terms.pop();
				// add placeholder to get the comma-and-space at the end
				terms.push('');
				
				this.value = terms.join(', ');
				
				var mode = jQuery('#quick_parent_type').val();

				addInvitee(ui.item.value, mode, ui.item); // crmv@118184

				return false;
			}
		});
		
		function split(val) {
			return val.split(/,\s*/);
		}
		
		function extractLast(term) {
			return split(term).pop();
		}
		
	});

	// crmv@118184
	function addInvitee(id, mode, item) {
	
		// try to decode it
		if (typeof item == 'string') {
			try {
				item = JSON.parse(item);
			} catch (e) {
				return false;
			}
		}
		
		var idlist = getObj('Calendar' + mode + '_idlist').value.split('|');
		
		// check for duplicates
		if (idlist.indexOf(id) >= 0) return false;
	
		// add the selected item
		var span = '<span id="Calendar' + mode + '_list_' + id + '" class="invBubble">'
			+ '<table cellpadding="3" cellspacing="0" class="small"><tr>';
			
		span += '<td rowspan="2">';
		if (item.img) {
			span += '<img src="' + item.img + '" class="userAvatar" />';
		} else {
			var modeLow = mode.toLowerCase();
			var moduleFirst = mode.charAt(0).toUpperCase();
			span += '<i class="icon-module icon-' + modeLow + '" data-first-letter="' + moduleFirst + '"></i>';
		}
		span += '</td>';
		
		if (item.entityname) {
			span += '<td rowspan="2">';
		} else {
			span += '<td>';
		}
		var label = item.full_name || item.entityname;
		span += label;
		span += '</td>';
		
		span += '<td align="right" valign="top">'
			+ '<div class="invRemoveIcon" id="Calendar' + mode + '_list_' + id + '_remove">'
			+ '<i class="vteicon small" title="Delete" onClick="removeInvite(\''+id+'\', \''+mode+'\');">clear</i>'
			+ '</div>'
			+ '</td></tr>'
			+ '<tr>';
		
		span += '<td>';
		var label = item.user_name || '&nbsp;';
		span += label;
		span += '</td>';
			
		span += '</tr></table></span>';
			
		// append the box
		jQuery("#CalendarAll_list").append(span);

		// and the id to the list
		getObj('Calendar' + mode + '_idlist').value = idlist.join('|') + id + '|';
	}
	// crmv@118184e
	
	function removeInvite(id, mode) {
		var tmp = getObj('Calendar' + mode + '_idlist').value;
		tmp = tmp.replace(id, '');
		getObj('Calendar' + mode + '_idlist').value = tmp;

		var d = document.getElementById('CalendarAll_list');
		var olddiv = document.getElementById('Calendar' + mode + '_list_' + id);
		d.removeChild(olddiv);
	}
	
	function removeAllUsers() {
		getObj('CalendarUsers_idlist').value = '';
		getObj('CalendarContacts_idlist').value = '';
		getObj('CalendarAll_list').innerHTML = '';
	}
	
</script>
{/literal}