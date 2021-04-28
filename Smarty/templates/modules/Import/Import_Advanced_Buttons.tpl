{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<button type="submit" name="import" class="crmbutton edit" onclick="return ImportJs.sanitizeAndSubmit();">
	{'LBL_IMPORT_BUTTON_LABEL'|@getTranslatedString:$MODULE}
</button>

<button type="button" name="cancel" class="crmbutton cancel" onclick="window.history.back()">
	{'LBL_CANCEL_BUTTON_LABEL'|@getTranslatedString:$MODULE}
</button>