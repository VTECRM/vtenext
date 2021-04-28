<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
VteSession::set('MorphsuitZombie', true);
VteSession::set("checkDataMorphsuit", 'yes');
header('location: index.php');
?>