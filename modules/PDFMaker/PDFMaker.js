/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@158392

window.VTE = window.VTE || {};

VTE.PDFMaker = VTE.PDFMaker || {
	
	ExportTemplates: function() {
		if (typeof (document.massdelete.selected_id) == 'undefined') {
			return false;
		}
		
		x = document.massdelete.selected_id.length;
		idstring = "";

		if (x == undefined) {
			if (document.massdelete.selected_id.checked) {
				idstring = document.massdelete.selected_id.value;

				window.location.href = "index.php?module=PDFMaker&action=PDFMakerAjax&file=ExportPDFTemplate&templates=" + idstring;
				xx = 1;
			} else {
				alert(alert_arr.SELECT_ATLEAST_ONE);
				return false;
			}
		} else {
			xx = 0;
			for (i = 0; i < x; i++) {
				if (document.massdelete.selected_id[i].checked) {
					idstring = document.massdelete.selected_id[i].value + ";" + idstring;
					xx++;
				}
			}
			if (xx != 0) {
				document.massdelete.idlist.value = idstring;
				window.location.href = "index.php?module=PDFMaker&action=PDFMakerAjax&file=ExportPDFTemplate&templates=" + idstring;
			} else {
				alert(alert_arr.SELECT_ATLEAST_ONE);
				return false;
			}
		}
	},

	massDelete: function() {
		if (typeof (document.massdelete.selected_id) == 'undefined') {
			return false;
		}
		
		x = document.massdelete.selected_id.length;
		idstring = "";

		if (x == undefined) {
			if (document.massdelete.selected_id.checked) {
				document.massdelete.idlist.value = document.massdelete.selected_id.value + ';';
				xx = 1;
			} else {
				alert(alert_arr.SELECT_ATLEAST_ONE);
				return false;
			}
		} else {
			xx = 0;
			for (i = 0; i < x; i++) {
				if (document.massdelete.selected_id[i].checked) {
					idstring = document.massdelete.selected_id[i].value + ";" + idstring;
					xx++;
				}
			}
			if (xx != 0) {
				document.massdelete.idlist.value = idstring;
			} else {
				alert(alert_arr.SELECT_ATLEAST_ONE);
				return false;
			}
		}

		vteconfirm(alert_arr.PDFMAKER_DELETE_CONFIRMATION, function(yes) {
			if (yes) {
				document.massdelete.action.value = "DeletePDFTemplate";
				document.massdelete.submit();
			} else {
				return false;
			}
		});
		
		return false;
	}
	
};

//crmv@158392e

var typeofdata = new Array();
typeofdata['V'] = ['e','n','s','ew','c','k'];
typeofdata['N'] = ['e','n','l','g','m','h'];
typeofdata['SUM'] = ['e','n','l','g','m','h'];
typeofdata['AVG'] = ['e','n','l','g','m','h'];
typeofdata['MIN'] = ['e','n','l','g','m','h'];
typeofdata['MAX'] = ['e','n','l','g','m','h'];
typeofdata['COUNT'] = ['e','n','l','g','m','h'];
typeofdata['T'] = ['e','n','l','g','m','h','bw','b','a'];
typeofdata['I'] = ['e','n','l','g','m','h'];
typeofdata['C'] = ['e','n'];
typeofdata['D'] = ['e','n','l','g','m','h','bw','b','a'];
typeofdata['NN'] = ['e','n','l','g','m','h'];
typeofdata['E'] = ['e','n','s','ew','c','k'];

var fLabels = new Array();
fLabels['e'] = alert_arr.EQUALS;
fLabels['n'] = alert_arr.NOT_EQUALS_TO;
fLabels['s'] = alert_arr.STARTS_WITH;
fLabels['ew'] = alert_arr.ENDS_WITH;
fLabels['c'] = alert_arr.CONTAINS;
fLabels['k'] = alert_arr.DOES_NOT_CONTAINS;
fLabels['l'] = alert_arr.LESS_THAN;
fLabels['g'] = alert_arr.GREATER_THAN;
fLabels['m'] = alert_arr.LESS_OR_EQUALS;
fLabels['h'] = alert_arr.GREATER_OR_EQUALS;
fLabels['bw'] = alert_arr.BETWEEN;
fLabels['b'] = alert_arr.BEFORE;
fLabels['a'] = alert_arr.AFTER;
var noneLabel;
var gcurrepfolderid=0;
function trimfValues(value)
{
    var string_array;
    string_array = value.split(":");
    return string_array[4];
}

