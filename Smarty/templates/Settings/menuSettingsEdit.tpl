{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@18592 crmv@54707 *}
<table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"> <!-- crmv@30683 -->
<tr>
	<td valign="top"></td>
    <td class="showPanelBg" style="padding: 5px;" valign="top" width="100%"> <!-- crmv@30683 -->
	<div align=center>
<!-- in setMenu table is opened and ends with one open td tag -->
		{include file='SetMenu.tpl'}
		{include file='Buttons_List.tpl'} {* crmv@30683 *}
		<form name="EditView" method="POST" action="index.php">
		<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
		<input type="hidden" name="module" value="Settings">
		<input type='hidden' name='parenttab' value='Settings'>
		<input type="hidden" name="action">

		<table border=0 cellspacing=0 cellpadding=5 width=100% class="settingsSelUITopLine">
		<tr>
			<td width=50 rowspan=2 valign=top><img src="{'menuSettings.gif'|resourcever}" width="48" height="48" border=0 ></td>
			<td class=heading2 valign=bottom><b><a href="index.php?module=Settings&action=menuSettings&parenttab=Settings">{$MOD.LBL_SETTINGS}</a> > {$MOD.LBL_MENU_TABS}</b></td>
		</tr>
		<tr>
			<td valign=top class="small">{$MOD.LBL_MENU_TABS_DESCRIPTION}</td>
		</tr>
		</table>
        <br>
        
        <div width="100%" height="30%" align="right">
	        <input title="{$APP.LBL_SAVE_BUTTON_TITLE}" accesskey="{$APP.LBL_SAVE_BUTTON_KEY}" class="small crmbutton save"  name="button" value="{$APP.LBL_SAVE_BUTTON_LABEL}" onclick="VteJS_DialogBox.block(); selectModuleLists(); this.form.action.value='SaveParentTabs'; this.form.submit();" style="min-width: 70px;" type="button" />
			<input title="{$APP.LBL_CANCEL_BUTTON_TITLE}" accesskey="{$APP.LBL_CANCEL_BUTTON_KEY}" class="small crmbutton cancel" name="button" value="{$APP.LBL_CANCEL_BUTTON_LABEL}" onclick="window.history.back()" style="min-width: 70px;" type="button" />
        </div>
        
        <table class="tableHeading" border="0" cellpadding="5" cellspacing="0" width="100%">
        <tr>
        <td><strong>{$MOD.LBL_MENU_TYPE}</strong></td>
        <td class="small" align=right>&nbsp;</td>
        </tr>
        </table>
        <table class="small" border="0" cellpadding="5" cellspacing="0" width="100%">
        <tr>
	        <td class="small cellLabel" width="50%">
				<input type="radio" name="menu_type" value="modules" id="menu_type_modules" {if $MENU_LAYOUT.type eq 'modules'}checked{/if}/><label for="menu_type_modules">{$MOD.LBL_MENU_MODULELIST}</label><br />
				<input type="radio" name="menu_type" value="tabs" id="menu_type_tabs" {if $MENU_LAYOUT.type neq 'modules'}checked{/if}/><label for="menu_type_tabs">{$MOD.LBL_MENU_TABLIST}</label>
			</td>
	        <td class="small cellLabel" width="50%">
				<input type="checkbox" id="enable_areas" name="enable_areas" {$ENABLE_AREAS} />
				<label for="enable_areas">{'LBL_ENABLE_AREAS'|getTranslatedString:'Settings'}</label>
			</td>
		</tr>
		<tr height="5"><td colspan="2"></td></tr>
        </table>
        
        <table class="tableHeading" border="0" cellpadding="5" cellspacing="0" width="100%">
        <tr>
        <td><strong>{$MOD.LBL_MENU_TABS_AVAIL}</strong></td>
        <td class="small" align=right>&nbsp;</td>
        </tr>
        </table>
		<table id="modules_Table" class="small" border="0" cellpadding="5" cellspacing="0" width="100%" style="display:none;">
        	<tr>
        		<td width=40% align=center colspan="2" class="small colHeader" style="border-left: 1px solid #ddd;"><b>{$MOD.LBL_FAST_MODULES}</b></td>
				<td width=20% align=center class="small colHeader"></td>
				<td width=40% align=center colspan="2" class="small colHeader"><b>{$MOD.LBL_OTHER_MODULES}</b></td>
        	</tr>
        	<tr class="cellLabel">
				<td width=5% align=center>
					<a href="javascript:;"><i class="vteicon" onclick="moveUp('left')">arrow_upward</i></a><br /><br />
					<a href="javascript:;"><i class="vteicon" onclick="moveDown('left')">arrow_downward</i></a> {* crmv@116876 *}
				</td>
				<td width=40% align=center>
					<div>
						<select name="fast_modules[]" id="left" class="small notdropdown" size=5 multiple style="height:300px;width:100%">
						{foreach key=id item=info from=$VisibleModuleList}
							<option value="{$info.tabid}">{$info.name|getTranslatedString:$info.name}</option>	{* crmv@32217 *}
						{/foreach}
				    	</select>
				 	</div>
				 </td>
				 <td width=10% align=center>
				  	<div>
						<i name="left2right" class="vteicon md-link" onclick="moveLeftRight(this)">arrow_forward</i><br/><br/>
				    	<i name="right2left" class="vteicon md-link" onclick="moveLeftRight(this)">arrow_back</i>
				  	</div>
				</td>
				<td width=45% align=center colspan="2">
					<div>
						<select name="other_modules[]" id="right" class="small notdropdown" size=5 multiple style="height:300px;width:100%">
					    {foreach key=id item=info from=$OtherModuleList}
							<option value="{$info.tabid}">{$info.name|getTranslatedString:$info.name}</option>	{* crmv@32217 *}
				      	{/foreach}
					    </select>
					</div>
				</td>
			</tr>
        </table>
        <table id="tabs_Table" class="small" border="0" cellpadding="5" cellspacing="0" width="100%" style="display:none;">
        <tr>
        	<td class="small colHeader" align="center" style="border-left: 1px solid #ddd;">{$MOD.LBL_MENU_TABS_ACTIVE}</td>
            <td class="small colHeader">{$MOD.LBL_MENU_TABS_NAME}</td>
        </tr>
		{foreach key=id item=tab from=$TABS}
			<tr>
               	<td class="small cellLabel" width="30px" align="center" >
               		{if $tab.hidden eq '0'}
               			{assign value="checked" var=checked}
               		{else}
               			{assign value="" var=checked}
               		{/if}
               		<input type="checkbox" name="ckb_{$id}" value="check" {$checked}>
               	</td>
				<td class="small cellLabel">{$tab.parenttab_label|getTranslatedString}</td>
			</tr>
		{/foreach}
        </table>
        </form>
        <!-- chiudo SetMenu.tpl i -->
		</td></tr></table>
		</td></tr></table>
		<!-- chiudo SetMenu.tpl e -->
	</div>
	</td>
	<td valign="top"></td>
	</tr>
