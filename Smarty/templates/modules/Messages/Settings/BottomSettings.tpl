{*
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
  * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
*}

{* crmv@46468 *}

<script type="text/javascript">
{literal}
jQuery(document).ready(function(){
	parent.jQuery('.fancybox-close').unbind();
	parent.jQuery('.fancybox-close').bind('click', function(){
		parent.location.reload();
	});
	parent.jQuery('.fancybox-overlay').unbind();
	parent.jQuery('.fancybox-overlay').bind('click', function(){
		parent.location.reload();
	});
});
{/literal}
</script>