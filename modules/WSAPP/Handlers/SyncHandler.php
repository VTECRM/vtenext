<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

abstract class SyncHandler{

    protected $user;
    protected $key;
    protected $syncServer;
    protected $syncModule;

    abstract function get($module,$token,$user);
    abstract function put($element,$user);
    abstract function map($element,$user);
    abstract function nativeToSyncFormat($element);
    abstract function syncToNativeFormat($element);

}
?>