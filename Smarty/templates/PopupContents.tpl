{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<!-- BEGIN: main -->

<form name="selectall" method="POST">
	<input name='search_url' id="search_url" type='hidden' value='{$SEARCH_URL}'>
	<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
	<table width="100%" border="0" cellspacing="0" cellpadding="0">
		<tr id="selectallTr"> {* crmv@21048m *}
			{assign var=colspan value=3}
			{if $SELECT eq 'enable'}
				{if $SELECT eq 'enable' && ($POPUPTYPE neq 'inventory_prod' && $POPUPTYPE neq 'inventory_prod_po' && $POPUPTYPE neq 'inventory_service')}
					<td style="padding-left:10px;" align="left"><input class="crmbutton save" type="button" value="{$APP.LBL_SELECT_BUTTON_LABEL} {$MODULE|@getTranslatedString:$MODULE}" onclick="if(SelectAll('{$MODULE}','{$RETURN_MODULE}')) closePopup();"/></td>{* crmv@21048m *}
				{elseif $SELECT eq 'enable' && ($POPUPTYPE eq 'inventory_prod' || $POPUPTYPE eq 'inventory_prod_po')}
					{if $RECORD_ID}
						{assign var=colspan value=4}
						<td style="padding-left:10px;" align="left" width=10%><input class="crmbutton save" type="button" value="{$APP.LBL_BACK}" onclick="window.history.back();"/></td>
					{/if}
					<td style="padding-left:10px;" align="left"><input class="crmbutton save" type="button" value="{$APP.LBL_SELECT_BUTTON_LABEL} {$MODULE|@getTranslatedString:$MODULE}" onclick="if(InventorySelectAll('{$RETURN_MODULE}',image_pth))closePopup();"/></td>{* crmv@21048m *}
				{elseif $SELECT eq 'enable' && $POPUPTYPE eq 'inventory_service'}
					<td style="padding-left:10px;" align="left"><input class="crmbutton save" type="button" value="{$APP.LBL_SELECT_BUTTON_LABEL} {$MODULE|@getTranslatedString:$MODULE}" onclick="if(InventorySelectAllServices('{$RETURN_MODULE}',image_pth))closePopup();"/></td>{* crmv@21048m *}
				{else}		
					<!-- <td>&nbsp;</td> --> <!-- crmv@98866 -->
				{/if}
			{else}
				<!-- <td>&nbsp;</td> --> <!-- crmv@98866 -->
			{/if}
			<td id="rec_string" style="padding-left:10px;" align="left">{$RECORD_COUNTS}</td>
			{* crmv@98866 *}
			<td id="filters" style="padding-right:10px;" align="right">
				<table border=0 cellspacing=0 cellpadding=0>
					<tr>
						<td style="padding-right:5px">
							{if $MODULE neq 'Calendar'}                      
								{$APP.LBL_HOME_COUNT}:&nbsp;
							{/if}                    
						</td>
						<td>
							{if $MODULE neq 'Calendar'}
								<div class="dvtCellInfo">
									<select name="counts" id="counts" class="detailedViewTextBox" onchange="VTE.ListViewCounts.onShowMoreEntries_popup(this,'{$MODULE}')">
										{$CUSTOMCOUNTS_OPTION}
									</select>
								</div>
							{/if}
						</td>	
						<td>{$APP.LBL_VIEW}</td>
						<td style="padding-left:5px;padding-right:5px">
							<div class="dvtCellInfo">
								<select name="viewname" id="viewname" class="detailedViewTextBox" onchange="showDefaultCustomView_popup(this,'{$MODULE}','{$CATEGORY}')">
									{$CUSTOMVIEW_OPTION}
								</select>
							</div>
						</td>
					</tr>
				</table> 
			</td>
			{* crmv@98866 end *}
		</tr>	
		<tr>
			<td colspan="{$colspan}">
		     	<input name="module" type="hidden" value="{$RETURN_MODULE}">
				<input name="action" type="hidden" value="{$RETURN_ACTION}">
				<input name="pmodule" type="hidden" value="{$MODULE}">
				<input type="hidden" name="curr_row" value="{$CURR_ROW}">	
				<input name="entityid" type="hidden" value="">
				<input name="popuptype" id="popup_type" type="hidden" value="{$POPUPTYPE}">
				<!-- //crmv@9183  -->     
				<input name="selected_ids" type="hidden" id="selected_ids" value="{$SELECTED_IDS}">
				<input name="all_ids" type="hidden" id="all_ids" value="{$ALL_IDS}">
				<!-- //crmv@9183 e -->  
				<input name="idlist" type="hidden" value="">
				
				<div id="list" style="overflow:auto;height:348px;">
					<table class="vtetable">
						<thead>
							<tr>
								{if $SELECT eq 'enable'}
						            <th><input type="checkbox" id="selectall" name="selectall" onClick="select_all_page(this.checked,this.form);"></th>
								{/if}
								{foreach item=header from=$LISTHEADER}
									<th>{$header}</th>
								{/foreach}
								{if $SELECT eq 'enable' && ($POPUPTYPE eq 'inventory_prod' || $POPUPTYPE eq 'inventory_prod_po')}
									{if !$RECORD_ID}
										<th>{$APP.LBL_ACTION}</th>
									{/if}
								{/if}
							</tr>
						</thead>
						<tbody>
							{foreach key=entity_id item=entity from=$LISTENTITY}
								{assign var=color value=$entity.clv_color}
								{assign var=foreground value=$entity.clv_foreground}
								{assign var=cell_class value="listview-cell"}
								
								{if !empty($foreground)}
									{assign var=cell_class value=$cell_class|cat:" color-`$foreground`"}
								{/if}
						
								<tr>
									{if $SELECT eq 'enable'}
										<td width="2%"><input type="checkbox" name="selected_id" id="{$entity_id}" value="{$entity_id}" onClick="update_selected_ids(this.checked,'{$entity_id}',this.form);" {if $SELECTED_IDS neq "" && in_array($entity_id,$SELECTED_IDS_ARRAY)} checked {/if} ></td>
									{/if}
									{foreach key=colname item=data from=$entity}
										{if ($colname neq 'clv_color' and $colname neq 'clv_foreground') or $colname eq '0'}
											<td bgcolor="{$color}" class="{$cell_class}">{$data}</td>
										{/if}		
 									{/foreach}
								</tr>
								{* crmv@98866 *}
								{foreachelse}
									<tr>
										<td colspan="{$HEADERCOUNT}" style="padding:1px">
											<div style="width: 100%;position: relative;padding:20px;">	<!-- crmv@18170 -->
												<table border="0" cellpadding="5" cellspacing="0" width="98%" class="table-fixed">
													<tr>
														<td rowspan="2" align="right"><i class="vteicon" style="font-size:40px">error_outline</i><!-- <img src="{'empty.jpg'|resourcever}"> --></td>
														{if $recid_var_value neq '' && $mod_var_value neq '' && $RECORD_COUNTS eq 0 }
															<script>redirectWhenNoRelatedRecordsFound();</script>
															<td align="left" nowrap="nowrap"><span class="genHeaderSmall">{$APP.LBL_NO_M} {$APP.LBL_RECORDS} {$APP.RELATED} !</span></td>
														{else}
															<td align="left" nowrap="nowrap"><span class="genHeaderSmall">{$APP.LBL_NO_M} {$APP.LBL_RECORDS} {$APP.LBL_FOUND} !</span></td>
														{/if}
													</tr>
												</table>
											</div>
										</td>
									</tr>
								{/foreach}
								{* crmv@98866 end *}
							</tbody>
						</table>
					</div>
				</td>
			</tr>
		</table>
		<table width="100%" align="center" class="reportCreateBottom">
			<tr>
				<td id="nav_buttons" align="center" style="width:100%;">{$NAVIGATION}</td>
			</tr>
		</table>
</form>

<script>

{* crmv@21048m *}
{literal}
	function setListHeight() {
		var minus;
		if (jQuery && jQuery.browser && jQuery.browser.msie) { // crmv@98866
			minus = 10;
		}
		else {
			minus = 45;
		}
		var heightRet = jQuery("#ListViewContents").outerHeight() - minus - jQuery('#selectallTr').outerHeight() - jQuery('.reportCreateBottom').outerHeight();// crmv@20172
		jQuery("#list").height(heightRet);
	}
	
	//crmv@112052
	jQuery(document).ready(function() {
		setTimeout(function() {
			jQuery("#ListViewContents").height(jQuery(window).height() - jQuery('#searchTable').outerHeight() - jQuery('#create').outerHeight() - jQuery('#moduleTable').outerHeight());// crmv@20172 
			setListHeight();
			loadedPopup();
		}, 100);
	});
	// crmv@112052e
{/literal}
{* crmv@21048m e *}

  
  function unselectAllIds()
  {ldelim}
     var button_top = document.getElementById("select_all_button_top");

     button_top.value = "{$APP.LBL_SELECT_ALL_IDS}";
  {rdelim}


  function selectAllIds()
  {ldelim}
     var button_top = document.getElementById("select_all_button_top");
     var choose_id = document.getElementById("select_ids");

     if (button_top.value == "{$APP.LBL_SELECT_ALL_IDS}")
     {ldelim}

        button_top.value = "{$APP.LBL_UNSELECT_ALL_IDS}";
        choose_id.value = document.getElementById("all_ids").value
        //crmv@7216
  		document.getElementById("selected_ids").value=choose_id.value;
  	  //crmv@7216e
        document.getElementById("selectall").checked=true;

    	if (isdefined("selected_id")){ldelim}
  	      if (typeof(getObj("selected_id").length)=="undefined")
  	      {ldelim}
  	             getObj("selected_id").checked=true;
  	          {rdelim} else {ldelim}
  	         for (var i=0;i<getObj("selected_id").length;i++){ldelim}
  	                    getObj("selected_id")[i].checked=true;
  	         {rdelim}           
  	      {rdelim}
    	{rdelim}	

     {rdelim} else {ldelim}
        button_top.value = "{$APP.LBL_SELECT_ALL_IDS}";
        choose_id.value = "";
        //crmv@7216
  		document.getElementById("selected_ids").value="";
  		//crmv@7216e
        document.getElementById("selectall").checked=false;

        if (typeof(getObj("selected_id").length)=="undefined")
        {ldelim}
           getObj("selected_id").checked=false;
            {rdelim} else {ldelim}
           for (var i=0;i<getObj("selected_id").length;i++){ldelim}
                      getObj("selected_id")[i].checked=false;
           {rdelim}           
              {rdelim}
     {rdelim}
  {rdelim}

  
//crmv navigation values ajax loaded
update_navigation_values(window.location.href);

{* crmv@107661 - update field list *}
{if $smarty.request.ajax == "true" && $smarty.request.changecustomview == "true"}
{assign var=selectcont value=""}
{foreach key=fieldval item=fieldlabel from=$SEARCHLISTHEADER}
	{assign var=selectcont value=$selectcont|cat:"<option value='`$fieldval`'>`$fieldlabel`</option>"}
{/foreach}
jQuery('select[name=search_field]').html('{$selectcont|replace:"'":"\'"}');
{/if}
{* crmv@107661 *}

</script>