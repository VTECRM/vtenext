<?php
/**
 * tnef_attachment.php
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
 * @version $Id: tnef_attachment.php 1166 2008-02-23 07:18:12Z tokul $
 */

/**
 * @package plugins
 * @subpackage attachment_tnef
 */
class TnefAttachment {
    var $tnef_debug;
    var $mailinfo;
    var $files;
    var $files_nested;
    var $attachments;
    var $current_receiver;

    function TnefAttachment($debug) {
        $this->tnef_debug = $debug;
        $this->files = array();
        $this->attachments = array();
        $this->mailinfo = new TnefMailinfo;
    }

    function &getFiles() {
        return $this->files;
    }

    function &getFilesNested() {
        if (!$this->files_nested) {
            $this->files_nested = array();

            $num_attach = count($this->attachments);
            if ($num_attach > 0) {
                for ($cnt = 0; $cnt < $num_attach; $cnt++) {
                    $this->addFiles($this->files_nested, $this->files);
                    $this->addFiles($this->files_nested, $this->attachments[$cnt]->getFilesNested());
                }
            } else {
                $this->addFiles($this->files_nested, $this->files);
            }
        }

        return $this->files_nested;
    }

    function addFiles(&$add_to, &$add) {
        $num_files = count($add);
        for ($cnt = 0; $cnt < $num_files; $cnt++) {
            if ((get_class($add[$cnt]) != "tneffilertf") || ($add[$cnt]->getSize() > 250)) {
                $add_to[] = &$add[$cnt];
            }
        }
    }

    function addFilesCond(&$add_to, &$add) {
        $num_files = count($add);
        for ($cnt = 0; $cnt < $num_files; $cnt++) {
            if ((get_class($add[$cnt]) == "tneffilertf") && ($add[$cnt]->getSize() > 250)) {
                $add_to[] = &$add[$cnt];
            }
        }
    }

    function &getAttachments() {
        return $this->attachments;
    }

    function &getMailinfo() {
        return $this->mailinfo;
    }

    function decodeTnef(&$buffer) {
        $tnef_signature = tnef_geti32($buffer);
        if ($tnef_signature == TNEF_SIGNATURE) {
            $tnef_key = tnef_geti16($buffer);
            if ($this->tnef_debug) {
                tnef_log(sprintf("Signature: 0x%08x\nKey: 0x%04x\n", $tnef_signature, $tnef_key));
            }

            while (strlen($buffer) > 0) {
                $lvl_type = tnef_geti8($buffer);

                switch($lvl_type) {
                case TNEF_LVL_MESSAGE:
                    $this->tnef_decode_attribute($buffer);
                    break;

                case TNEF_LVL_ATTACHMENT:
                    $this->tnef_decode_attribute($buffer);
                    break;

                default:
                    if ($this->tnef_debug) {
                        $len = strlen($buffer);
                        if ($len > 0) {
                            tnef_log("Invalid file format! Unknown Level $lvl_type. Rest=$len");
                        }
                    }
                    break;
                }
            }
        } else {
            if ($this->tnef_debug) {
                tnef_log("Invalid file format! Wrong signature.");
            }
        }
    }

    function tnef_decode_attribute(&$buffer) {
        $attribute = tnef_geti32($buffer);     // attribute if
        $length = tnef_geti32($buffer);        // length
        $value = tnef_getx($length, $buffer);  // data
        tnef_geti16($buffer);                  // checksum

        if ($this->tnef_debug) {
            show_tnef_attribute($attribute, $value, $length);
        }

        switch($attribute) {
        case TNEF_ARENDDATA:                   // marks start of new attachment
            if ($this->tnef_debug) {
                tnef_log("Creating new File for Attachment");
            }
            $this->current_receiver = new TnefFile($this->tnef_debug);
            $this->files[] = &$this->current_receiver;
            break;

        case TNEF_AMAPIATTRS:
            if ($this->tnef_debug) {
                tnef_log("mapi attrs");
            }
            $this->extract_mapi_attrs($value);
            break;

        case TNEF_AMAPIPROPS:
            if ($this->tnef_debug) {
                tnef_log("mapi props");
            }
            $this->extract_mapi_attrs($value);
            break;

        case TNEF_AMCLASS:
            $value = substr($value, 0, $length - 1);
            if ($value == 'IPM.Contact') {
                if ($this->tnef_debug) {
                    tnef_log("Creating vCard Attachment");
                }
                $this->current_receiver = new TnefvCard($this->tnef_debug);
                $this->files[] = &$this->current_receiver;
            }
            break;

        default:
            $this->mailinfo->receiveTnefAttribute($attribute, $value, $length);
            if ($this->current_receiver) {
                $this->current_receiver->receiveTnefAttribute($attribute, $value, $length);
            }
            break;
        }
    }

