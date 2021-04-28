<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
$currtime = date("Y:m:d:H:i:s");
list($y,$m,$d,$h,$min,$sec) = explode(':',$currtime);	//crmv@39176
echo "[{YEAR:'".$y."',MONTH:'".$m."',DAY:'".$d."',HOUR:'".$h."',MINUTE:'".$min."'}]";
die;
