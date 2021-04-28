<?php 

/* crmv@187406 */

unlink("themes/softed/scss/wizard.scss");
unlink("themes/softed/wizard.css");
unlink("themes/next/scss/wizard.scss");
unlink("themes/next/wizard.css");
unlink("themes/next/scss/next.scss");
unlink("themes/next/next.css");

@unlink('Smarty/templates/themes/next/modules/Campaigns/NewsletterWizard.tpl');
FSUtils::deleteFolder('Smarty/templates/themes/next/modules/Campaigns');

@unlink('Smarty/templates/themes/next/modules/Messages/Settings/Account.tpl');
@unlink('Smarty/templates/themes/next/modules/Messages/Settings/Accounts.tpl');
FSUtils::deleteFolder('Smarty/templates/themes/next/modules/Messages/Settings');
