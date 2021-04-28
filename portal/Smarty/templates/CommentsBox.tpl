{*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************}
{* crmv@173271 *}

<div class="panel panel-default">
	<div class="panel-heading">
		<h4 style="line-height:35px;">
			<i class="material-icons">chat</i>
			{'LBL_TICKET_COMMENTS'|getTranslatedString}
			<span class="badge">{$BADGE}</span>
			<div class="pull-right" id="comments">
				<button id="comments-close" type="button" class="btn btn-default" style="margin:0px;">
					<i class="material-icons">arrow_downward</i>
				</button>
			</div>
			<div class="clearfix"></div>
		</h4>
	</div>
	<div class="panel-body" id="panel-comments">
		{if !empty($COMMENTS)}
			<ul class="timeline">
				{foreach from=$COMMENTS item=comm} {* crmv@160733 *}
					{if $comm.ownertype eq 'customer'}
					<li>
						<div class="timeline-badge users">
							<i class="fa fa-check"></i>
						</div>
						<div class="timeline-panel">
							<div class="timeline-heading">
								<h4 class="timeline-title">{$comm.num} {'LBL_COMMENT_BY'|getTranslatedString} : {$comm.owner}</h4> {* crmv@160733 *}
								<p>
									<small class="text-muted">
										<i class="fa fa-clock-o"></i>
									 	{$comm.createdtime}
									</small>
								</p>
							</div>
							<div class="timeline-body">
								<p>{$comm.comments}</p> {* crmv@160733 *}
							</div>
						</div>
					</li>
					{else}
					<li class="timeline-inverted">
						<div class="timeline-badge vtecrm">
							<i class="fa-vtecrm"></i>
						</div>
						<div class="timeline-panel">
							<div class="timeline-heading">
								<h4 class="timeline-title">{$comm.num} {'LBL_COMMENT_BY'|getTranslatedString} : {$comm.owner}</h4> {* crmv@160733 *}
								<p>
									<small class="text-muted">
										<i class="fa fa-clock-o"></i>
										{$comm.createdtime}
									</small>
								</p>
							</div>
							<div class="timeline-body">
								<p>{$comm.comments}</p> {* crmv@160733 *}
							</div>
						</div>
					</li>
					{/if}
				{/foreach}
			</ul>
		{else}
			<b>{'LBL_NO_COMMENTS'|getTranslatedString}</b>
		{/if}
	</div>
</div>