    function extract_mapi_attrs(&$buffer) {
        $number = tnef_geti32($buffer); // number of attributes
        $props = 0;
        $ended = 0;

        while ((strlen($buffer) > 0) && ($props < $number) && (!$ended)) {
            $props++;
            unset($value);
            unset($named_id);
            $length = 0;
            $have_multivalue = 0;
            $num_multivalues = 1;
            $attr_type = tnef_geti16($buffer);
            $attr_name = tnef_geti16($buffer);

            if (($attr_type & TNEF_MAPI_MV_FLAG) != 0) {
                if ($this->tnef_debug) {
                    tnef_log("Multivalue Attribute found.");
                }
                $have_multivalue = 1;
                $attr_type = $attr_type & ~TNEF_MAPI_MV_FLAG;
            }

            if (($attr_name >= 0x8000) && ($attr_name < 0xFFFE)) {      // Named Attribute
                $guid = tnef_getx(16, $buffer);
                $named_type = tnef_geti32($buffer);
                switch ($named_type) {
                case TNEF_MAPI_NAMED_TYPE_ID:
                    $named_id = tnef_geti32($buffer);
                    $attr_name = $named_id;
                    if ($this->tnef_debug) {
                        tnef_log(sprintf("Named Id='0x%04x'", $named_id));
                    }
                    break;

                case TNEF_MAPI_NAMED_TYPE_STRING:
                    $attr_name = 0x9999;                                             // dummy to identify strings
                    $idlen = tnef_geti32($buffer);
                    if ($this->tnef_debug) {
                        tnef_log("idlen=$idlen");
                    }
                    $buflen = $idlen + ((4 - ($idlen % 4)) % 4);                     // pad to next 4 byte boundary
                    if ($this->tnef_debug) {
                        tnef_log("buflen=$buflen");
                    }
                    $named_id = substr(tnef_getx($buflen, $buffer), 0, $idlen );     // read and truncate to length
                    if ($this->tnef_debug) {
                        tnef_log("Named Id='$named_id'");
                    }
                    break;

                default:
                    if ($this->tnef_debug) {
                        tnef_log(sprintf("Unknown Named Type 0x%04x found", $named_type));
                    }
                    break;
                }
            }

            if ($have_multivalue) {
                $num_multivalues = tnef_geti32($buffer);
                if ($this->tnef_debug) {
                    tnef_log("Number of multivalues=$num_multivalues");
                }
            }
$value = '';
            switch($attr_type) {
            case TNEF_MAPI_NULL:
                break;

            case TNEF_MAPI_SHORT:
                $value = tnef_geti16($buffer);
                break;

            case TNEF_MAPI_INT:
            case TNEF_MAPI_BOOLEAN:
                for ($cnt = 0; $cnt < $num_multivalues; $cnt++) {
                    $value = tnef_geti32($buffer);
                }
                break;

            case TNEF_MAPI_FLOAT:
            case TNEF_MAPI_ERROR:
                $value = tnef_getx(4, $buffer);
                break;

            case TNEF_MAPI_DOUBLE:
            case TNEF_MAPI_APPTIME:
            case TNEF_MAPI_CURRENCY:
            case TNEF_MAPI_INT8BYTE:
            case TNEF_MAPI_SYSTIME:
                $value = tnef_getx(8, $buffer);
                break;

            case TNEF_MAPI_CLSID:
                if ($this->tnef_debug) {
                    tnef_log("What is a MAPI CLSID ????");
                }
                break;

            case TNEF_MAPI_STRING:
            case TNEF_MAPI_UNICODE_STRING:
            case TNEF_MAPI_BINARY:
            case TNEF_MAPI_OBJECT:
                if ($have_multivalue) {
                    $num_vals = $num_multivalues;
                } else {
                    $num_vals = tnef_geti32($buffer);
                }
                if ($num_vals > 20) {               // A Sanity check.
                    $ended = 1;
                    if ($this->tnef_debug) {
                        tnef_log("Number of entries in String Attributes=$num_vals. Aborting Mapi parsing.");
                    }
                } else {
                    for ($cnt = 0; $cnt < $num_vals; $cnt++) {
                        $length = tnef_geti32($buffer);
                        $buflen = $length + ((4 - ($length % 4)) % 4); // pad to next 4 byte boundary
                        if ($attr_type == TNEF_MAPI_STRING) {
                            $length -= 1;
                        }
                        $value = substr(tnef_getx($buflen, $buffer), 0, $length); // read and truncate to length
                    }
                }
                break;

            default:
                if ($this->tnef_debug) {
                    tnef_log("Unknown mapi attribute! $attr_type");
                }
                break;
            }

            if ($this->tnef_debug) {
                show_mapi_attribute($attr_type, $attr_name, $value, $length);
            }

            switch ($attr_name) {
            case TNEF_MAPI_ATTACH_DATA:
                if ($this->tnef_debug) {
                    tnef_log("MAPI Found nested attachment. Processing new one.");
                }
                tnef_getx(16, $value); // skip the next 16 bytes (unknown data)
                $att = new TnefAttachment($this->tnef_debug);
                $att->decodeTnef($value);
                $this->attachments[] = &$att;
                if ($this->tnef_debug) {
                    tnef_log("MAPI Finished nested attachment. Continuing old one.");
                }
                break;

            case TNEF_MAPI_RTF_COMPRESSED:
                if ($this->tnef_debug) {
                    tnef_log("MAPI Found Compressed RTF Attachment.");
                }
                $this->files[] = new TnefFileRTF($this->tnef_debug, $value);;
                break;

            default:
                $this->mailinfo->receiveMapiAttribute($attr_type, $attr_name, $value, $length);
                if ($this->current_receiver) {
                    $this->current_receiver->receiveMapiAttribute($attr_type, $attr_name, $value, $length);
                }
                break;
            }
        }
        if (($this->tnef_debug) && ($ended)) {
            $len = strlen($buffer);
            for ($cnt = 0; $cnt < $len; $cnt++) {
                $ord = ord($buffer{$cnt});
                if ($ord == 0) {
                    $char = "";
                } else {
                    $char = $buffer{$cnt};
                }
                tnef_log(sprintf("Char Nr. %6d = 0x%02x = '%s'", $cnt, $ord, $char));
            }
        }
    }
}