function updatefOptions(sel, opSelName) {
    var selObj = document.getElementById(opSelName);
    var fieldtype = null ;

    var currOption = selObj.options[selObj.selectedIndex];
    var currField = sel.options[sel.selectedIndex];

    if(currField.value != null && currField.value.length != 0)
    {
	fieldtype = trimfValues(currField.value);
	ops = typeofdata[fieldtype];
	var off = 0;
	if(ops != null)
	{

		var nMaxVal = selObj.length;
		for(nLoop = 0; nLoop < nMaxVal; nLoop++)
		{
			selObj.remove(0);
		}
		selObj.options[0] = new Option ('None', '');
		if (currField.value == '') {
			selObj.options[0].selected = true;
		}
		off = 1;
		for (var i = 0; i < ops.length; i++)
		{
			var label = fLabels[ops[i]];
			if (label == null) continue;
			var option = new Option (fLabels[ops[i]], ops[i]);
			selObj.options[i + off] = option;
			if (currOption != null && currOption.value == option.value)
			{
				option.selected = true;
			}
		}
	}
    }else
    {
	var nMaxVal = selObj.length;
	for(nLoop = 0; nLoop < nMaxVal; nLoop++)
	{
		selObj.remove(0);
	}
	selObj.options[0] = new Option ('None', '');
	if (currField.value == '') {
		selObj.options[0].selected = true;
	}
    }

}

function changeSteps()
{
   actual_step = getObj('step').value * 1;
   next_step = actual_step + 1;

   if (next_step == "2")
   {
       document.getElementById('back_rep').disabled = false;
       changeSecOptions();
   }
   else if (next_step == "5")
   {
       blockname_val = document.getElementById('blockname').value
       
       if (blockname_val == '')
    	 {
    	    alert(alert_arr.BLOCK_NAME_CANNOT_BE_BLANK);
          return false;
       }
		
       document.NewBlock.submit();
   }
   else
   {
       if (next_step == "3")
       { 
          if (selectedColumnsObj.options.length == 0) 
          {
  					alert(alert_arr.COLUMNS_CANNOT_BE_EMPTY);
  					return false;
  				}
           
          createRelatedBlockTable();
       } 
       else if (next_step == "4")
       {
           formSelectColumnString();

           if(!formSelectConditions()) return false;
           
           var date1=getObj("startdate")
        	 var date2=getObj("enddate")

        	 if ((date1.value != '') || (date2.value != ''))
        	 {
          		if(!dateValidate("startdate","Start Date","D")) return false;
          	
          		if(!dateValidate("enddate","End Date","D")) return false;
          
          		if(! dateComparison("startdate",'Start Date',"enddate",'End Date','LE')) return false;
           }
       }
       
       getObj("step"+actual_step+"label").className = 'settingsTabList';
	     getObj("step"+next_step+"label").className = 'settingsTabSelected';
       hide('step'+actual_step);
       show('step'+next_step);
   }   
   
   
   
   getObj('step').value = next_step;
}



function changeStepsback()
{
   actual_step = getObj('step').value * 1;
   last_step = actual_step - 1;

 	 getObj("step"+actual_step+"label").className = 'settingsTabList';
	 getObj("step"+last_step+"label").className = 'settingsTabSelected';
   
   hide('step'+actual_step);
   show('step'+last_step);
   
   if (last_step == 1) document.getElementById('back_rep').disabled = true;
   
   getObj('step').value = last_step;
}


function getCheckedValue(radioObj) {
	if(!radioObj)
		return "";
	var radioLength = radioObj.length;
	if(radioLength == undefined)
		if(radioObj.checked)
			return radioObj.value;
		else
			return "";
	for(var i = 0; i < radioLength; i++) {
		if(radioObj[i].checked) {
			return radioObj[i].value;
		}
	}
	return "";
}      

