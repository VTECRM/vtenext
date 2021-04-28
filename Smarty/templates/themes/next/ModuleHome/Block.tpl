{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}

<div id="modblock_{$BLOCK.blockid}" class="modblock">
	<table width="100%" cellpadding="0" cellspacing="0" border="0" class="stuffHeader">
		
		<tr id="headerrow_{$BLOCK.blockid}" style="height:50px">
		
			<td align="left" class="homePageMatrixHdr stuffTitle headerrow" width="70%">
				<span>{$BLOCK.title}</span>
			</td>
			
			<td align="right" class="homePageMatrixHdr" width="30%" nowrap>
			
				<div class="dropdown">
					<span class="vcenter" id="refresh_{$BLOCK.blockid}">&nbsp;&nbsp;</span>
					
					<i id="toggle_{$BLOCK.blockid}" class="vteicon valign-middle md-link dropdown-toggle" data-toggle="dropdown">more_vert</i>
					
					<ul class="dropdown-menu dropdown-menu-right dropdown-autoclose">
						{* refresh button *}
						<li>
							<a href="javascript:void(0);" onclick="ModuleHome.loadBlock('{$MODHOMEID}', '{$BLOCK.blockid}');">
								<i class="vteicon valign-middle">refresh</i> {'Refresh'|@getTranslatedString}
							</a>
						</li>
						
						{* remove button *}
						<li>
							<a href="javascript:void(0);" onclick="ModuleHome.removeBlock('{$MODHOMEID}', '{$BLOCK.blockid}')">
								<i class="vteicon valign-middle">clear</i> {'LBL_CLOSE'|@getTranslatedString}</div>
							</a>
						</li>
					</ul>
				</div>

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