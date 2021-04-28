{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{include file="HTMLHeader.tpl" head_include="icons,jquery"}
<body class="small">

{* additional style *}
{* TODO: move in the global css *}
<style type="text/css">
{literal}
	a.x {
		color:black;
		text-align:center;
		text-decoration:none;
		padding:5px;
		font-weight:bold;
	}
	
	a.x:hover {
		color:#333333;
		text-decoration:underline;
		font-weight:bold;
	}

	li {
		background:transparent;
		padding:0px;
		margin:0px 0px 0px 0px;
	}

	ul li{
		margin-top:5px;
		margin-left:5px;
	}

	ul {color:black;}	 
{/literal}
</style>

<table border="0" cellpadding="5" cellspacing="0" width="100%">
	<tr height="34">
		<td style="padding:5px" class="level3Bg">
			<b>{$CMOD.LBL_ASSIGN_ROLE}</b>
		</td>
	</tr>
</table>
<table border="0" cellspacing="0" cellpadding="5" width="100%" class="small">
	<tr>
		<td valign="top">
			<div style="height:380px;overflow:auto;">{$ROLETREE}</div>
		</td>
	</tr>
</table>
<script type="text/javascript">
var image_path = "{$IMAGE_PATH}";

{literal}

function showhide(argg,imgId) {
	var harray=argg.split(",");
	var harrlen = harray.length;	

	for(var i=0; i<harrlen; i++) {
		var x=document.getElementById(harray[i]).style;
		if (x.display=="none") {
			x.display="block";
			jQuery('#'+imgId).text('indeterminate_check_box');
		} else {
			x.display="none";
			jQuery('#'+imgId).text('add_box');
		}
	}
}

function loadValue(currObj,roleid) {
	// crmv@21048m
	parent.document.getElementById('role_name').value = convert_lt_gt(document.getElementById(currObj).innerHTML);
	parent.document.getElementById('user_role').value = roleid;
	closePopup();
	//crmv@21048m-e
}

function convert_lt_gt(str) {
	str = str.replace(/(&lt;)/g,'<');
	str = str.replace(/(&gt;)/g,'>');
	str = str.replace(/(&amp;)/g,'&');
	return str;
}		



jQuery(document).ready(function() {
	loadedPopup();
});
{/literal}
</script>
</body>
</html>