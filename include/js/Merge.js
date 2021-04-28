/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

//to merge a list of acounts with a template
function massMerge(module)
{
//crmv@massmerge fix
	var idstring = get_real_selected_ids(module);
	if (idstring.substr('0','1')==";")
		idstring = idstring.substr('1');
	if(getObj('mergefile').value == '')
	{
	    alert(alert_arr.SELECT_TEMPLATE_TO_MERGE);
        return false;   
    }		
	if (idstring == "" || idstring == ";" || idstring == 'null')
	{
		alert(alert_arr.SELECT);
		return false;
	}
	document.getElementById('selected_ids').value = idstring;
	document.massdelete.action.value = 'Merge';
	document.getElementById('massdelete').action="index.php?module="+module+"&action=Merge&return_module="+module+"&return_action=index";
//crmv@massmerge fix end
}
function mergeshowhide(argg)
{
    var x=document.getElementById(argg).style
    if (x.display=="none")
    {
        x.display="block"
   
    }
    else 
	{
		x.display="none"
	}
}

function mergehide(argg)
{
    var x=document.getElementById(argg);
	if (x != null) x.style.display = "none";
}

 function moveMe(arg1) {
    var posx = 0;
    var posy = 0;
    var e=document.getElementById(arg1);
   
    if (!e) var e = window.event;
   
    if (e.pageX || e.pageY)
    {
        posx = e.pageX;
        posy = e.pageY;
    }
    else if (e.clientX || e.clientY)
    {
        posx = e.clientX + document.body.scrollLeft;
        posy = e.clientY + document.body.scrollTop;
    }
 }
 
//crmv@69201
function MergeFieldsAjax(){
	
	var params = {
		'module' : gVTModule,
		'action' : gVTModule+'Ajax',
		'file' : 'getMergeFields',
		'forModule' : gVTModule,
		'dataType' : 'html',
		'async' : false,
	};

	jQuery.ajax({
		url: 'index.php?' + jQuery.param(params),
		type: 'POST',
		success: function(data) {
			data = eval(data);
			
			jQuery('#availList').html(data[0]);
			jQuery('#selectedColumns').html(data[1]);			
		},
		error: function(data){
			console.log('Ajax Error');
		}
	});	
	//////////////////////////////////////////////////////
	moveMe('mergeDup');
	mergeshowhide('mergeDup');
	searchhide('searchAcc','advSearch');
}
//crmv@69201e