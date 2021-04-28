{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{literal} 
<script type='text/javascript'>
// crmv@99018
function vtlib_toggleModule(module, action, type, mlabel) {
	if (!type) type = '';

	if (action == 'module_disable' && type == '') {
		var label = alert_arr.LBL_DISABLE_MODULE.replace('%s', mlabel);
		vteconfirm(label, function(yes) {
			if (yes) doToggle();
		});
	} else {
		doToggle();
	}
	
	function doToggle() {
		var data = "module_name=" + encodeURIComponent(module) + "&" + action + "=true" + "&module_type=" + type;
		jQuery('#status').show();
		jQuery.ajax({
			url: 'index.php?module=Settings&action=SettingsAjax&file=ModuleManager',
			method: 'post',
			data: data,
			success: function(response) {
				jQuery('#status').hide();
				// Reload the page to apply the effect of module setting
				window.location.href = 'index.php?module=Settings&action=ModuleManager&parenttab=Settings';
			}
		});
	}
}
// crmv@99018e
</script>
{/literal}

<div id="vtlib_modulemanager" style="display:block;position:absolute;width:500px;"></div>

<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tr>
	<td valign="top"></td>
    <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->

	<div align=center>
		{include file='SetMenu.tpl'}
		{include file='Buttons_List.tpl'} {* crmv@30683 *}
		<table class="settingsSelUITopLine" border="0" cellpadding="5" cellspacing="0" width="100%">
		<tr>
			<td rowspan="2" valign="top" width="50"><img src="{'vtlib_modmng.gif'|resourcever}" alt="{$MOD.LBL_MODULE_MANAGER}" title="{$MOD.LBL_MODULE_MANAGER}" border="0" height="48" width="48"></td>
			<td class="heading2" valign="bottom"><b> {$MOD.LBL_SETTINGS} &gt; {$MOD.VTLIB_LBL_MODULE_MANAGER}</b></td> <!-- crmv@30683 -->
		</tr>

		<tr>
			<td class="small" valign="top">{$MOD.VTLIB_LBL_MODULE_MANAGER_DESCRIPTION}</td>
		</tr>
		</table>
				
		
		<table border="0" cellpadding="10" cellspacing="0" width="100%">
		<tr>
			<td>
				<div id="vtlib_modulemanager_list">
                	{include file="Settings/ModuleManager/ModuleManagerAjax.tpl"}
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
</table>
<br>