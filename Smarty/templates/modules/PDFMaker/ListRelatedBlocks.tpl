{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
{* crmv@59054 *}
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset={$APP.LBL_CHARSET}">
	<title>{$MOD.LBL_MANAGE_RELATED_BLOCK}</title>
	<link href="{$THEME_PATH}style.css" rel="stylesheet" type="text/css">
	<script language="JavaScript" type="text/javascript" src="{"include/js/general.js"|resourcever}"></script>
	<script language="JavaScript" type="text/javascript" src="include/js/{$AUTHENTICATED_USER_LANGUAGE}.lang.js"></script> {* crmv@181170 *}
</head>
<body class="small">
<table width="100%"  border="0" cellspacing="0" cellpadding="0" class="mailClient mailClientBg">
<tr>
	<td>
		<form name="NewRep" method="POST" ENCTYPE="multipart/form-data" action="index.php" style="margin:0px">
		<input type="hidden" name="__csrf_token" value="{$CSRF_TOKEN}"> {* crmv@171581 *}
		<input type="hidden" name="module" value="PDFMaker">
		<input type="hidden" name="pdfmodule" value="{$REL_MODULE}">
		<input type="hidden" name="record" value="">
		<input type="hidden" name="file" value="">
		<input type="hidden" name="action" value="PDFMakerAjax">
		<table width="100%" border="0" cellspacing="0" cellpadding="5" >
			<tr>
				<td class="moduleName" width="80%">{$MOD.LBL_MANAGE_RELATED_BLOCK} </td>
				<td width=30% nowrap align=right>{if $EDIT eq 'permitted' && !empty($RELATEDMODULES)} <input class="crmButton create small" type="submit" value="{$APP.LBL_CREATE} {$APP.LBL_A} {$MOD.LBL_RELATED_BLOCK}" name="profile"  onclick="this.form.file.value='EditRelatedBlock';" />&nbsp;{/if}</td>
			</tr>
		</table>
		
		<table width="100%" border="0" cellspacing="0" cellpadding="5" class="homePageMatrixHdr"> 
		<tr>
		<td>
		
		 <table border=0 cellspacing=0 cellpadding=5 width=100% class="listTable">
          <tr>
              <td width=2% class="colHeader small">#</td>
              <td class="colHeader small">{$MOD.LBL_RELATED_BLOCK_NAME}</td>
              <td width=20% class="colHeader small">{$MOD.LBL_MODULENAMES}</td>
              <td width=5% class="colHeader small">{$APP.LBL_ACTIONS}</td>
          </tr>
          {foreach item=block name=mailmerge from=$RELATEDBLOCKS}
          <tr>
              <td class="listTableRow small" valign=top>{$smarty.foreach.mailmerge.iteration}</td>
              <td class="listTableRow small" valign=top>{$block.blockname}</a></td>
              <td class="listTableRow small" valign=top>{$block.secmodule}</a></td>
              <td class="listTableRow small" valign=top nowrap>{$block.edit}</td>
          </tr>
          {foreachelse}
          <tr>
              <td style="background-color:#efefef;height:340px" align="center" colspan="6">
                  <div style="border: 3px solid rgb(153, 153, 153); background-color: rgb(255, 255, 255); width: 45%; position: relative; z-index: 10000000;">
                      <table border="0" cellpadding="5" cellspacing="0" width="98%">
                      <tr><td rowspan="2" width="25%"><img src="{'empty.jpg'|resourcever}" height="60" width="61"></td>
                          <td style="border-bottom: 1px solid rgb(204, 204, 204);" nowrap="nowrap" width="75%" align="left">
                              <span class="genHeaderSmall">{$APP.LBL_NO} {$MOD.LBL_RELATED_BLOCK} {$APP.LBL_FOUND}</span>
                          </td>
                      </tr>
					  {if $EDIT eq 'permitted' && !empty($RELATEDMODULES)}
						  <tr>
							  <td class="small" align="left" nowrap="nowrap">{$APP.LBL_YOU_CAN_CREATE} {$APP.LBL_A} {$MOD.LBL_RELATED_BLOCK} {$APP.LBL_NOW}. {$APP.LBL_CLICK_THE_LINK}:<br>
								  &nbsp;&nbsp;-<a href="index.php?module=PDFMaker&action=PDFMakerAjax&file=EditRelatedBlock&parenttab=Tools&pdfmodule={$REL_MODULE}">{$APP.LBL_CREATE} {$APP.LBL_A} {$MOD.LBL_RELATED_BLOCK}</a><br>
							  </td>
						  </tr>
					  {/if}
                      </table>
                  </div>
              </td>
          </tr>
          {/foreach}
  
          </table>


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
<script>
function deleteRelBlock(id)
{ldelim}
   if (confirm('{$APP.NTC_DELETE_CONFIRMATION}')) 
   {ldelim}
       window.location.href = "index.php?module=PDFMaker&action=PDFMakerAjax&file=EditRelatedBlock&mode=Delete&record=" + id;
   {rdelim}
{rdelim}
</script>