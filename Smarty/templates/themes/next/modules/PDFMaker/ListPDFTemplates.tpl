{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{include file='Buttons_List.tpl'}

{* crmv@158392 *}

<span id="vtbusy_info" style="display:none;">{include file="LoadingIndicator.tpl" LINEAR=true}</span>

<nav class="navbar buttonsList">
	<div class="container-fluid">
		<div class="navbar-header">
			<button type="button" class="navbar-toggle primary" data-toggle="collapse" data-target="#vteNavbar">
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
				<span class="icon-bar"></span>
			</button>
		</div>
		<div class="collapse navbar-collapse" id="vteNavbar">
			<ul class="nav navbar-nav navbar-right">
				<li>
					{if $EDIT eq 'permitted'}
						<button class="crmbutton with-icon save crmbutton-nav" type="submit" name="profile" onclick="document.massdelete.action.value='EditPDFTemplate'; document.massdelete.parenttab.value='Tools'; document.massdelete.submit();">
							<i class="vteicon">add</i>
							{$MOD.LBL_ADD_TEMPLATE}
						</button>
					{/if}
				</li>
				<li>
					<div class="dropdown">
						<button type="button" class="crmbutton with-icon save crmbutton-nav" data-toggle="dropdown">
							<i class="vteicon">reorder</i>
							{'LBL_OTHER'|getTranslatedString:'Users'}
						</button>
						<ul class="dropdown-menu">
							{if $DELETE eq 'permitted'}
								<li>
									<a href="javascript:void(0)" onclick="return VTE.PDFMaker.massDelete();">
										<i class="vteicon">delete</i> {$MOD.LBL_DELETE}
									</a>
								</li>
							{/if} 
							{if $IS_ADMIN eq '1' && $TO_UPDATE eq 'true'}
								<li>
									<a href="javascript:void(0)" onclick="window.location.href='index.php?module=PDFMaker&action=update&parenttab=Tools'">{$MOD.LBL_UPDATE}</a>
								</li>
							{/if}
							<li>
								<a href="javascript:void(0)" onclick="window.location='index.php?module=PDFMaker&amp;action=ImportPDFTemplate'">
									<i class="vteicon">file_download</i> {$APP.LBL_IMPORT}
								</a>
							</li>
							<li>
								<a href="javascript:void(0)" onclick="return VTE.PDFMaker.ExportTemplates();">
									<i class="vteicon">file_upload</i> {$APP.LBL_EXPORT}
								</a>
							</li>
						</ul>
					</div>
				</li>
			</ul>
		</div>
	</div>
</nav>

<div class="container-fluid">
	<div class="row">
		<div class="col-sm-12">
			<form name="massdelete" method="POST" onsubmit="VteJS_DialogBox.block();">
				<input name="idlist" type="hidden">
				<input name="module" type="hidden" value="PDFMaker">
				<input name="parenttab" type="hidden" value="Tools">
				<input name="action" type="hidden" value="">

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

				<div class="vte-card">
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
										<td style="height: 340px" align="center" colspan="7">
											<div class="vte-collection-empty">
												<div class="collection-item">
													<div class="circle">
														<i class="vteicon nohover">picture_as_pdf</i>
													</div>
													<h4 class="title">{$APP.LBL_NO} {$MOD.LBL_TEMPLATE} {$APP.LBL_FOUND}</h4>
													<p>
														{$APP.LBL_YOU_CAN_CREATE} {$APP.LBL_A} {$MOD.LBL_TEMPLATE} {$APP.LBL_NOW}. {$APP.LBL_CLICK_THE_LINK}:
														<br>
														<a href="index.php?module=PDFMaker&action=EditPDFTemplate&parenttab=Tools">{$APP.LBL_CREATE} {$APP.LBL_A} {$MOD.LBL_TEMPLATE}</a>
													</p>
												</div>
											</div>
										</td>
									</tr>
								{/foreach}
							</tbody>
						</table>
					</div>
				</div>
			</form>
		</div>
	</div>
</div>