function setObjects() 
{
	availListObj=getObj("availList")
	selectedColumnsObj=getObj("selectedColumns")

	moveupLinkObj=getObj("moveup_link")
	moveupDisabledObj=getObj("moveup_disabled")
	movedownLinkObj=getObj("movedown_link")
	movedownDisabledObj=getObj("movedown_disabled")
}

function addColumn() 
{
	selectedColumnsObj=getObj("selectedColumns");
    
    for (i=0;i<selectedColumnsObj.length;i++) 
	{
		selectedColumnsObj.options[i].selected=false
	}
	addColumnStep1();
}

function addColumnStep1()
{
	availListObj=getObj("availList");
    selectedColumnsObj=getObj("selectedColumns");
    //the below line is added for report not woking properly in browser IE7 --bharath
	document.getElementById("selectedColumns").style.width="164px";
	
	if (availListObj.options.selectedIndex > -1)
	{
		for (i=0;i<availListObj.length;i++) 
		{
			if (availListObj.options[i].selected==true) 
			{
				var rowFound=false;
				for (j=0;j<selectedColumnsObj.length;j++) 
				{
					if (selectedColumnsObj.options[j].value==availListObj.options[i].value) 
					{
						var rowFound=true;
						var existingObj=selectedColumnsObj.options[j];
						break;
					}
				}

				if (rowFound!=true) 
				{
					var newColObj=document.createElement("OPTION")
					newColObj.value=availListObj.options[i].value
					if (browser_ie) newColObj.innerText=availListObj.options[i].innerText
					else if (browser_nn4 || browser_nn6) newColObj.text=availListObj.options[i].text
					selectedColumnsObj.appendChild(newColObj)
					newColObj.selected=true
				} 
				else 
				{
					existingObj.selected=true
				}
				availListObj.options[i].selected=false
				addColumnStep1();
			}
		}
	}
}	        

function selectedColumnClick(oSel)
{
	var error_msg = '';
	var error_str = false;
	if(oSel.selectedIndex > -1) {
                for(var i = 0; i < oSel.options.length; ++i) {
                        if(oSel.options[i].selected == true && oSel.options[i].disabled == true) {
                                error_msg = error_msg + oSel.options[i].text+',';
				error_str = true;
                                oSel.options[i].selected = false;
                        }
                }
        }
	if(error_str)
	{
		error_msg = error_msg.substr(0,error_msg.length-1);
		alert(alert_arr.NOT_ALLOWED_TO_EDIT_FIELDS+"\n"+error_msg);
		return false;
	}
	else
		return true;
}
function delColumn() 
{
	selectedColumnsObj=getObj("selectedColumns");
	
    if (selectedColumnsObj.options.selectedIndex > -1)
	{
		for (i=0;i < selectedColumnsObj.options.length;i++) 
		{
			if(selectedColumnsObj.options[i].selected == true)
			{
				selectedColumnsObj.remove(i);
				delColumn();
			}
		}
	}
}

function formSelectColumnString()
{
	selectedColumnsObj=getObj("selectedColumns");
    var selectedColStr = "";
	for (i=0;i<selectedColumnsObj.options.length;i++) 
	{
		selectedColStr += selectedColumnsObj.options[i].value + ";";
	}
	document.NewBlock.selectedColumnsString.value = selectedColStr;
}

function moveUp() 
{
	selectedColumnsObj=getObj("selectedColumns");
    var currpos=selectedColumnsObj.options.selectedIndex
	var tempdisabled= false;
	for (i=0;i<selectedColumnsObj.length;i++) 
	{
		if(i != currpos)
			selectedColumnsObj.options[i].selected=false
	}
	if (currpos>0) 
	{
		var prevpos=selectedColumnsObj.options.selectedIndex-1

		if (browser_ie) 
		{
			temp=selectedColumnsObj.options[prevpos].innerText
			tempdisabled = selectedColumnsObj.options[prevpos].disabled;
			selectedColumnsObj.options[prevpos].innerText=selectedColumnsObj.options[currpos].innerText
			selectedColumnsObj.options[prevpos].disabled = false;
			selectedColumnsObj.options[currpos].innerText=temp
			selectedColumnsObj.options[currpos].disabled = tempdisabled;     
		} 
		else if (browser_nn4 || browser_nn6) 
		{
			temp=selectedColumnsObj.options[prevpos].text
			tempdisabled = selectedColumnsObj.options[prevpos].disabled;
			selectedColumnsObj.options[prevpos].text=selectedColumnsObj.options[currpos].text
			selectedColumnsObj.options[prevpos].disabled = false;
			selectedColumnsObj.options[currpos].text=temp
			selectedColumnsObj.options[currpos].disabled = tempdisabled;
		}
		temp=selectedColumnsObj.options[prevpos].value
		selectedColumnsObj.options[prevpos].value=selectedColumnsObj.options[currpos].value
		selectedColumnsObj.options[currpos].value=temp
		selectedColumnsObj.options[prevpos].selected=true
		selectedColumnsObj.options[currpos].selected=false
		}
}

