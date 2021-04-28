/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

window.VTEPortal = window.VTEPortal || {}; // crmv@173153

function fnLoadValues(obj){
	window.location.href = "index.php?module="+obj+"&action=index";
}

function fnDown(obj){
	var tagName = document.getElementById(obj);
	if(tagName.style.display == 'block')
		tagName.style.display = 'none';
	else
		tagName.style.display = 'block';
}

function fnShowDiv(obj){
	var tagName = document.getElementById(obj);
		tagName.style.visibility = 'visible';
}


function fnHideDiv(obj){
	var tagName = document.getElementById(obj);
		tagName.style.visibility = 'hidden';
}

function findPosX(obj) {
	var curleft = 0;
	if (document.getElementById || document.all) {
		while (obj.offsetParent) {
			curleft += obj.offsetLeft
			obj = obj.offsetParent;
		}
	} 
	else if (document.layers) {curleft += obj.x;}
	return curleft;
}

function findPosY(obj) {
	var curtop = 0;
	if (document.getElementById || document.all) {
		while (obj.offsetParent) {
			curtop += obj.offsetTop
			obj = obj.offsetParent;
		}
	}
	else if (document.layers) {curtop += obj.y;}
	return curtop;
}

function fnShow(obj){
	var tagName = document.getElementById('faqDetail');
	var leftSide = findPosX(obj);
	var topSide = findPosY(obj);
		topSide = topSide - 90;
		leftSide = leftSide - 200; 
		tagName.style.top = topSide + 'px';
		tagName.style.left = leftSide + 'px';
		tagName.style.visibility = 'visible';
}

function trim(s) 
{
	while (s.substring(0,1) == " ")
	{
		s = s.substring(1, s.length);
	}
	while (s.substring(s.length-1, s.length) == ' ')
	{
		s = s.substring(0,s.length-1);
	}

	return s;
}

function getList(obj, module)
{
	var list_type = document.getElementById('show_combo').value; // crmv@173271
	
	var onlymine = true;
	if (list_type == 'all') {
	    onlymine = false;
	}
	
	var appendurl = '';
	var showstatus_ele = document.getElementById('status_combo'); // crmv@173271
	if (showstatus_ele != null) {
		var showstatus = showstatus_ele.value;
		appendurl = '&showstatus='+showstatus;
	}
	
	window.location.href = "index.php?module="+module+"&action=index&onlymine="+onlymine+appendurl;
}


function updateCount(fileid){
	
var xmlHttp;
try{
// Firefox, Opera 8.0+, Safari
  xmlHttp=new XMLHttpRequest();
	}catch (e){
  // Internet Explorer
  		try{
    		xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
    	}catch (e){
    		try{
      			xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
   			}catch (e){
      			alert("Your browser does not support AJAX!");
      			return false;
      		}
    	}
  	}
 xmlHttp.open("POST","index.php?action=updateCount&module=Documents&file_id="+fileid,true);
 xmlHttp.send(null);
}

//crmv@80441
function getFile(url) {
	  if (window.XMLHttpRequest) {
	    AJAX=new XMLHttpRequest();
	  } else {
	    AJAX=new ActiveXObject("Microsoft.XMLHTTP");
	  }
	  if (AJAX) {
	     AJAX.open("GET", url, false);
	     AJAX.send(null);
	     return AJAX.responseText;
	  } else {
	     return false;
	  }
}