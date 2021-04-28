{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{if $STUFFTYPE eq 'SDKIframe'}
	{assign var="URL" value="$URL&stuffid=$WIDGETID"}
{/if}

<iframe id="url_contents_{$WIDGETID}" src="{$URL}" frameborder="0" scrolling="auto" width="100%" sandbox="allow-same-origin allow-scripts allow-popups allow-forms allow-top-navigation allow-downloads"></iframe> {* crmv@105924 crmv@155089 crmv@206811 *}

{if $URL|strpos:'&widget=DetailViewBlockCommentWidget' neq false}
	{assign var="URL_TEMP" value=$URL|cat:"&target_frame=url_contents_"|cat:$WIDGETID|cat:'&indicator=refresh_'|cat:$WIDGETID}
	{assign var="URL" value=""}
	<script type="text/javascript" id="loadModCommentsNewsScript_{$WIDGETID}">
		loadModCommentsNews(VTE.ModCommentsCommon.default_number_of_news,'url_contents_{$WIDGETID}','refresh_{$WIDGETID}');
		jQuery('#url_contents_{$WIDGETID}').attr('height','610px');
	</script>
{elseif $STUFFTYPE eq 'Iframe'}
	<script type="text/javascript">
		jQuery('#url_contents_{$WIDGETID}').css('height','460px');
	</script>
{elseif $STUFFTYPE eq 'SDKIframe'}
	<script type="text/javascript">
		jQuery('#url_contents_{$WIDGETID}').css('height',jQuery('#stuff_{$WIDGETID} div.MatrixBorder').innerHeight()-5);
	</script>
{else}
	<script type="text/javascript">
		jQuery('#url_contents_{$WIDGETID}').css('height',jQuery('#stuff_{$WIDGETID} div.MatrixBorder').innerHeight()-5);
		jQuery('#url_contents_{$WIDGETID}').css('height',jQuery('#stuff_{$WIDGETID} div.MatrixBorderURL').innerHeight()-5);
	</script>
{/if}