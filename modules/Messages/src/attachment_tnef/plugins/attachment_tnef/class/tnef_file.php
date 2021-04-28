<?php
/**
 * tnef_file.php
 *
 * Copyright (c) 2003 Bernd Wiegmann <bernd@wib-software.de>
 * Copyright (c) 2008 The NaSMail Project
 * This file is part of NaSMail attachment_tnef plugin.
 *
 * This plugin is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free
 * Software Foundation; either version 2 of the License, or (at your option)
 * any later version.
 *
 * This plugin is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * plugin; if not, write to the Free Software Foundation, Inc., 59 Temple
 * Place, Suite 330, Boston, MA  02111-1307  USA
 * @package plugins
 * @subpackage attachment_tnef
 * @version $Id: tnef_file.php 1166 2008-02-23 07:18:12Z tokul $
 */

/**
 * @package plugins
 * @subpackage attachment_tnef
 */
class TnefFile extends TnefFileBase {
    var $metafile;

    /**
     * Constructor
     * @param boolean Controls logging of debug info
     */
    function TnefFile($tnef_debug) {
        $this->TnefFileBase($tnef_debug);
    }

    function getMetafile() {
        return $this->metafile;
    }

    function receiveTnefAttribute($attribute, $value, $length) {
        switch ($attribute) {
        case TNEF_AFILENAME: // filename
            $this->name = preg_replace('/.*[\/](.*)$/', '\1', $value);
            break;

        case TNEF_ATTACHDATA: // the attachment itself
            $this->content = $value;
            break;

        case TNEF_ATTACHMETAFILE:  // a metafile
            $this->metafile = $value;
            break;

        case TNEF_AATTACHCREATEDATE:
            $this->created = new TnefDate;
            $this->created->setTnefBuffer($value);
            break;

        case TNEF_AATTACHMODDATE:
            $this->modified = new TnefDate;
            $this->modified->setTnefBuffer($value);
            break;
        }
    }

    function receiveMapiAttribute($attr_type, $attr_name, $value, $length) {
        switch ($attr_name) {
        case TNEF_MAPI_ATTACH_LONG_FILENAME: // used in preference to AFILENAME value
            //$this->name = preg_replace('/.*[\/](.*)$/', '\1', $value);  // strip path	//crmv@112756
            break;

        case TNEF_MAPI_ATTACH_MIME_TAG: // Is this ever set, and what is format?
            $type0 = preg_replace('/^(.*)/.*/', '\1', $value);
            $type1 = preg_replace('/.*/(.*)$/', '\1', $value);
            $this->type = "$type0/$type1";
            break;

        case TNEF_MAPI_ATTACH_EXTENSION:
            $type = ext_to_mime($value);
            if ($type) {
                $this->type = $type;
            }
            break;
        }
    }
}
