{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@173271 *} 

<div class="panel panel-default">
	<div class="panel-heading">
		<h4 style="line-height:35px;">
			<i class="material-icons">cloud_upload</i>
			{'LBL_ATTACHMENTS'|getTranslatedString}
		</h4>
	</div>
	<div class="panel-body">
		<table class="table">
			<thead>
				<tr>
					<th>{'LBL_ATTACHMENT_NAME'|getTranslatedString}</th>
				</tr>
			</thead>
			<tbody>
				{foreach from=$FILES item=FILE}
					<tr>
						<td>
							{if !empty($FILE.filelocationtype)}
								{if $FILE.filelocationtype eq "I" || $FILE.filelocationtype eq "B"}
									<a href="index.php?downloadfile=true&amp;fileid={$FILE.fileid}&amp;filename={$FILE.filename}&amp;filetype={$FILE.filetype}&amp;filesize={$FILE.filesize}&amp;ticketid={$TICKETID}">
										{$FILE.filename}
									</a>
								{/if}
								{if $FILE.filelocationtype eq "E"}
									<a href="{$FILE.filename}">{$FILE.filename}</a>
								{/if}
							{else}
								{'NO_ATTACHMENTS'|getTranslatedString}
							{/if}
						</td>
					</tr>
				{/foreach}
			</tbody>
		</table>
	</div>
</div>