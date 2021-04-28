<?php
/**
 * Support functions
 *
 * Copyright (c) 2002-2003 Bernd Wiegmann <bernd@wib-software.de>
 *                    Graham Norbury <gnorbury@bondcar.com>
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
 * @version $Id: functions.php 1173 2008-02-23 18:21:11Z tokul $
 */

/**
 * Maps extension to mime type
 *
 * @param string $extension
 * @return string
 */
function ext_to_mime($extension) {
    $ext_mime_array = array(
        '.gif' => 'image/gif',
		'.jpg' => 'image/jpg',
		'.png' => 'image/png',
		'.vcf' => 'text/x-vcard',
		'.zip' => 'application/zip',
		'.rtf' => 'application/rtf',
		'.pdf' => 'application/pdf');
    if (array_key_exists($extension,$ext_mime_array)) {
        return $ext_mime_array[$extension];
    } else {
        return 'application/octet-stream';
    }
}

/**
 * Just a little helper for $tnef_debug == 1
 * Will not create any output in production environment.
 * @param string $string Debug message
 */
function tnef_log($string) {
    global $attachment_dir;
    //error_log($string . "\n", 3, $attachment_dir . "tnef.log");
    echo $string . "\n<br>";	//crmv@112756
}

/**
 * Creates links for application/ms-tnef attachments
 * @param array Attachment hook arguments
 */
function attachment_tnef_link_do(&$Args) {
    nsm_bindtextdomain('attachment_tnef',SM_PATH . 'locale');
    nsm_textdomain('attachment_tnef');

    $Args[1]['attachment_tnef']['href'] = '../plugins/attachment_tnef/tnef.php?startMessage=' .
        $Args[2] . '&amp;passed_id=' . $Args[3] . '&amp;mailbox=' .
        $Args[4] . '&amp;ent_id=' . $Args[5];

    // Attached rfc822 message
    if (sqgetGlobalVar('passed_ent_id',$passed_ent_id,SQ_FORM)) {
        $Args[1]['attachment_tnef']['href'].= '&amp;passed_ent_id=' . urlencode($passed_ent_id);
    }

    // Search data
    if (!empty($Args[8]) && !empty($Args[9])) {
        $Args[1]['attachment_tnef']['href'] .= '&amp;where=' . urlencode($Args[8]) .
            '&amp;what=' . urlencode($Args[9]);
    }

    $Args[1]['attachment_tnef']['text'] = _("View");
    $Args[6] = $Args[1]['attachment_tnef']['href'];

    nsm_textdomain('nasmail');
}


function tnef_getx($size, &$buf) {
    $value = null;
    $len = strlen($buf);
    if ($len >= $size) {
        $value = substr($buf, 0, $size);
        $buf = substr_replace($buf, '', 0, $size);
    } else {
        substr_replace($buf, '', 0, $len);
    }
    return $value;
}

function tnef_geti8(&$buf) {
    $value = null;
    $len = strlen($buf);
    if ($len >= 1) {
        $value = ord($buf{0});
        $buf = substr_replace($buf, '', 0, 1);
    } else {
        substr_replace($buf, '', 0, $len);
    }
    return $value;
}

function tnef_geti16(&$buf) {
    $value = null;
    $len = strlen($buf);
    if ($len >= 2) {
        $value = ord($buf{0}) +
            (ord($buf{1}) << 8);
        $buf = substr_replace($buf, '', 0, 2);
    } else {
        substr_replace($buf, '', 0, $len);
    }
    return $value;
}

function tnef_geti32(&$buf) {
    $value = null;
    $len = strlen($buf);
    if ($len >= 4) {
        $value = ord($buf{0}) +
            (ord($buf{1}) << 8) +
            (ord($buf{2}) << 16) +
            (ord($buf{3}) << 24);
        $buf = substr_replace($buf, '', 0, 4);
    } else {
        substr_replace($buf, '', 0, $len);
    }
    return $value;
}

