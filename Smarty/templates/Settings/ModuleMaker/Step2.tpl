{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@64542 *}

{* It would be nice to just include the field edit file, but that one is a mess, and the backend reads from the db *}
{* include file="Settings/LayoutBlockEntries.tpl" *}

{* So let's do it in javascript only *}

<div>
	<p>{$MOD.LBL_MMAKER_STEP2_INTRO}</p>
</div>

{* include blocks table *}
<div id="mmaker_div_allblocks">
{include file="Settings/ModuleMaker/Step2Fields.tpl"}
</div>