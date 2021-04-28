{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@140887 *}

{if $LINEAR eq true}
	<div class="dataloader" data-loader="linear" id="{$LIID}" style="{$LIEXTRASTYLE}">
		<div class="wrap go">
			<div class="linearloader bar">
				<div></div>
			</div>
		</div>
	</div>
{else}
	<i class="dataloader" data-loader="circle" id="{$LIID}" style="vertical-align:middle;{$LIEXTRASTYLE}"></i>
{/if}