{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
<TABLE border=0 cellspacing=0 cellpadding=0 width=100% class=small>
<tr><td style="height:2px"></td></tr>
<tr>
	<td style="padding-left:10px;padding-right:50px" class="moduleName" nowrap>{$APP.$CATEGORY} > <a class="hdrLink" href="index.php?action=ListPDFTemplates&module=PDFMaker&parenttab={$CATEGORY}">{$MOD.LBL_TEMPLATE_GENERATOR}</a></td>

	<td width=100% nowrap>
	
		<table border="0" cellspacing="0" cellpadding="0" >
		<tr>
		<td class="sep1" style="width:1px;"></td>
		<td class=small >
			<!-- Add and Search -->
			<table border=0 cellspacing=0 cellpadding=0>
			<tr>
			<td>
				<table border=0 cellspacing=0 cellpadding=5>
				<tr>
					{if $EDIT eq 'permitted'}
	                    <td style="padding-right:0px;padding-left:10px;"><a href="index.php?module=PDFMaker&action=EditPDFTemplate&return_action=DetailView&parenttab={$CATEGORY}"><img src="{'btnL3Add.gif'|resourcever}" alt="{$MOD.LBL_ADD_TEMPLATE}" title="{$MOD.LBL_ADD_TEMPLATE}" border=0></a></td>
					{else}
						<td style="padding-right:0px;padding-left:10px;"><img src="{'btnL3Add-Faded.gif'|resourcever}" border=0></td>	
					{/if}
									
						<td style="padding-right:10px"><img src="{'btnL3Search-Faded.gif'|resourcever}" border=0></td>
					
				</tr>
				</table>
			</td>
			</tr>
			</table>
		</td>
		<td style="width:20px;">&nbsp;</td>
		<td class="small">
			<!-- Calendar Clock Calculator and Chat -->
				<table border=0 cellspacing=0 cellpadding=5>
				<tr>
 		            <td style="padding-right:0px;padding-left:10px;"><img src="{'btnL3Calendar-Faded.gif'|resourcever}"></td> 
					{if $WORLD_CLOCK_DISPLAY eq 'true'} 
                        <td style="padding-right:0px"><a href="javascript:;"><img src="{'btnL3Clock.gif'|resourcever}" alt="{$APP.LBL_CLOCK_ALT}" title="{$APP.LBL_CLOCK_TITLE}" border=0 onClick="fnvshobj(this,'wclock');"></a></a></td> 
                    {/if} 
					{* crmv@208475 *}
                    {* crmv@180714 - removed code *}
				</td>
					<td style="padding-right:10px"><img src="{'btnL3Tracker.gif'|resourcever}" alt="{$APP.LBL_LAST_VIEWED}" title="{$APP.LBL_LAST_VIEWED}" border=0 onClick="fnvshobj(this,'tracker');">
                    </td>	
				</tr>
				</table>
		</td>
		<td style="width:20px;">&nbsp;</td>
		<td class="small">
			<!-- Import / Export -->
			<table border=0 cellspacing=0 cellpadding=5>
			<tr>
			{* vtlib customization: Hook to enable import/export button for custom modules. Added CUSTOM_MODULE *}
	   		{if $IMPORT eq 'yes'}	
				<td style="padding-right:0px;padding-left:10px;"><a href="index.php?module=PDFMaker&action=ImportPDFTemplate&parenttab={$CATEGORY}"><img src="{'tbarImport.gif'|resourcever}" alt="{$APP.LBL_IMPORT} {$MODULE|getTranslatedString:$MODULE}" title="{$APP.LBL_IMPORT} {$MODULE|getTranslatedString:$MODULE}" border="0"></a></td>	
			{else}	
				<td style="padding-right:0px;padding-left:10px;"><img src="{'tbarImport-Faded.gif'|resourcever}" border="0"></td>	
			{/if}	
			{if $EXPORT eq 'yes'}
			    <td style="padding-right:10px"><a name='export_link' href="javascript:void(0)" onclick="return VTE.PDFMaker.ExportTemplates();"><img src="{'tbarExport.gif'|resourcever}" alt="{$APP.LBL_EXPORT} {$MODULE|getTranslatedString:$MODULE}" title="{$APP.LBL_EXPORT} {$MODULE|getTranslatedString:$MODULE}" border="0"></a></td> {* crmv@158392 *}
			{else}	
				<td style="padding-right:10px"><img src="{'tbarExport-Faded.gif'|resourcever}" border="0"></td>
            {/if}
			<td style="padding-right:10px"><img src="{'FindDuplicates-Faded.gif'|resourcever}" border="0"></td>
			</tr>
			</table>	
		<td style="width:20px;">&nbsp;</td>
		<td class="small">
			<!-- All Menu -->
				<table border=0 cellspacing=0 cellpadding=5>
				<tr>
					{if $CHECK.moduleSettings eq 'yes'}
		        		<td style="padding-left:10px;"><a href='index.php?module=Settings&action=ModuleManager&module_settings=true&formodule=PDFMaker&parenttab=Settings'><img src="{'settingsBox.png'|resourcever}" alt="{$MODULE|getTranslatedString:$MODULE} {$APP.LBL_SETTINGS}" title="{$MODULE|getTranslatedString:$MODULE} {$APP.LBL_SETTINGS}" border="0"></a></td>
					{/if}
				</tr>
				</table>
		</td>			
		</tr>
		</table>
	</td>
</tr>
</TABLE>