function moveDown() 
{
	selectedColumnsObj=getObj("selectedColumns");
    var currpos=selectedColumnsObj.options.selectedIndex
	var tempdisabled= false;
	for (i=0;i<selectedColumnsObj.length;i++) 
	{
		if(i != currpos)
			selectedColumnsObj.options[i].selected=false
	}
	if (currpos<selectedColumnsObj.options.length-1)	
	{
		var nextpos=selectedColumnsObj.options.selectedIndex+1

		if (browser_ie) 
		{	
			temp=selectedColumnsObj.options[nextpos].innerText
			tempdisabled = selectedColumnsObj.options[nextpos].disabled;
			selectedColumnsObj.options[nextpos].innerText=selectedColumnsObj.options[currpos].innerText
			selectedColumnsObj.options[nextpos].disabled = false;
			selectedColumnsObj.options[nextpos];

			selectedColumnsObj.options[currpos].innerText=temp
			selectedColumnsObj.options[currpos].disabled = tempdisabled;
		}
		else if (browser_nn4 || browser_nn6) 
		{
			temp=selectedColumnsObj.options[nextpos].text
			tempdisabled = selectedColumnsObj.options[nextpos].disabled;
			selectedColumnsObj.options[nextpos].text=selectedColumnsObj.options[currpos].text
			selectedColumnsObj.options[nextpos].disabled = false;
			selectedColumnsObj.options[nextpos];
			selectedColumnsObj.options[currpos].text=temp
			selectedColumnsObj.options[currpos].disabled = tempdisabled;
		}
		temp=selectedColumnsObj.options[nextpos].value
		selectedColumnsObj.options[nextpos].value=selectedColumnsObj.options[currpos].value
		selectedColumnsObj.options[currpos].value=temp

		selectedColumnsObj.options[nextpos].selected=true
		selectedColumnsObj.options[currpos].selected=false
	}
}

function disableMove() 
{
	selectedColumnsObj=getObj("selectedColumns");
    var cnt=0
		for (i=0;i<selectedColumnsObj.options.length;i++) 
		{
			if (selectedColumnsObj.options[i].selected==true)
				cnt++
		}

	if (cnt>1) 
	{
		moveupLinkObj.style.display=movedownLinkObj.style.display="none"
			moveupDisabledObj.style.display=movedownDisabledObj.style.display="block"
	}
	else 
	{
		moveupLinkObj.style.display=movedownLinkObj.style.display="block"
			moveupDisabledObj.style.display=movedownDisabledObj.style.display="none"
	}
}

function standardFilterDisplay()
{
  if(document.NewBlock.stdDateFilterField.options.length <= 0 || (document.NewBlock.stdDateFilterField.selectedIndex > -1 && document.NewBlock.stdDateFilterField.options[document.NewBlock.stdDateFilterField.selectedIndex].value == "Not Accessible")) 
	{
		getObj('stdDateFilter').disabled = true;
		getObj('startdate').disabled = true;
    getObj('enddate').disabled = true;
		getObj('jscal_trigger_date_start').style.visibility="hidden";
		getObj('jscal_trigger_date_end').style.visibility="hidden";
	}
	else
	{
		getObj('stdDateFilter').disabled = false;
		getObj('startdate').disabled = false;
		getObj('enddate').disabled = false;
		getObj('jscal_trigger_date_start').style.visibility="visible";
		getObj('jscal_trigger_date_end').style.visibility="visible";
	}
}

