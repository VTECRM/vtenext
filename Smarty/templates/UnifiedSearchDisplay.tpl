{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@187493 *}

<script language="JavaScript" type="text/javascript" src="{"include/js/general.js"|resourcever}"></script>
<script language="JavaScript" type="text/javascript" src="include/js/search.js"></script>
<script language="JavaScript" type="text/javascript" src="{"include/js/ListView.js"|resourcever}"></script>
{include file='Buttons_List.tpl'}	{* crmv@30828 *}

<ul id="UnifiedSearchTitle" class="vteUlTable buttonsList buttonsListFixed" data-minified="enabled">
	<li>
		<span class="dvHeaderText" style="padding-left:15px">
			<span class="recordTitle1">{'LBL_SEARCH_RESULTS_FOR'|getTranslatedString} "{$SEARCH_STRING}"</span>
		</span>
	</li>
	<li id="global_search_total_count" style="padding-left:30px">&nbsp;</li>
	<li class="pull-right" style="padding-right:30px">
		<ul class="vteUlTable">
			<li nowrap align="right">{'LBL_SHOW_RESULTS'|getTranslatedString}</li>
			<li>
				<div class="dvtCellInfo">
					<select id="global_search_module" name="global_search_module" onChange="displayModuleList(this);" class="detailedViewTextBox">
						<option value="All">{'COMBO_ALL'|getTranslatedString}</option>
						{foreach key="MODULE_NAME" item="OBJECT_NAME" from=$MODULES_LIST}
							{assign var="SELECTED" value=""}
							{if $SEARCH_MODULE neq '' && $MODULE_NAME eq $SEARCH_MODULE}
								{assign var="SELECTED" value="selected"}
							{elseif $SEARCH_MODULE eq '' && $MODULE_NAME eq 'All'}
								{assign var="SELECTED" value="selected"}
							{/if}
							<option value="{$MODULE_NAME}" {$SELECTED}>{$MODULE_NAME|getTranslatedString:$MODULE_NAME}</option>
						{/foreach}
					</select>
				</div>
			</li>
		</ul>
	</li>
</ul>
<div id="UnifiedSearchTitlePlaceholder" style="height: 50px;"></div>

<div id="searchResultContainerId">
	<input name="globalSearchText" id="globalSearchText" type="hidden" value="{$SEARCH_STRING}" />
</div>

<div id="indicatorAppend" align="center" style="padding: 8px; display: none;">
	<i class="dataloader" data-loader="circle" id="" style="vertical-align:middle;"></i>
</div>

<a id="btnScrollTop" class="btn btn-info btn-fab" href="#" style="display:none">
	<i class="vteicon nohover">arrow_upward</i>
</a>

<script type="text/javascript">
	var navbarHeight = jQuery('#UnifiedSearchTitle').height();
	jQuery('#UnifiedSearchTitlePlaceholder').height(navbarHeight);
	
	var module_list = JSON.parse('{$MODULES_LIST_JS}'),
		count = module_list.length,
		search_module = '{$SEARCH_MODULE}';
	
	{literal}
	jQuery("#status").show();
	jQuery('#indicatorAppend').show();
	jQuery.each(module_list, function(k,module) {
		jQuery.ajax({
			url: 'index.php?module=Home&action=HomeAjax&file=UnifiedSearchAjax',
			type: 'POST',
			data: {'smodule':module,'display':(module == search_module || search_module == ''),'query_string':'{/literal}{$SEARCH_STRING}{literal}'},
			success: function(data) {
				jQuery('#searchResultContainerId').append(data);
				if (jQuery('#global_search_module').val() != 'All' && jQuery('#global_search_module').val() != module) {
					displayModuleList(jQuery('#global_search_module'));
				}
				if (!--count) {
					jQuery("#status").hide();
					jQuery('#indicatorAppend').hide();
				}
			}
		});
	});
	
	function displayModuleList(selectmodule_obj){
		jQuery("#status").show();
		jQuery('#indicatorAppend').show();
		
		var selectmodule = jQuery(selectmodule_obj).val();
		if (selectmodule == 'All') {
			jQuery('[id^=global_list_]').show();
		} else {
			jQuery('[id^=global_list_]').hide();
			jQuery("#global_list_"+selectmodule).show();
		}
		// hide indicators if choose a module already loaded during page loading
		if (selectmodule == 'All' || jQuery("#global_list_"+selectmodule).length > 0) {
			jQuery("#status").hide();
			jQuery('#indicatorAppend').hide();
		}
	}
	{/literal}
</script>