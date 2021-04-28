{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}
 
{* crmv@140887 *}

{if empty($IN_LOGIN)}
	{literal}
	<script type="text/javascript">
		var mainContainer = jQuery('body').get(0);
		var wrapperHeight = parseInt(visibleHeight(mainContainer));
		
		jQuery('#mainContent').css('min-height', wrapperHeight + 'px');
		
		if (window.Theme) {
			Theme.hideLoadingMask();
		}
	</script>
	{/literal}
	
	</div> <!-- #mainContent -->
	</div> <!-- #mainContainer -->
{/if}

</body>
</html>