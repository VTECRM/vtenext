{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@98866 *}

{* crmv@103922 *}
<script type="text/javascript" src="{"include/js/dtlviewajax.js"|resourcever}"></script>

<span id="crmspanid" style="display:none;position:absolute;" onmouseover="show('crmspanid');">
   <a class="edit" href="javascript:;">{$APP.LBL_EDIT_BUTTON}</a>
</span>

<input type="hidden" id="hdtxt_IsAdmin" value="{if $IS_ADMIN}1{else}0{/if}"> {* crmv@181170 *}
{* crmv@103922e *}

{if empty($MODE) || $MODE eq 'edit'}
	{include file="modules/Calendar/EditViewBlock.tpl"}
{else}
	{include file="modules/Calendar/DetailViewBlock.tpl"}
{/if}

<script type="text/javascript">
	var cPopTitle1 = "{$SINGLE_MOD|@getTranslatedString:$MODULE}";
	var cPopTitle2 = {$JS_NAME};
</script>