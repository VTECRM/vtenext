<title><?php echo $title; ?></title>
<meta charset="UTF-8" />
<link rel="SHORTCUT ICON" href="<?php echo get_logo_install('favicon'); ?>" />	<!-- crmv@18123 -->
<script type="text/javascript" src="include/js/jquery.js"></script>	<!-- crmv@26523 -->
<script type="text/javascript" src="include/js/general.js"></script>
<link href="themes/softed/vte_bootstrap.css" rel="stylesheet" type="text/css" />
<link href="themes/softed/style.css" rel="stylesheet" type="text/css" />
<link href="themes/softed/install.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="themes/softed/js/material/material.js"></script>
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery.material.options.withRipples += ",.crmbutton:not(.withoutripple)";
		jQuery.material.init();
	});
</script>