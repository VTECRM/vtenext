{*+*************************************************************************************
{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

{* crmv@81291 *} {* crmv@173153 *}

<div id="tabSrch" style="display:none;">
	<form action="index.php" method="post" name="search">
		<input type="hidden" name="module">
		<input type="hidden" name="action">
		<input type="hidden" name="fun">
		
		<div class="row">
			<div class="col-md-12" align="right"><a href="javascript:fnDown('tabSrch');" class="hdr">{'LBL_CLOSE'|getTranslatedString}</a></div>
		</div>
		
		<div class="row">
			<div class="form-group col-sm-6">
				<label for="search_ticketid">{'TICKETID'|getTranslatedString}</label>
				<input id="search_ticketid" name="search_ticketid" type="text" class="form-control" value="">
			</div>
			
			<div class="form-group col-sm-6">
				<label for="search_title">{'TICKET_TITLE'|getTranslatedString}</label>
				<input id="search_title" name="search_title" type="text" class="form-control" value="">
			</div>
		</div>

		<div class="row">
			<div class="form-group col-sm-6">
				<label>{'TICKET_STATUS'|getTranslatedString}</label>
				{$SEARCH_TICKETSTATUS}
			</div>
			
			<div class="form-group col-sm-6">
				<label>{'TICKET_PRIORITY'|getTranslatedString}</label>
				{$SEARCH_TICKETPRIORITY}
			</div>
		</div>
		
		<div class="row">
			<div class="form-group col-sm-6">
				<label>{'TICKET_CATEGORY'|getTranslatedString}</label>
				{$SEARCH_TICKETCATEGORY}
			</div>
			
			<div class="form-group col-sm-6">
				<label>{'TICKET_MATCH'|getTranslatedString}</label>
				<select name="search_match" class="form-control">
					<option value="all">{'LBL_ALL'|getTranslatedString}</option>
					<option value="any">{'LBL_ANY'|getTranslatedString}</option>
				</select>
			</div>
		</div>

		<div class="row">
			<div align="center" class="col-md-12">
				<button name="Search" type="submit" class="btn btn-info" onclick="fnDown('tabSrch');this.form.module.value='HelpDesk';this.form.action.value='index';this.form.fun.value='search'">
					<i class="material-icons">search</i>
					{'LBL_SEARCH'|getTranslatedString}
				</button>
			</div>
		</div>
	</form>
</div>