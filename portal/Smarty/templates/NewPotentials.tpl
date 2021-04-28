{*+*************************************************************************************
{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

<div class="row">
	<div class="col-lg-12">
		<h1 class="page-header">{'NEW_POTENTIALS'|getTranslatedString}</h1>
	</div>
</div>
<form name="Save" method="post" action="index.php" class="NewTicket" enctype="multipart/form-data">
	<input type="hidden" name="module" value="Potentials">
	<input type="hidden" name="action" value="index">
	<input type="hidden" name="fun" value="SavePotentials">
	<input type="hidden" name="projectid" value="{$PROJECTID}" />

	<!--crmv@57342-->
	<input type="hidden" name="customerfile_hidden"/>
	<!--crmv@57342e-->
	<div class="row">
		<div class="form-group">
			<label>
				<h4>{'potentialname'|getTranslatedString}</h4>
			</label>
		</div>
		<input type="text" name="potentialname" id="disabledInput" value="{$NAME_POTENTIALS}" disabled style="min-width:500px">
	<!--	<div class="form-group">
			<label>
				<h4>Stadio di vendita{*'TICKET_TITLE'|getTranslatedString*}</h4>
			</label>
		</div>
		<input type="text" name="sales_stage"> -->
<!-- crmv@57342	
	<div class="row">
		<div class="form-group" style="margin-top:10px">
			<label>
				<h4>{'LBL_TICKET_PRIORITY'|getTranslatedString}</h4>
			</label>
		</div>
		<select name="priority">
			{foreach from=$PRIORITY item=PRIO}
				<option value="{$PRIO}">{$PRIO}</option>
			{/foreach}
		</select>
	</div>
	<div class="row">
		<div class="form-group" style="margin-top:10px">
			<label>
				<h4>{'LBL_TICKET_SEVERITY'|getTranslatedString}</h4>
			</label>
		</div>
		<select name="severity">
			{foreach from=$SEVERITY item=SER}
				<option value="{$SER}">{$SER}</option>
			{/foreach}
		</select>
	</div>
	<div class="row">
		<div class="form-group" style="margin-top:10px">
			<label>
				<h4>{'LBL_TICKET_CATEGORY'|getTranslatedString}</h4>
			</label>
		</div>		
		<select name="category">
			{foreach from=$CATEGORY item=CAT}
				<option value="{$CAT}">{$CAT}</option>
			{/foreach}
		</select>
	</div>
 crmv@57342e -->
		<div class="form-group" style="margin-top:10px;">
			<label>
				<h4>{'LBL_DESCRIPTION'|getTranslatedString}</h4>
			</label>
		</div>
		<textarea name="description" class="form-control" rows="12" style="margin-bottom: 10px;"></textarea>

		<!--crmv@57342-->
		<div class="form-group" style="margin-top:10px;">
			<label>
				<h4>{'LBL_UPLOAD_PICTURE'|getTranslatedString}</h4>
			</label>
		</div>
		<input type="file" class="detailedViewTextBox" name="customerfile" onchange="validateFilename(this);" />
		<!--crmv@57342e-->
	</div>
	
	<div class="row" style="margin-top: 10px">
		<button class="btn btn-success" accesskey="S" name="button" value="{'LBL_SEND'|getTranslatedString}" type="submit" onclick="return formvalidate(this.form)">{'LBL_SEND_REQUEST'|getTranslatedString}</button>
		<button class="btn btn-danger" accesskey="X" name="button" value="{'LBL_CANCEL'|getTranslatedString}" type="button" onclick="window.history.back()">{'LBL_CANCEL'|getTranslatedString}</button>
	</div>
</form>

<script>
{literal}
function formvalidate(form)
{
	if(trim(form.title.value) == '')
	{
		alert("Ticket Title is empty");
		return false;
	}
	return true;
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