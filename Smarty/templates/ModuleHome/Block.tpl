{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

<div id="modblock_{$BLOCK.blockid}" class="modblock">
	<table width="100%" cellpadding="0" cellspacing="0" class="small" style="padding:0px" border="0">
		<tr id="headerrow_{$BLOCK.blockid}" class="dvInnerHeader headerrow">
			<td align="left" class="homePageMatrixHdr" style="height:30px;" nowrap><b>&nbsp;{$BLOCK.title}</b></td>
			<td align="right" class="homePageMatrixHdr" style="height:30px;" width="40">
				<span id="refresh_{$BLOCK.blockid}" style="position:relative;">&nbsp;&nbsp;</span>
			</td>
			<td align="right" class="homePageMatrixHdr" style="height:30px;" width="80" nowrap>

	{* refresh button *}
	<a style='cursor:pointer;' onclick="ModuleHome.loadBlock('{$MODHOMEID}', '{$BLOCK.blockid}');">
		<i class="vteicon" title="{'Refresh'|@getTranslatedString}">refresh</i>
	</a>
	
	{* remove button *}
	<a style='cursor:pointer;' onclick="ModuleHome.removeBlock('{$MODHOMEID}', '{$BLOCK.blockid}')">
		<i class="vteicon" title="{'LBL_CLOSE'|@getTranslatedString}">clear</i>
	</a>

			</td>
		</tr>
	</table>

	<div class="MatrixBorder">
		<div id="maincont_row_{$BLOCK.blockid}" class="show_tab">
			<div id="blockcont_{$BLOCK.blockid}" class="block-content">
			</div>
		</div>

		{if $BLOCK.type eq "QuickFilter"}
		<table width="100%" cellpadding="0" cellspacing="5" class="scrollLink">
		<tr>
			<td align="right">
				<a href="index.php?module={$MODULE}&amp;action=ListView&amp;viewname={$BLOCK.config.cvid}" target="_blank">{$MOD.LBL_MORE}</a>
			</td>
		</tr>
		</table>
		{/if}
	</div>
</div>

<script language="javascript" type="text/javascript">
	// position the block properly
	ModuleHome.positionBlock('{$BLOCK.blockid}', '{$BLOCK.type}', '{$BLOCK.size}');
</script>