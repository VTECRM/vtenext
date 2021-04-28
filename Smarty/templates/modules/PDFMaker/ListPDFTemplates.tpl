{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{include file='Buttons_List.tpl'}

<table align="center" border="0" cellpadding="0" cellspacing="0" width="98%">   
	<tr>
	    <td class="showPanelBg" style="padding: 10px;" valign="top" width="100%">
		    <form  name="massdelete" method="POST" onsubmit="VteJS_DialogBox.block();">
			    <input name="idlist" type="hidden">
			    <input name="module" type="hidden" value="PDFMaker">
			    <input name="parenttab" type="hidden" value="Tools">
			    <input name="action" type="hidden" value="">
			
			    <table border=0 cellspacing=0 cellpadding=0 width=100% >
			    	<tr>
			    		<td>
							<div class="vte-card">
								<table border=0 cellspacing=0 cellpadding=5 width=100% class="listTableTopButtons">
									<tr>
										{if $DELETE eq 'permitted'}
											<td class=small>
												<input type="submit" value="{$MOD.LBL_DELETE}" onclick="return VTE.PDFMaker.massDelete();" class="crmButton delete small">
											</td>
										{/if}
										<td align="right">
											{if $EDIT eq 'permitted'}
												<input class="crmButton create small" type="submit" value="{$MOD.LBL_ADD_TEMPLATE}" name="profile"  onclick="this.form.action.value='EditPDFTemplate'; this.form.parenttab.value='Tools'" />
												&nbsp;
											{/if}
											{if $IS_ADMIN eq '1' && $TO_UPDATE eq 'true'}
											<input type="button" value="{$MOD.LBL_UPDATE}" class="crmbutton small delete" title="{$MOD.LBL_UPDATE}" onclick="window.location.href='index.php?module=PDFMaker&action=update&parenttab=Tools'" />
											&nbsp;
											{/if} 
											{if $IS_ADMIN eq '1'}					      
												<span id="vtbusy_info" style="display:none;" valign="bottom">{include file="LoadingIndicator.tpl"}</span>
											{/if}
										</td>		
									</tr>
								</table>
						
								{if $DIR eq 'asc'} 
									{assign var="dir_img" value='arrow_drop_up'} 
								{else} 
									{assign var="dir_img" value='arrow_drop_down'} 
								{/if}
								
								{assign var="name_dir" value="asc"}
								{assign var="module_dir" value="asc"}
								{assign var="description_dir" value="asc"}
								
								{if $ORDERBY eq 'filename' && $DIR eq 'asc'}        
									{assign var="name_dir" value="desc"}
								{elseif $ORDERBY eq 'module' && $DIR eq 'asc'}                
									{assign var="module_dir" value="desc"}
								{elseif $ORDERBY eq 'description' && $DIR eq 'asc'}
									{assign var="description_dir" value="desc"}           
								{/if}
						
								<div class="table-responsive">
									<table class="vtetable">
										<thead>
											<tr>
												<th width="2%">#</th>
												<th width="3%">{$MOD.LBL_LIST_SELECT}</th>
												<th width="20%">
													<a href="index.php?module=PDFMaker&action=index&parenttab=Tools&orderby=name&dir={$name_dir}">
														{$MOD.LBL_PDF_NAME}
														{if $ORDERBY eq 'filename'}<i class="vteicon vtesorticon md-text">{$dir_img}</i>{/if}
													</a>
												</th>
												<th width="20%">
													<a href="index.php?module=PDFMaker&action=index&parenttab=Tools&orderby=module&dir={$module_dir}">
														{$MOD.LBL_MODULENAMES}
														{if $ORDERBY eq 'module'}<i class="vteicon vtesorticon md-text">{$dir_img}</i>{/if}
													</a>
												</th>
												<th width="40%">
													<a href="index.php?module=PDFMaker&action=index&parenttab=Tools&orderby=description&dir={$description_dir}">
														{$MOD.LBL_DESCRIPTION}
														{if $ORDERBY eq 'description'}<i class="vteicon vtesorticon md-text">{$dir_img}</i>{/if}
													</a>
												</th>
												{if $VERSION_TYPE neq 'deactivate'}
													<th width="5%">{$APP.LBL_STATUS}</th>
													<th width="5%">{$APP.LBL_ACTIONS}</th>
												{/if}
											</tr>
										</thead>
										<tbody>
											{foreach item=template name=mailmerge from=$PDFTEMPLATES}
												<tr class="{if $template.status eq 0}bg-danger{/if}">
													<td>{$smarty.foreach.mailmerge.iteration}</td>
													<td>
														<input type="checkbox" class=small name="selected_id" value="{$template.templateid}">
													</td>
													<td>{$template.filename}</td>
													<td class="{if $template.status eq 0}text-muted{/if}">{$template.module}</td>
													<td class="{if $template.status eq 0}text-muted{/if}">{$template.description}</td>
													{if $VERSION_TYPE neq 'deactivate'}
														<td class="{if $template.status eq 0}text-muted{/if}">{$template.status_lbl}</td>
														<td nowrap>{$template.edit}</td>
													{/if}
												</tr>
											{foreachelse}
												<tr>
													<td style="background-color:#efefef;height:340px" align="center" colspan="6">
														<div style="border: 3px solid rgb(153, 153, 153); background-color: rgb(255, 255, 255); width: 45%; position: relative; z-index: 10000000;">
															<table border="0" cellpadding="5" cellspacing="0" width="98%">
															<tr><td rowspan="2" width="25%"><img src="{'empty.jpg'|resourcever}" height="60" width="61"></td>
																<td style="border-bottom: 1px solid rgb(204, 204, 204);" nowrap="nowrap" width="75%" align="left">
																	<span class="genHeaderSmall">{$APP.LBL_NO} {$MOD.LBL_TEMPLATE} {$APP.LBL_FOUND}</span>
																</td>
															</tr>
															<tr>
																<td class="small" align="left" nowrap="nowrap">{$APP.LBL_YOU_CAN_CREATE} {$APP.LBL_A} {$MOD.LBL_TEMPLATE} {$APP.LBL_NOW}. {$APP.LBL_CLICK_THE_LINK}:<br>
																	&nbsp;&nbsp;-<a href="index.php?module=PDFMaker&action=EditPDFTemplate&parenttab=Tools">{$APP.LBL_CREATE} {$APP.LBL_A} {$MOD.LBL_TEMPLATE}</a><br>
																</td>
															</tr>
															</table>
														</div>
													</td>
												</tr>
											{/foreach}
										</tbody>
									</table>
								</div>
							</div>
						</form>
					</td>
				</tr>
				<tr><td align="center" class="small" style="color: rgb(153, 153, 153);">{$MOD.PDF_MAKER} {$VERSION} {$MOD.COPYRIGHT}</td></tr>
			</table>
		</td>
	</tr>
</table>