/** */
function show_tnef_attribute($attribute, $value, $length) {
    switch($attribute)    {
    case TNEF_ABODYTEXT:
        tnef_log(sprintf("TNEF attribute: <b>Embedded message:</b><pre>%s</pre>",$value));
        break;

    case TNEF_ASUBJECT:
        $value = substr($value, 0, $length - 1);
        tnef_log("TNEF attribute: Subject($length)='$value'");
        break;

    case TNEF_AVERSION:
        $value = tnef_geti32($value);
        tnef_log(sprintf("TNEF attribute: TNEF Version = 0x%x", $value));
        break;

    case TNEF_AOEMCODEPAGE:
        tnef_log("TNEF attribute: Codepage BYTEs ($length)");
        break;

    case TNEF_ASTATUS:
        $value = tnef_geti8($value);
        tnef_log("TNEF attribute: Message Status=$value");
        break;

    case TNEF_AMCLASS:
        $value = substr($value, 0, $length - 1);
        tnef_log("TNEF attribute: Message Class DWORDs ($length) as String='$value'");
        break;

    case TNEF_AREQUESTRES:
        $value = tnef_geti16($value);
        tnef_log("TNEF attribute: Request Res=$value");
        break;

    case TNEF_ADATERECEIVED:
        tnef_log("TNEF attribute: Date received ($length)");
        break;

    case TNEF_ADATESENT:
        tnef_log("TNEF attribute: Date sent ($length)");
        break;

    case TNEF_ADATEMODIFIED:
        tnef_log("TNEF attribute: Date modified ($length)");
        break;

    case TNEF_AIDOWNER:
        $value = tnef_geti32($value);
        tnef_log("TNEF attribute: ID Owner=$value");
        break;

    case TNEF_AMESSAGEID:
        $value = substr($value, 0, $length - 1);
        tnef_log("TNEF attribute: Message Id='$value'");
        break;

    case TNEF_APRIORITY:
        $value = tnef_geti16($value);
        tnef_log("TNEF attribute: Priority=$value");
        break;

    case TNEF_AMAPIPROPS:
        tnef_log("TNEF attribute: MAPI Props ($length)");
        break;

    case TNEF_AATTACHCREATEDATE:
        tnef_log("TNEF attribute: Date Attachment created ($length)");
        break;

    case TNEF_AATTACHMODDATE:
        tnef_log("TNEF attribute: Date Attachment modified ($length)");
        break;

    case TNEF_AFROM:
        tnef_log("TNEF attribute: From (Triples) ($length)");
        break;

    case TNEF_ARENDDATA:
        tnef_log("TNEF attribute: ARENDDATA ($length)");
        break;

    case TNEF_AMAPIATTRS:
        tnef_log("TNEF attribute: Mapi Attribs ($length)");
        break;

    case TNEF_AMAPIPROPS:
        tnef_log("TNEF attribute: Mapi Properties ($length)");
        break;

    case TNEF_AFILENAME:
        $value = substr($value, 0, $length - 1);
        tnef_log("TNEF attribute: Filename='$value'");
        break;

    case TNEF_ATTACHDATA:
        tnef_log("TNEF attribute: Attachment Data ($length)");
        break;

    case TNEF_ATTACHMETAFILE:
        tnef_log("TNEF attribute: Attach Metafile ($length)");
        break;

    default:
        tnef_log(sprintf("TNEF attribute: [%08x] %d bytes", $attribute, $length));
        break;
    }
}

