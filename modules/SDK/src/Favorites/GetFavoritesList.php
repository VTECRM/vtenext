<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
//crmv@26986
global $current_user;
require_once('modules/SDK/src/Favorites/Utils.php');
echo getHtmlFavoritesList($current_user->id,$_REQUEST['mode']);
exit;
//crmv@26986e
?>