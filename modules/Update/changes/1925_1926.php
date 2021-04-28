<?php

@unlink('Smarty/templates/themes/next/EmailNotification.tpl');
@unlink('Smarty/templates/themes/next/InventoryNotify.tpl');
@unlink('Smarty/templates/themes/next/CreateWordTemplate.tpl');
@unlink('Smarty/templates/themes/next/BackupServerContents.tpl');
@unlink('Smarty/templates/themes/next/PDFFieldAccess.tpl');
@unlink('Smarty/templates/themes/next/ProxyServer.tpl');
@unlink('Smarty/templates/themes/next/Settings/PickList.tpl');
@unlink('Smarty/templates/themes/next/WorkflowEditView.tpl');
@unlink('Smarty/templates/themes/next/WorkflowListViewContents.tpl');
FSUtils::deleteFolder('Smarty/templates/themes/next/modules/PickList');
FSUtils::deleteFolder('Smarty/templates/themes/next/modules/Picklistmulti');
FSUtils::deleteFolder('Smarty/templates/themes/next/modules/Transitions');
@unlink('Smarty/templates/themes/next/ListLoginHistory.tpl');