function createRelatedBlockTable()
{
     selectedColumnsObj=getObj("selectedColumns");
     var oEditor = CKEDITOR.instances.relatedblock;     

     table = "<table border='1' cellpadding='3' cellspacing='0' style='border-collapse: collapse;'>";
     //header
     table += "<tr>";
     for (i=0;i < selectedColumnsObj.options.length;i++) 
  	 {
  			table += "<td>";
  			   table +=	selectedColumnsObj.options[i].innerHTML;
  			table += "</td>";
  	 }
     table += "</tr>";
          
     //separator Start
     table += "<tr>";
        table += "<td colspan='50'>#RELBLOCK_START#</td>";
     table += "</tr>";      
          
     table += "<tr>";
     for (i=0;i < selectedColumnsObj.options.length;i++) 
  	 {
		table += "<td>";
		coldata = selectedColumnsObj.options[i].value.split(":");
		if (coldata[3] == 'date_start') coldata[3] = 'Start_Date_and_Time'; // crmv@48520
		table += "$" + coldata[3] + "$";
		table += "</td>";
  	 }
     table += "</tr>";     
          
     //separator End
     table += "<tr>";
        table += "<td colspan='50'>#RELBLOCK_END#</td>";
     table += "</tr>";       
          
     table += "</table>";
                       
     /********************************************************************/                  
        
     /*
     table += "{STARTRELBLOCK}";
     table += "<table border='1'>";     
     
     for (i=0;i < selectedColumnsObj.options.length;i++) 
  	 {
  			table += "<tr>";
            table += "<td>";
      			   table +=	selectedColumnsObj.options[i].innerHTML + ":";
      			table += "</td>";
            
            table += "<td>";
      			   coldata = selectedColumnsObj.options[i].value.split(":");
      			   table += "{$" + coldata[3] + "$}";
      			table += "</td>";
  			table += "</tr>"; 
  	 }
         
     table += "</table><br />"; 
     table += "{ENDRELBLOCK}";     
     */ 
                
     oEditor.setData(table);
}

