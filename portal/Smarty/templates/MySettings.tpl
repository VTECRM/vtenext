{*+*************************************************************************************
{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
<!-- Bootstrap Core CSS-->
<link href="css/bootstrap.css" rel="stylesheet">
<link href="css/bootstrap.min.css" rel="stylesheet">
<script src="js/jquery-1.11.0.js"></script>
<script src="js/bootstrap.min.js"></script>

<!-- Material Design -->
<link href="css/material_design/material.min.css" rel="stylesheet">
<link href="css/material_design/roboto.min.css" rel="stylesheet">
<link href="css/material_design/material-fullpalette.min.css" rel="stylesheet">
<link href="css/material_design/ripples.min.css" rel="stylesheet">
<link href="css/material_design/ripples.min.css" rel="stylesheet"> 

{* crmv@171581 - csrf protection *}
<script language="JavaScript" type="text/javascript" src="js/csrf.js"></script>
<script type="text/javascript">
	VTEPortal.CSRF.initialize('__csrf_token', '{$CSRF_TOKEN}');
</script>
{* crmv@171581e *}

<div class="modal-header">
		<h4 class="modal-title" id="mySmallModalLabel">{'LBL_CHANGE_PASSWORD'|getTranslatedString}</h4>
</div>
{if !empty($ERRORMSG)}
	{if $ERRORMSG == 'MSG_PASSWORD_CHANGED'|getTranslatedString}
		<div class="alert alert-success">
		{$ERRORMSG}
		</div>
	{else}
		<div class="alert alert-danger">
		{$ERRORMSG}
		</div>
	{/if}
{/if}

<form style="padding:10px;margin-bottom: 0px"id="myModal" name="savepassword" action="MySettings.php" method="post">
	<input type="hidden" name="fun" value="savepassword">
		<div class="form-group">
			<label>{'LBL_OLD_PASSWORD'|getTranslatedString}</label>
			<input class="form-control" type="password" name="old_password" value="">
		</div>
		<div class="form-group" style="margin-top:5px">
			<label>{'LBL_NEW_PASSWORD'|getTranslatedString}</label>
			<input class="form-control" type="password" name="new_password" value="">
		</div>
		<div class="form-group" style="margin-top:5px">	
			<label>{'LBL_CONFIRM_PASSWORD'|getTranslatedString}</label>
			<input class="form-control" type="password" name="confirm_password" value="">
		</div>
		<center><input class="btn btn-default" style="margin-top:10px" name="savepassword" type="submit" value="{'LBL_SAVE'|getTranslatedString}" onclick="return verify_data(this.form)"></center>
</form>

<script>
	{literal}
		function verify_data(form)
		{
		        oldpw = trim(form.old_password.value);
		        newpw = trim(form.new_password.value);
		        confirmpw = trim(form.confirm_password.value);
		        if(oldpw == '')
		        {
				alert("Enter Old Password");
		                return false;
		        }
		        else if(newpw == '')
		        {
				alert("Enter New Password");
		                return false;
		        }
		        else if(confirmpw == '')
		        {
				alert("Confirm the New Password");
		                return false;
		        }
		        else
		        {
		                return true;
		        }
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
	{/literal}
</script>
</body>
</html>