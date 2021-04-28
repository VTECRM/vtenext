{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{include file="HTMLHeader.tpl" head_include="jquery" BROWSER_TITLE=$MOD.LBL_EDIT_RELATED_BLOCK} {* crmv@198310 *}

<body class="small">
<script language="JavaScript" type="text/javascript" src="modules/PDFMaker/PDFMaker.js"></script>

<table width="100%"  border="0" cellspacing="0" cellpadding="0" class="mailClient mailClientBg">
<tr>
	<td>
		<form name="NewBlock" method="POST" ENCTYPE="multipart/form-data" action="index.php" style="margin:0px">
		<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
		<input type="hidden" name="module" value="PDFMaker">
		<input type="hidden" name="pdfmodule" value="{$REL_MODULE}">
		<input type="hidden" name="primarymodule" value="{$REL_MODULE}">
		<input type="hidden" name="record" value="{$RECORD}">
		<input type="hidden" name="file" value="SaveRelatedBlock">
		<input type="hidden" name="action" value="PDFMakerAjax">
    <input type="hidden" name="step" id="step" value="2">
    
    <div id="filter_columns" style="display:none"><option value="">{$REP.LBL_NONE}</option>{$SECCOLUMNS}</div>
    
		<table width="100%" border="0" cellspacing="0" cellpadding="5" >
			<tr>
				<td  class="moduleName" width="80%">{$MOD.LBL_EDIT_RELATED_BLOCK} </td>
				<td  width=30% nowrap class="componentName" align=right></td>
			</tr>
		</table>
	
	
		<table width="100%" border="0" cellspacing="0" cellpadding="5" class="homePageMatrixHdr"> 
		<tr>
		<td>
		
					<table width="100%" border="0" cellspacing="0" cellpadding="0" > 
						<tr>
							<td width="25%" valign="top" >
								<table width="100%" border="0" cellpadding="5" cellspacing="0" class="small">
									<tr><td id="step1label" class="settingsTabList" style="padding-left:10px;">1. {$REP.LBL_FILTERS} </td></tr>
									<tr><td id="step2label" class="settingsTabSelected" style="padding-left:10px;">2. {$MOD.LBL_BLOCK_STYLE} </td></tr>
								</table>
							</td>
							<td width="75%" valign="top"  bgcolor=white >
								<!-- STEP 1 -->
								<div id="step1" style="display:none;">
								{include file='modules/PDFMaker/BlockFilters.tpl'}
								</div>
								<!-- STEP 2 -->
								{literal}   
                    <script type="text/javascript" src="include/ckeditor/ckeditor.js"></script>
                {/literal} 
								
								<div id="step2" style="display:block;"> 
								    
                    <table class="small" bgcolor="#ffffff" border="0" cellpadding="5" cellspacing="0" width="100%">
										<tr height='10%'>
  										<td colspan="2">
  											<span class="genHeaderGray">{$MOD.LBL_BLOCK_STYLE}</span><hr>
  										</td>
										</tr>
										<tr>
                      <td width="10%" align="right">{$APP.Name}:</td>
                      <td>
                      	<div class="dvtCellInfo">
                      		<input type="text" name="blockname" id="blockname" class="detailedViewTextBox" value="{$BLOCKNAME}">
                      	</div>
                      </td>
                    </tr>
                    <tr>
                      <td colspan="2">
                        <textarea name="relatedblock" id="relatedblock" style="width:90%;height:700px" class=small tabindex="5">{$RELATEDBLOCK}</textarea>
                      </td>
                    </tr>
                </div>
                
                {literal}   
                    <script type="text/javascript">
                    	CKEDITOR.replace('relatedblock',{customConfig:'../../../modules/PDFMaker/fck_config.js'} );
                    </script>
                {/literal}
						</td>
					</tr>
				</table>


			</td>
		</tr>
		</table>
		
	<table width="100%" border="0" cellpadding="0" cellspacing="0" class="reportCreateBottom">
		<tr>
			<td align="right" style="padding:10px;">
			<input type="button" name="back_rep" id="back_rep" value=" &nbsp;&lt;&nbsp;{$APP.LBL_BACK}&nbsp; " {if $RECORD eq ""}disabled="disabled"{/if} class="crmbutton small cancel" onClick="changeStepsback();">
			&nbsp;<input type="button" name="next" id="next" value=" &nbsp;{$APP.LNK_LIST_NEXT}&nbsp;&rsaquo;&nbsp; " onClick="changeEditSteps();" class="crmbutton small save">
			</td>
		</tr>
	</table>
		</form>	

</td>
</tr>
</table>
	
	
</body>
</html>
{if $BACK_WALK eq 'true'}
{literal}
<script>
	hide('step1');
	show('step2');
	document.getElementById('back_rep').disabled = false;
	getObj('step1label').className = 'settingsTabList'; 
	getObj('step2label').className = 'settingsTabSelected';
</script>
{/literal}
{/if}
{if $BACK eq 'false'}
{literal}
<script>
	hide('step1');
	show('step2');
	document.getElementById('back_rep').disabled = true;
	getObj('step1label').className = 'settingsTabList'; 
	getObj('step2label').className = 'settingsTabSelected';
</script>
{/literal}
{/if}