function formSelectConditions()
{
    var escapedOptions = new Array('account_id','contactid','contact_id','product_id','parent_id','campaignid','potential_id','assigned_user_id1','quote_id','accountname','salesorder_id','vendor_id','time_start','time_end','lastname');
		
		var conditionColumns = vt_getElementsByName('tr', "conditionColumn");
		var criteriaConditions = [];
		for(var i=0;i < conditionColumns.length ; i++) {
			
			var columnRowId = conditionColumns[i].getAttribute("id");
			var columnRowInfo = columnRowId.split("_");
			var columnGroupId = columnRowInfo[1];
			var columnIndex = columnRowInfo[2];
			
			var columnId = "fcol"+columnIndex;
			var columnObject = getObj(columnId);
			var selectedColumn = trim(columnObject.value);
			var selectedColumnIndex = columnObject.selectedIndex;	
			var selectedColumnLabel = columnObject.options[selectedColumnIndex].text;
			
			var comparatorId = "fop"+columnIndex;
			var comparatorObject = getObj(comparatorId);
			var comparatorValue = trim(comparatorObject.value);
			
			var valueId = "fval"+columnIndex;
			var valueObject = getObj(valueId);
			var specifiedValue = trim(valueObject.value);
			
			var extValueId = "fval_ext"+columnIndex;
			var extValueObject = getObj(extValueId);
			if(extValueObject) {
				extendedValue = trim(extValueObject.value);
			}
			
			var glueConditionId = "fcon"+columnIndex;
			var glueConditionObject = getObj(glueConditionId);
			var glueCondition = '';
			if(glueConditionObject) {
				glueCondition = trim(glueConditionObject.value);
			}

			if (!emptyCheck(columnId," Column ","text"))
				return false;
			if (!emptyCheck(comparatorId,selectedColumnLabel+" Option","text"))
				return false;

			var col = selectedColumn.split(":");
			if(escapedOptions.indexOf(col[3]) == -1) {
				if(col[4] == 'T') {   
					var datime = specifiedValue.split(" ");
					if(!re_dateValidate(datime[0],selectedColumnLabel+" (Current User Date Time Format)","OTH"))
						return false
					if(datime.length > 1)	
					if(!re_patternValidate(datime[1],selectedColumnLabel+" (Time)","TIMESECONDS"))
						return false
				}	
				else if(col[4] == 'D')
				{        
					if(!dateValidate(valueId,selectedColumnLabel+" (Current User Date Format)","OTH"))
						return false
					if(extValueObject) {
						if(!dateValidate(extValueId,selectedColumnLabel+" (Current User Date Format)","OTH"))
							return false
					}
				}else if(col[4] == 'I')
				{
					if(!intValidate(valueId,selectedColumnLabel+" (Integer Criteria)"+i))           
						return false
				}else if(col[4] == 'N')
				{  
					if (!numValidate(valueId,selectedColumnLabel+" (Number) ","any",true))
						return false
				}else if(col[4] == 'E')
				{
					if (!patternValidate(valueId,selectedColumnLabel+" (Email Id)","EMAIL"))
						return false
				}
			}
			
			//Added to handle yes or no for checkbox fields in reports advance filters. 
			if(col[4] == "C") {
				if(specifiedValue == "1")
					specifiedValue = getObj(valueId).value = 'yes';
				else if(specifiedValue =="0")
					specifiedValue = getObj(valueId).value = 'no';
			}
			if (extValueObject && extendedValue != null && extendedValue != '') specifiedValue = specifiedValue +','+ extendedValue;
			
			criteriaConditions[columnIndex] = {"groupid":columnGroupId, 
												"columnname":selectedColumn,
												"comparator":comparatorValue,
												"value":specifiedValue,
												"columncondition":glueCondition
											};
		}
		
		jQuery('#advft_criteria').val(JSON.stringify(criteriaConditions)); // crmv@192033
		
		var conditionGroups = vt_getElementsByName('div', "conditionGroup");
		var criteriaGroups = [];
		for(var i=0;i < conditionGroups.length ; i++) {
			var groupTableId = conditionGroups[i].getAttribute("id");
			var groupTableInfo = groupTableId.split("_");
			var groupIndex = groupTableInfo[1];
			
			var groupConditionId = "gpcon"+groupIndex;
			var groupConditionObject = getObj(groupConditionId);
			var groupCondition = '';
			if(groupConditionObject) {
				groupCondition = trim(groupConditionObject.value);
			}
			criteriaGroups[groupIndex] = {"groupcondition":groupCondition};
			
		}
		jQuery('#advft_criteria_groups').val(JSON.stringify(criteriaGroups)); // crmv@192033
		
		return true;
}

/**
 * IE has a bug where document.getElementsByName doesnt include result of dynamically created 
 * elements
 */
function vt_getElementsByName(tagName, elementName) {
	var inputs = document.getElementsByTagName( tagName );
	var selectedElements = [];
	for(var i=0;i<inputs.length;i++){
	  if(inputs.item(i).getAttribute( 'name' ) == elementName ){
		selectedElements.push( inputs.item(i) );
	  }
	}
	return selectedElements;
}

function changeEditSteps()
{
   actual_step = getObj('step').value * 1;
   next_step = actual_step + 1;


   if (next_step == "3")
   {
       blockname_val = document.getElementById('blockname').value;
       
       if (blockname_val == '')
    	 {
    	    alert(alert_arr.BLOCK_NAME_CANNOT_BE_BLANK);
          return false;
       }
		
       document.NewBlock.submit();
   }
   else
   {
       if (next_step == "2")
       {
           document.getElementById('back_rep').disabled = false;
           
           if(!formSelectConditions()) return false;
           
           var date1=getObj("startdate")
        	 var date2=getObj("enddate")

        	 if ((date1.value != '') || (date2.value != ''))
        	 {
          		if(!dateValidate("startdate","Start Date","D")) return false;
          	
          		if(!dateValidate("enddate","End Date","D")) return false;
          
          		if(! dateComparison("startdate",'Start Date',"enddate",'End Date','LE')) return false;
           }
       }
       
       getObj("step"+actual_step+"label").className = 'settingsTabList';
	     getObj("step"+next_step+"label").className = 'settingsTabSelected';
       hide('step'+actual_step);
       show('step'+next_step);
   }

   getObj('step').value = next_step;
}