function show_mapi_attribute($attr_type, $attr_name, $value, $length) {

    switch($attr_name) {
    case TNEF_MAPI_ATTACH_LONG_FILENAME: // used in preference to AFILENAME value
        $name = preg_replace('/.*[\/](.*)$/', '\1', $value); // strip path
        tnef_log("MAPI Filename = '$name'");
        break;

    case TNEF_MAPI_DISPLAY_NAME: // Just to test
        tnef_log("MAPI Displayname='$value'");
        break;

    case TNEF_MAPI_ATTACH_MIME_TAG: // Is this ever set, and what is the format?
        $type0 = preg_replace('/^(.*)/.*/', '\1', $value);
        $type1 = preg_replace('/.*/(.*)$/', '\1', $value);
        tnef_log("MAPI type0=$type0 type1=$type1");
        break;

    case TNEF_MAPI_ATTACH_DATA:
        tnef_log("MAPI Attachment. Length=$length");
        break;

    case TNEF_MAPI_NORMALIZED_SUBJECT:
        tnef_log("MAPI Normalized Subject=$value");
        break;

    case TNEF_MAPI_ATTACH_SIZE:
        tnef_log("MAPI Attach Size=$value");
        break;

    case TNEF_MAPI_ATTACH_NUM:
        tnef_log("MAPI Attach Num=$value");
        break;

    case TNEF_MAPI_RENDERING_POSITION:
        tnef_log("MAPI Rendering Position=$value");
        break;

    case TNEF_MAPI_ACCESS_LEVEL:
        tnef_log("MAPI Access Level=$value");
        break;

    case TNEF_MAPI_CREATION_TIME:
        tnef_log("MAPI Creation Time");
        break;

    case TNEF_MAPI_MODIFICATION_TIME:
        tnef_log("MAPI Modification Time");
        break;

    case TNEF_MAPI_ATTACH_METHOD:
        tnef_log("MAPI Attach Method=$value");
        break;

    case TNEF_MAPI_ATTACH_ENCODING:
        tnef_log("MAPI Attach Encoding");
        break;

    case TNEF_MAPI_ATTACH_EXTENSION:
        tnef_log("MAPI Attach Extension='$value'");
        break;

    case TNEF_MAPI_ORIGINAL_AUTHOR:
        tnef_log("MAPI Original Autor='$value'");
        break;

    case TNEF_MAPI_SENT_REP_NAME:
        tnef_log("MAPI Sent Rep Name='$value'");
        break;

    case TNEF_MAPI_SENT_REP_ADDRTYPE:
        tnef_log("MAPI Sent Rep Addrtype='$value'");
        break;

    case TNEF_MAPI_SENT_REP_EMAIL_ADDR:
        tnef_log("MAPI Sent Rep eMail Addr='$value'");
        break;

    case TNEF_MAPI_SUBJECT_PREFIX:
        tnef_log("MAPI Subject Prefix='$value'");
        break;

    case TNEF_MAPI_CONVERSATION_TOPIC:
        tnef_log("MAPI Conversation Topic='$value'");
    break;

    case TNEF_MAPI_MAPPING_SIGNATURE:
        tnef_log("MAPI Mapping Signature");
        break;

    case TNEF_MAPI_RECORD_KEY:
        tnef_log("MAPI Record Key");
        break;

    case TNEF_MAPI_STORE_RECORD_KEY:
        tnef_log("MAPI Store Record Key");
        break;

    case TNEF_MAPI_STORE_ENTRY_ID:
        tnef_log("MAPI Store Entry ID");
        break;

    case TNEF_MAPI_OBJECT_TYPE:
        tnef_log("MAPI Object Type=$value");
        break;

    case TNEF_MAPI_RTF_SYNC_BODY_TAG:
        tnef_log("MAPI RTF SYNC Body Tag=$value");
        break;

    case TNEF_MAPI_RTF_COMPRESSED:
        tnef_log("MAPI RTF Compressed=($length)");
        break;

    case TNEF_MAPI_ACCOUNT:
        tnef_log("MAPI Account='$value'");
        break;

    case TNEF_MAPI_GENERATION:
        tnef_log("MAPI Generation='$value'");
        break;

    case TNEF_MAPI_SENDER_NAME:
        tnef_log("MAPI Sender Name='$value'");
        break;

    case TNEF_MAPI_SENDER_ADDRTYPE:
        tnef_log("MAPI Sender Addrtype='$value'");
        break;

    case TNEF_MAPI_SENDER_EMAIL_ADDRESS:
        tnef_log("MAPI Sender eMail Address='$value'");
        break;

    case TNEF_MAPI_GIVEN_NAME:
        tnef_log("MAPI Given Name='$value'");
        break;

    case TNEF_MAPI_INITIALS:
        tnef_log("MAPI Initials='$value'");
        break;

    case TNEF_MAPI_KEYWORDS:
        tnef_log("MAPI Keywords='$value'");
        break;

    case TNEF_MAPI_LANGUAGE:
        tnef_log("MAPI Language='$value'");
        break;

    case TNEF_MAPI_LOCATION:
        tnef_log("MAPI Location='$value'");
        break;

    case TNEF_MAPI_SURNAME:
        tnef_log("MAPI Surname='$value'");
        break;

    case TNEF_MAPI_COMPANY_NAME:
        tnef_log("MAPI Company Name='$value'");
        break;

    case TNEF_MAPI_TITLE:
        tnef_log("MAPI Title='$value'");
        break;

    case TNEF_MAPI_DEPARTMENT_NAME:
        tnef_log("MAPI Department Name='$value'");
        break;

    case TNEF_MAPI_OFFICE_LOCATION:
        tnef_log("MAPI Office Location='$value'");
        break;

    case TNEF_MAPI_COUNTRY:
        tnef_log("MAPI Country='$value'");
        break;

    case TNEF_MAPI_LOCALTY:
        tnef_log("MAPI Localty='$value'");
        break;

    case TNEF_MAPI_STATE_OR_PROVINCE:
        tnef_log("MAPI State or Province='$value'");
        break;

    case TNEF_MAPI_MIDDLE_NAME:
        tnef_log("MAPI Middle Name='$value'");
        break;

    case TNEF_MAPI_DISPLAYNAME_PREFIX:
        tnef_log("MAPI Display Name Prefix='$value'");
        break;

    case TNEF_MAPI_BUSINESS_TEL_NUMBER:
        tnef_log("MAPI Business Telefone='$value'");
        break;

    case TNEF_MAPI_BUSINESS2_TEL_NUMBER:
        tnef_log("MAPI Business 2 Telefone='$value'");
        break;

    case TNEF_MAPI_HOME_TEL_NUMBER:
        tnef_log("MAPI Home Telefone='$value'");
        break;

    case TNEF_MAPI_PRIMARY_TEL_NUMBER:
        tnef_log("MAPI Primary Telefone='$value'");
        break;

    case TNEF_MAPI_POSTAL_ADDRESS:
        tnef_log("MAPI Postal Address='$value'");
        break;

    case TNEF_MAPI_MOBILE_TEL_NUMBER:
        tnef_log("MAPI Mobile Telefone='$value'");
        break;

    case TNEF_MAPI_RADIO_TEL_NUMBER:
        tnef_log("MAPI Radio Telefone='$value'");
        break;

    case TNEF_MAPI_CAR_TEL_NUMBER:
        tnef_log("MAPI Car Telefone='$value'");
        break;

    case TNEF_MAPI_OTHER_TEL_NUMBER:
        tnef_log("MAPI Other Telefone='$value'");
        break;

    case TNEF_MAPI_PAGER_TEL_NUMBER:
        tnef_log("MAPI Pager Telefone='$value'");
        break;

    case TNEF_MAPI_PRIMARY_FAX_NUMBER:
        tnef_log("MAPI Primary Fax='$value'");
        break;

    case TNEF_MAPI_BUSINESS_FAX_NUMBER:
        tnef_log("MAPI Business Fax='$value'");
        break;

    case TNEF_MAPI_TELEX_NUMBER:
        tnef_log("MAPI Telex Number='$value'");
        break;

    case TNEF_MAPI_ISDN_NUMBER:
        tnef_log("MAPI ISDN Number='$value'");
        break;

    case TNEF_MAPI_HOME_FAX_NUMBER:
        tnef_log("MAPI Home Fax='$value'");
        break;

    case TNEF_MAPI_ASSISTANT_TEL_NUMBER:
        tnef_log("MAPI Assistant Telefone='$value'");
        break;

    case TNEF_MAPI_HOME2_TEL_NUMBER:
        tnef_log("MAPI Home Telefone='$value'");
        break;

    case TNEF_MAPI_STREET_ADDRESS:
        tnef_log("MAPI Street Address='$value'");
        break;

    case TNEF_MAPI_POSTAL_CODE:
        tnef_log("MAPI Postal Code='$value'");
        break;

    case TNEF_MAPI_POST_OFFICE_BOX:
        tnef_log("MAPI Post Office Box='$value'");
        break;

    case TNEF_MAPI_NICKNAME:
        tnef_log("MAPI Nickname='$value'");
        break;

    case TNEF_MAPI_PERSONAL_HOME_PAGE:
        tnef_log("MAPI Personal Home Page='$value'");
        break;

    case TNEF_MAPI_BUSINESS_HOME_PAGE:
        tnef_log("MAPI Business Home Page='$value'");
        break;

    default:
        if ($attr_type == TNEF_MAPI_STRING) {
            tnef_log(sprintf("mapi attribute %04x:%04x value='%s'", $attr_type, $attr_name, $value));
        } else {
            tnef_log(sprintf("mapi attribute %04x:%04x", $attr_type, $attr_name));
        }
        break;
    }
}
