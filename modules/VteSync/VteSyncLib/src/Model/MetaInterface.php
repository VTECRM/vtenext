<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Model;

interface MetaInterface {

	public static function fromRawData($data);
	public static function fromCommonMeta(CommonMeta $cmeta);
	public function toCommonMeta();

}