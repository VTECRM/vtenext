{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@158543 *}
<script language="JavaScript" type="text/javascript" src="{"include/js/customview.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/layouteditor.js"|resourcever}"></script>

{literal}
<script language="javascript">

var for_mobile = 0;
var gselected_fieldtype = '';

LayoutEditor = VTE.extend(LayoutEditor, {

	showFieldPopup: function(self) {
		var type = jQuery('input[name=activitytype]:checked').val();
		var blockid = (type == 'E' ? events_blockid : calendar_blockid);
	
		showFloatingDiv('addfield_'+blockid, self);
	},

	deleteCalendarField: function(id, fld_module, colName, uitype) {
        var me = this;
		
		vteconfirm(alert_arr.ARE_YOU_SURE_YOU_WANT_TO_DELETE, function(yes) {
			if (yes) {
				me.ajaxCall('deleteCustomField', {
					fld_id: id,
					colName: colName,
					uitype: uitype
				}, {
					reloadVersion: false,
					container: 'none',
				}, function() {
					gselected_fieldtype = '';
					location.reload();
				});
			}
		});
	},
	
	getCreateCustomFieldForm: function(modulename, blockid, mode) {
		var me = this;
		var actType = jQuery('input[name=activitytype]:checked').val();

		if (!validateLayoutEditor(blockid)) return false;

		var type = jQuery("#fieldType_"+blockid).val();
		var label = jQuery("#fldLabel_"+blockid).val();
		var fldLength = jQuery("#fldLength_"+blockid).val();
		var fldDecimal = jQuery("#fldDecimal_"+blockid).val();
		var fldPickList = jQuery("#fldPickList_"+blockid).val();
		//crmv@113771
		var fldOnclick = jQuery("#fldOnclick_"+blockid).val();
		var fldCode = jQuery("#fldCode_"+blockid).val();
		//crmv@113771e
		
		//crmv@101683
		var fldCustomUserPick = jQuery('#fldCustomUserPick_'+blockid).val();
		if (fldCustomUserPick != null && fldCustomUserPick.length > 0) fldCustomUserPick = JSON.stringify(fldCustomUserPick); else fldCustomUserPick = '';
		//crmv@101683e
		
		me.ajaxCall('addCustomField', {
			blockid: blockid,
			fieldType: type,
			fldLabel: label,
			fldLength: fldLength,
			fldDecimal: fldDecimal,
			fldPickList: fldPickList,
			//crmv@113771
			fldOnclick: fldOnclick,
			fldCode: fldCode,
			//crmv@113771e
			fldCustomUserPick: fldCustomUserPick,
			activity_type: actType,
		}, {
			checkError: true,
			reloadVersion: false,
			container: 'none',
		}, function(result) {
			gselected_fieldtype = '';
			location.reload();
		});
	}
	
});

</script>
{/literal}

<div id="createcf" style="display:block;position:absolute;width:500px;"></div>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tbody><tr>
        <td valign="top"></td>
        <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->


	<div align=center>
			{include file='SetMenu.tpl'}
			{include file='Buttons_List.tpl'} {* crmv@30683 *}
			<!-- DISPLAY -->
			{if $MODE neq 'edit'}
			<b><font color=red>{$DUPLICATE_ERROR} </font></b>
			{/if}
			
				<table class="settingsSelUITopLine" border="0" cellpadding="5" cellspacing="0" width="100%">
				<tbody><tr>
					<td rowspan="2" valign="top" width="50"><img src="{'custom.gif'|resourcever}" alt="{$MOD.LBL_USERS}" title="{$MOD.LBL_USERS}" border="0" height="48" width="48"></td>
					<td class="heading2" valign="bottom"><b>{$MOD.LBL_SETTINGS} &gt; {$MOD.LBL_CUSTOM_FIELD_SETTINGS}</b></td> <!-- crmv@30683 -->
				</tr>

				<tr>
					<td class="small" valign="top">{$MOD.LBL_CREATE_AND_MANAGE_USER_DEFINED_FIELDS}</td>
				</tr>
				</tbody></table>
				
				<br>
				<table border="0" cellpadding="10" cellspacing="0" width="100%">
				<tbody><tr>
				<td>

				<div id="cfList">
					{include file="CustomFieldEntries.tpl"}
                </div>	
			{include file="Settings/ScrollTop.tpl"}
			</td>
			</tr>
			</table>
		<!-- End of Display -->
		
		</td>
        </tr>
        </table>
        </td>
        </tr>
        </table>
        </div>

        </td>
        <td valign="top"></td>
        </tr>
</tbody>
</table>
<br>