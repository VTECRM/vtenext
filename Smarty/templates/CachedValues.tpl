{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@sdk-26228	crmv@sdk-26252	crmv@sdk-26260	crmv@26280	crmv@47905bis *}
{assign var=sdk_js_language value=$SDK->loadJsLanguage()}
{if $sdk_js_language|strpos:'ar alert_arr' eq 1}
	<script type="text/javascript">{$sdk_js_language};</script>
{elseif $sdk_js_language neq ''}
	<script type="text/javascript" src="{$sdk_js_language}"></script>
{/if}
<script type="text/javascript">
var sdk_js_uitypes = new Array();
{assign var=sdk_js_uitypes value=$SDK->getJsUitypes()}
{if $sdk_js_language neq ''}
	sdk_js_uitypes = eval({$sdk_js_uitypes});
{/if}

var sdk_js_presave = new Array();
{assign var=sdk_js_presave value=$SDK->getJSPreSaveLis()}
{if $sdk_js_presave neq ''}
	sdk_js_presave = eval({$sdk_js_presave});
{/if}

var merge_user_fields = new Array();
merge_user_fields = eval({$smarty.session.merge_user_fields});
</script>