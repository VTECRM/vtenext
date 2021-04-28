{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<table width="100%" cellspacing="0" cellpadding="5">
	<tr>
		<td width="15%" class="heading2">{'LBL_IMPORT_STEP_1'|@getTranslatedString:$MODULE}:</td>
		<td class="big">{'LBL_IMPORT_STEP_1_DESCRIPTION'|@getTranslatedString:$MODULE}</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
			<input type="hidden" name="type" value="csv" />
			<input type="hidden" name="is_scheduled" value="1" />
			<input type="file" name="import_file" id="import_file" class="small" size="60" onchange="ImportJs.checkFileType()"/>
			<!-- input type="hidden" name="userfile_hidden" value=""/ -->
		</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td class="small">{'LBL_IMPORT_SUPPORTED_FILE_TYPES'|@getTranslatedString:$MODULE}</td>
	</tr>
</table>