</table>
<script type="text/javascript">
{literal}

var menu_type = jQuery('input:radio[name=menu_type]:checked').val();
jQuery('#'+menu_type+'_Table').show();

jQuery('input:radio[name=menu_type]').click(function() {
	jQuery('#modules_Table').hide();
	jQuery('#tabs_Table').hide();
	menu_type = this.value;
	jQuery('#'+menu_type+'_Table').show();
});

function moveLeftRight(self) {
	var arr = jQuery(self).attr("name").split("2");
	var from = arr[0];
	var to = arr[1];
	jQuery("#" + from + " option:selected").each(function(){
		jQuery("#" + to).append(jQuery(this).clone());
		jQuery(this).remove();
	});
	return false;
}

function selectModuleLists() {
	jQuery("select[name='fast_modules[]'] option").each(function () {
		jQuery(this).selected(true);
	});
	jQuery("select[name='other_modules[]'] option").each(function () {
		jQuery(this).selected(true);
	});
}

/**
 * this function is used to move the selected option up in the assigned picklist
 */
function moveUp(el){
	var elem = document.getElementById(el);
	if(elem.options.selectedIndex>=0){
		for (var i=1;i<elem.options.length;i++){
			if(elem.options[i].selected == true){
				//swap with one up
				var first = elem.options[i-1];
				var second = elem.options[i];
				var temp = new Array();
				
				temp.value = first.value;
				temp.innerHTML = first.innerHTML;
				
				first.value = second.value;
				first.innerHTML = second.innerHTML;
				
				second.value = temp.value;
				second.innerHTML = temp.innerHTML;
				
				first.selected = true;
				second.selected = false;
			}
		}
	}
}

/**
 * this function is used to move the selected option down in the assigned picklist
 */
function moveDown(el){
	var elem = document.getElementById(el);
	if(elem.options.selectedIndex>=0){
		for (var i=elem.options.length-2;i>=0;i--){
			if(elem.options[i].selected == true){
				//swap with one down
				var first = elem.options[i+1];
				var second = elem.options[i];
				var temp = new Array();
				
				temp.value = first.value;
				temp.innerHTML = first.innerHTML;
				
				first.value = second.value;
				first.innerHTML = second.innerHTML;
				
				second.value = temp.value;
				second.innerHTML = temp.innerHTML;
				
				first.selected = true;
				second.selected = false;
			}
		}
	}
}
{/literal}
</script>
{* crmv@18592e *}