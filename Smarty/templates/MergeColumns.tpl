{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<form enctype="multipart/form-data" name="mergeDuplicates" method="post" action="index.php?module={$MODULE}&action=FindDuplicateRecords" onsubmit="VteJS_DialogBox.block();">
	<input type="hidden" name="module" value="{$MODULE}">
	<input type="hidden" name="parenttab" value="{$CATEGORY}">
	<input type="hidden" name="action" value="FindDuplicateRecords">
	<input type="hidden" name="selectedColumnsString">
	<input type="hidden" name="save_mapping_flag" value="false">
	
	<table class="searchUIBasic small" border="0" cellpadding="5" cellspacing="0" width="100%" height="10" align="center">
		<tr class="lvtCol" style="Font-Weight: normal">
			<td colspan="3">
				<span class="moduleName">{$APP.LBL_SELECT_MERGECRITERIA_HEADER}</span><br>
				<span>{$APP.LBL_SELECT_MERGECRITERIA_TEXT}</span>
			</td>
	   </tr>
	   <tr><td colspan="3"></td></tr>
		<tr>
			<td><b>{$APP.LBL_AVAILABLE_FIELDS}</b></td>
			<td></td>
			<td><b>{$APP.LBL_SELECTED_FIELDS}</b></td>
		</tr>
		<tr>
			<td width=47%>
				<select id="availList" multiple size="10" name="availList" class="detailedViewTextBox">
					{$AVALABLE_FIELDS}
				</select>
			</td>
			<td width="6%">
				<div align="center">
					<button type="button" name="Button" onclick="addColumn()" class="crmbutton edit">&nbsp;&rsaquo;&rsaquo;&nbsp;</button><br /><br />
					<button type="button" name="Button1" onclick="delColumn()" class="crmbutton edit">&nbsp;&lsaquo;&lsaquo;&nbsp;</button><br /><br />
				</div>
			</td>
			<td width="47%">
				<select id="selectedColumns" size="10" name="selectedColumns" multiple class="detailedViewTextBox">
					{$FIELDS_TO_MERGE}
				</select>
			</td>
		</tr>
		{*//crmv@36508*}
		<tr class="lvtCol">
			<td colspan="2">
				<span>{$APP.LBL_SELECT_MERGECRITERIA_EMPTY}</span>
				<input type="hidden" name="empty_flag" id="empty_flag" value="1">
				{literal}
				<input type="checkbox" name="empty_flag_check" id="empty_flag_check" checked="checked" onclick="if (this.checked){getObj('empty_flag').value = 1}else{getObj('empty_flag').value = 0}">
				{/literal}
			</td>
	   </tr>
	   {*//crmv@36508 e*}			
		<tr>
			<td colspan="3" align="center">
				<button type="submit" name="save" class="crmbutton small edit" onclick="return SaveMergeFields();">{$APP.SAVE_MERGE_FIELDS}</button>
				<button type="submit" name="save&merge" class="crmbutton small edit" onclick="return formSelectColumnString()">{$APP.LBL_SAVE_MERGE_BUTTON_TITLE}</button>
				<button type="button" name="cancel" class="crmbutton small cancel" onclick="mergeshowhide('mergeDup');">{$APP.LBL_CANCEL_BUTTON_LABEL}</button>
			</td>
		</tr>
	</table>
</form>

<script type="text/javascript">    
        var moveupLinkObj,moveupDisabledObj,movedownLinkObj,movedownDisabledObj;
        function setObjects() 
        {ldelim}
            availListObj=getObj("availList")
            selectedColumnsObj=getObj("selectedColumns")

        {rdelim}

        function addColumn() 
        {ldelim}
        setObjects();
            for (i=0;i<selectedColumnsObj.length;i++) 
            {ldelim}
                selectedColumnsObj.options[i].selected=false
            {rdelim}

            for (i=0;i<availListObj.length;i++) 
            {ldelim}
                if (availListObj.options[i].selected==true) 
                {ldelim}            	
                	var rowFound=false;
                	var existingObj=null;
                    for (j=0;j<selectedColumnsObj.length;j++) 
                    {ldelim}
                        if (selectedColumnsObj.options[j].value==availListObj.options[i].value) 
                        {ldelim}
                            rowFound=true
                            existingObj=selectedColumnsObj.options[j]
                            break
                        {rdelim}
                    {rdelim}

                    if (rowFound!=true) 
                    {ldelim}
                        var newColObj=document.createElement("OPTION")
                        newColObj.value=availListObj.options[i].value
                        if (browser_ie) newColObj.innerText=availListObj.options[i].innerText
                        else if (browser_nn4 || browser_nn6) newColObj.text=availListObj.options[i].text
                        selectedColumnsObj.appendChild(newColObj)
                        availListObj.options[i].selected=false
                        newColObj.selected=true
                        rowFound=false
                    {rdelim} 
                    else 
                    {ldelim}
                        if(existingObj != null) existingObj.selected=true
                    {rdelim}
                {rdelim}
            {rdelim}
        {rdelim}

        function delColumn() 
        {ldelim}
        setObjects();
            for (i=selectedColumnsObj.options.length;i>0;i--) 
            {ldelim}
                if (selectedColumnsObj.options.selectedIndex>=0)
                selectedColumnsObj.remove(selectedColumnsObj.options.selectedIndex)
            {rdelim}
        {rdelim}
        
        function formSelectColumnString()
        {ldelim}
            var selectedColStr = "";
            setObjects();
            for (i=0;i<selectedColumnsObj.options.length;i++) 
            {ldelim}
                selectedColStr += selectedColumnsObj.options[i].value + ",";
            {rdelim}
            if (selectedColStr == "")
            {ldelim}
            	alert('{$APP.LBL_MERGE_SHOULDHAVE_INFO}');
            	return false;
            {rdelim}
            document.mergeDuplicates.selectedColumnsString.value = selectedColStr;
            return;
        {rdelim}
		
        function SaveMergeFields()
        {ldelim}
            var selectedColStr = "";
            setObjects();
            for (i=0;i<selectedColumnsObj.options.length;i++) 
            {ldelim}
                selectedColStr += selectedColumnsObj.options[i].value + ",";
            {rdelim}
            document.mergeDuplicates.selectedColumnsString.value = selectedColStr;
            document.mergeDuplicates.save_mapping_flag.value = 'true';
            return;
        {rdelim}
	setObjects();		
</script>