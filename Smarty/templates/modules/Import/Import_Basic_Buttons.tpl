{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

<button type="submit" name="next" class="crmbutton edit" onclick="return ImportJs.uploadAndParse();">
	{'LBL_NEXT_BUTTON_LABEL'|@getTranslatedString:$MODULE}
</button>

<button type="button" name="cancel" class="crmbutton cancel" onclick="location.href='index.php?module={$FOR_MODULE}&action=index'">
	{'LBL_CANCEL_BUTTON_LABEL'|@getTranslatedString:$MODULE}
</button>