<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

// crmv@140887

$qc_modules = getQuickCreateModules();

$smarty = new VteSmarty();

$smarty->assign("QCMODULE", $qc_modules);

$smarty->display("FastQuickCreate.tpl");