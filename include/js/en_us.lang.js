/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/
var alert_arr = {
	DELETE:'Are you sure you want to delete the selected ',
	RECORDS:' records?',
	SELECT:'Please select at least one entity',
	DELETE_RECORD: 'Are you sure you want to delete the selected record?',
	DELETE_RECORDS: 'Are you sure you want to delete the %s selected records?',
	DELETE_ACCOUNT:'Deleting this account will remove its related potentials and quotes. Are you sure you want to delete it?',
	DELETE_ACCOUNTS:'Deleting these accounts will remove its related potentials and quotes. Are you sure you want to delete them?',
	DELETE_VENDOR:'Deleting this vendor will remove its related purchase orders. Are you sure you want to delete it?',
	DELETE_VENDORS:'Deleting these vendors will remove its related purchase orders. Are you sure you want to delete them?',
	SELECT_MAILID:'Please Select a mailid',
	OVERWRITE_EXISTING_ACCOUNT2:') address details?\nIf you click Cancel the item is still linked keeping independent addresses.',
	MISSING_FIELDS:'Missing required fields:',
	NOT_ALLOWED_TO_EDIT:'you are not allowed to edit this field',
	NOT_ALLOWED_TO_EDIT_FIELDS:'you are not allowed to edit the field(s)',
	COLUMNS_CANNOT_BE_EMPTY:'Selected Columns cannot be empty',
	CANNOT_BE_EMPTY:'%s cannot be empty',
	CANNOT_BE_NONE:'%s cannot be none',
	ENTER_VALID:'Please enter a valid ',
	SHOULDBE_LESS:' should be less than ',
	SHOULDBE_LESS_EQUAL:' should be less than or equal to ',
	SHOULDBE_EQUAL:' should be equal to ',
	SHOULDBE_GREATER:' should be greater than ',
	SHOULDBE_GREATER_EQUAL:' should be greater than or equal to ',
	SHOULDNOTBE_EQUAL:' should not be equal to ',
	SHOULDBE_LESS_1: '%s should be less than %d',
	SHOULDBE_LESS_EQUAL_1: '%s should be less than or equal to %d',
	SHOULDBE_EQUAL_1: '%s should be equal to %d',
	SHOULDBE_GREATER_1: '%s should be greater than %d',
	SHOULDBE_GREATER_EQUAL_1: '%s should be greater than or equal to %d',
	SHOULDNOTBE_EQUAL_1: '%s should not be equal to %d',
	DATE_SHOULDBE_LESS: '%s should be less than %s',
	DATE_SHOULDBE_LESS_EQUAL: '%s should be less than or equal to %s',
	DATE_SHOULDBE_EQUAL: '%s should be equal to %s',
	DATE_SHOULDBE_GREATER: '%s should be greater than %s',
	DATE_SHOULDBE_GREATER_EQUAL: '%s should be greater than or equal to %s',
	DATE_SHOULDNOTBE_EQUAL: '%s should not be equal to %s',
	LENGTH_SHOULDBE_LESS: '%s %s should be less than %d %s',
	LENGTH_SHOULDBE_LESS_EQUAL: '%s %s should be less than or equal to %d %s',
	LENGTH_SHOULDBE_EQUAL: '%s %s should be equal to %d %s',
	LENGTH_SHOULDBE_GREATER: '%s %s should be greater than %d %s',
	LENGTH_SHOULDBE_GREATER_EQUAL: '%s %s should be greater than or equal to %d %s',
	LENGTH_SHOULDNOTBE_EQUAL: '%s %s should not be equal to %d %s',
	INVALID:'Invalid ',
	EXCEEDS_MAX:' exceeds the maximum limit ',
	OUT_OF_RANGE:' is out of range',
	PORTAL_PROVIDE_EMAILID:'Portal user should provide email Id for portal login',
	ADD_CONFIRMATION:'Are you sure you want to add the selected ',
	ACCOUNTNAME_CANNOT_EMPTY:'AccountName Cannot be Empty',
	CANT_SELECT_CONTACTS:"You can\'t select related contacts from Lead",
	LBL_THIS:'This ',
	DOESNOT_HAVE_MAILIDS:" doesn\'t have any mail ids",
	ARE_YOU_SURE:'Are You Sure You want to Delete?',
	DOESNOT_HAVE_AN_MAILID:'" '+"doesn\'t have an Email Id",
	MISSING_REQUIRED_FIELDS:'Missing required fields: ',
	READONLY:"it\'s readonly",
	SELECT_ATLEAST_ONE_USER:'Please select at least one user',
	DISABLE_SHARING_CONFIRMATION:'Are you sure you want to disable sharing for selected ',
	USERS:' user(s) ?',
	ENDTIME_GREATER_THAN_STARTTIME:'End Time should be greater than Start Time ',
	FOLLOWUPTIME_GREATER_THAN_STARTTIME:'Followup Time should be greater than End Time ',
	MISSING_EVENT_NAME:'Missing Event Name',
	EVENT_TYPE_NOT_SELECTED:'Event Type is not selected',
	CLOSEDATE_CANNOT_BE_EMPTY:'Closing Date cannot be Empty',
	SITEURL_CANNOT_BE_EMPTY:'Site Url cannot be empty',
	SITENAME_CANNOT_BE_EMPTY:'Site Name cannot be empty',
	LISTPRICE_CANNOT_BE_EMPTY:'List Price cannot be empty',
	INVALID_LIST_PRICE:'Invalid List Price',
	PROBLEM_ACCESSSING_URL:'Problem accessing url: ',
	CODE:' Code: ',
	WISH_TO_QUALIFY_MAIL_AS_CONTACT:'Are you sure you wish to Qualify this Mail as Contact?',
	SELECT_ATLEAST_ONEMSG_TO_DEL:'Please select at least one message to delete',
	ERROR:'Error',
	FIELD_TYPE_NOT_SELECTED:'Field Type is not selected',
	SPECIAL_CHARACTERS_NOT_ALLOWED:'Special characters are not allowed in Label field',
	SPECIAL_CHARACTERS:'Special characters',
	NOT_ALLOWED:'are not allowed. Please try with some other values',
	PICKLIST_CANNOT_BE_EMPTY:'Picklist value cannot be empty',
	DUPLICATE_VALUES_FOUND:'Duplicate Values found',
	DUPLICATE_MAPPING_ACCOUNTS:'Duplicate mapping for accounts!!',
	DUPLICATE_MAPPING_CONTACTS:'Duplicate mapping for Contacts!!',
	DUPLICATE_MAPPING_POTENTIAL:'Duplicate mapping for Potential!!',
	ERROR_WHILE_EDITING:'Error while Editing',
	CURRENCY_CHANGE_INFO:'Currency Changes has been made Successfully',
	CURRENCY_CONVERSION_INFO:'Are you using Dollar $ as Currency?\nClick OK to remain as $, Cancel to change the currency conversion rate.',
	THE_EMAILID: "The email id \'",
	EMAIL_FIELD_INVALID:"\' in the email field is invalid",
	MISSING_REPORT_NAME:'Missing Report Name',
	REPORT_NAME_EXISTS:'Report name already exists, try again...',
	WANT_TO_CHANGE_CONTACT_ADDR:'Do you want to change the addresses of the Contacts related to this Account?',
	SURE_TO_DELETE:'Are you sure you want to delete ?',
	NO_PRODUCT_SELECTED:'No product is selected. Select at least one Product',
	VALID_FINAL_PERCENT:'Enter valid Final Discount Percentage',
	VALID_FINAL_AMOUNT:'Enter valid Final Discount Amount',
	VALID_SHIPPING_CHARGE:'Enter a valid Shipping & Handling charge',
	VALID_ADJUSTMENT:'Enter a valid Adjustment',
	WANT_TO_CONTINUE:'Do you want to Continue?',
	ENTER_VALID_TAX:'Please enter Valid TAX value',
	VALID_TAX_NAME:'Enter valid Tax Name',
	CORRECT_TAX_VALUE:'Enter Correct Tax Value',
	ENTER_POSITIVE_VALUE:'Please enter positive value',
	LABEL_SHOULDNOT_EMPTY:'The tax label name should not be empty',
	NOT_VALID_ENTRY:'is not a valid entry. Please enter correct value',
	VALID_DISCOUNT_PERCENT:'Enter a valid Discount percentage',
	VALID_DISCOUNT_AMOUNT:'Enter a valid Discount Amount',
	SELECT_TEMPLATE_TO_MERGE:'Please select a template to merge',
	SELECTED_MORE_THAN_ONCE:'You have selected the following product(s) more than once.',
	YES:'yes',
	NO:'no',
	MAIL:'mail',
	EQUALS:'equals',
	NOT_EQUALS_TO:'not equal to',
	STARTS_WITH:'starts with',
	CONTAINS:'contains',
	DOES_NOT_CONTAINS:'does not contains',
	LESS_THAN:'less than',
	GREATER_THAN:'greater than',
	LESS_OR_EQUALS:'less or equal',
	GREATER_OR_EQUALS:'greater or equal',
	NO_SPECIAL_CHARS:'Special Characters are not allowed in Invoice String',
	PLS_SELECT_VALID_FILE:'Please select a file with the following extension:\n',
	NO_SPECIAL:'Special Characters are not allowed',
	IN_PROFILENAME:' in Profile Name',
	IN_GROUPNAME:' in Group Name',
	IN_ROLENAME:' in Role Name',
	VALID_TAX_PERCENT:'Enter a valid Tax percentage',
	VALID_SH_TAX:'Enter valid Taxes for shipping and handling ',
	ROLE_DRAG_ERR_MSG:'You cannot move a Parent Node under a Child Node',
	LBL_DEL:'del',
	VALID_DATA:' Enter Valid Data ,Please try again... ',
	STDFILTER:'Standard Filters',
	STARTDATE:'Start Date',
	ENDDATE:'End Date',
	START_DATE_TIME:'Start Date & Time',
	START_TIME:'Start Time',
	DATE_SHOULDNOT_PAST:'Current date & time for Activities with status as Planned',
	TIME_SHOULDNOT_PAST:'Current Time for Activities with status as Planned',
	LBL_AND:'And',
	LBL_ENTER_VALID_PORT:'Please enter valid port number',
	IN_USERNAME:' in Username ',
	LBL_ENTER_VALID_NO:'Please enter valid number',
	LBL_PROVIDE_YES_NO:' Invalid value.\nPlease Provide Yes or No',
	LBL_SELECT_CRITERIA:' Invalid criteria.\nPlease select criteria',
	OPPORTUNITYNAME_CANNOT_BE_EMPTY:'Potential Name field cannot be empty',
	OVERWRITE_EXISTING_ACCOUNT1:'Do you want to Overwrite the existing address with this selected account(',
	NAME_DESC:' for Folder Name & Description',
	LBL_NONE:'None',
	ENDS_WITH:'ends with',
	SHARED_EVENT_DEL_MSG:'The User does not have permission to Edit/Delete Shared Event.',
	LBL_WRONG_IMAGE_TYPE:'Allowed file types for Contacts - jpeg, png, jpg, pjpeg, x-png or gif',
	SELECT_MAIL_MOVE:'Please select a mail and then move..',
	LBL_NOTSEARCH_WITHSEARCH_ALL:'You haven\'t used the search. All the records will be Exported from ',
	LBL_NOTSEARCH_WITHSEARCH_CURRENTPAGE:'You haven\'t searched any thing. But you selected with search & current page options. So the records in the current page will be Exported from ',
	LBL_NO_DATA_SELECTED:'There is no record selected. Select at least one record to Export',
	LBL_SEARCH_WITHOUTSEARCH_ALL:'You have used search option but you have not selected without search & all options.\nYou can click [ok] to export all data or You can click [cancel] and try again with other export criteria',
	STOCK_IS_NOT_ENOUGH:'Stock is not enough',
	INVALID_QTY:'Invalid Qty',
	LBL_SEARCH_WITHOUTSEARCH_CURRENTPAGE:'You have used search option but you have not selected without search & currentpage options.\nYou can click [ok] to export current page data or You can click [cancel] and try again with some other export criteria.',
	LBL_SELECT_COLUMN:' Invalid column.\nPlease select column',
	LBL_NOT_ACCESSIBLE:'Not Accessible',
	LBL_FILENAME_LENGTH_EXCEED_ERR:'Filename cannot exceed 255 characters',
	LBL_DONT_HAVE_EMAIL_PERMISSION:'You don\'t have permission for Email field so you can\'t choose the email id',
	LBL_NO_FEEDS_SELECTED:'No Feeds Selected',
	LBL_SELECT_PICKLIST:'Please select at least one value to delete',
	LBL_CANT_REMOVE:'You can\'t remove all the values',
	LBL_CHECKIN_OVERWRITE:'This record has been since your last opening. Do you still want to save?',
	POTENTIAL_AMOUNT_CANNOT_BE_EMPTY:'Potential amount cannot be empty',
	LBL_CHECKIN_RELOAD:'The page will reload with updated data.',
	LBL_ALERT_EXT_CODE:'There is an account with the same external code, do you want to merge these clients?',
	LBL_ALERT_EXT_CODE_NOTFOUND:'No accounts with that external code found. Operation aborted',
	LBL_ALERT_EXT_CODE_COMMIT:'Accounts merged succesfully, You will be redirected to the page of the merged account',
	LBL_ALERT_EXT_CODE_FAIL:'Operation failed',
	LBL_ALERT_EXT_CODE_DUPLICATE:'Merge already done with that code or the code is used also by a deleted account, operation aborted. Empty the Recycle Bin and try again.',
	LBL_ALERT_EXT_CODE_SAVE:'Do you want to save changes anyway?',
	LBL_ALERT_EXT_CODE_NOTFOUND_SAVE:'No accounts with that code were found.Do you want to save external code anyway?',
	LBL_ALERT_EXT_CODE_NOTFOUND_SAVE2:'No accounts with that code were found.Do you want to save anyway?',
	LBL_ALERT_EXT_CODE_NO_PERMISSION:'There is already an account with the same code assigned to other users. So you can\'t merge it.',
	DOESNOT_HAVE_AN_FAXID:'" '+"doesn\'t have a Fax Id",
	LBL_DONT_HAVE_FAX_PERMISSION:"You don't have permission for Fax field so you can't choose the fax id",
	DOESNOT_HAVE_AN_SMSID:'" '+"doesn\'t have a Sms Id",
	LBL_DONT_HAVE_SMS_PERMISSION:"You don't have permission for Mobile field so you can't choose the sms id",
	NO_RULES_FOUND:'No rules found for this module, You will redirected to the rule creation form',
	SAME_GROUPS:'You have to select the records in the same groups for merging',
	ATLEAST_TWO:'Select at least two records for merging',
	MAX_THREE:'You are allowed to select a maximum of three records',
	MAX_RECORDS:'You are allowed to select a maximum of four records',
	CON_MANDATORY:'Select the mandatory field Last Name',
	LE_MANDATORY:'Select the mandatory fields Last Name and Company',
	ACC_MANDATORY:'Select the mandatory field Account Name',
	PRO_MANDATORY:'Select the mandatory field Product Name',
	TIC_MANDATORY:'Select the mandatory field Ticket Title',
	POTEN_MANDATORY:'Select the mandatory field Potential Name',
	VEN_MANDATORY:'Select the mandatory field Vendor Name',
	DEL_MANDATORY:'You are not allowed to delete the mandatory field',
	LBL_HIDEHIERARCH:'Hide hierarchy',
	LBL_SHOWHIERARCH:'Show hierarchy',
	LBL_NO_ROLES_SELECTED:'No roles have been selected, do you wish to continue?',
	LBL_DUPLICATE_FOUND:'Duplicate entries found for the value ',
	LBL_CANNOT_HAVE_EMPTY_VALUE:'Cannot replace with blank value, to remove the value use Delete option.',
	LBL_DUPLICATE_VALUE_EXISTS:'Duplicate value exists',
	LBL_WANT_TO_DELETE:'This will delete the selected picklist value(s) for all roles. You sure you want to continue? ',
	LBL_DELETE_ALL_WARNING:'Must have at least one value for the picklist',
	LBL_PLEASE_CHANGE_REPLACEMENT:'please change the replacement value; it is also selected for delete',
	LBL_BLANK_REPLACEMENT:'Cannot select blank value for replacement',
	LBL_PLEASE_SELECT:'--Please select--',
	MUST_BE_CHECKED:'Must be checked',
	CHARACTER:'characters',
	LENGTH:'length of',
	MSG_CHANGE_CURRENCY_REVISE_UNIT_PRICE:'Unit price of all the Currencies will be revised based on the selected Currency. Are you sure?',
	Select_one_record_as_parent_record:'Select one record as parent record',
	RECURRING_FREQUENCY_NOT_PROVIDED:'Recurring frequency not provided',
	RECURRING_FREQNECY_NOT_ENABLED:'Recurring frequency is provided, but recurring is not enabled',
	NO_SPECIAL_CHARS_DOCS:'Special characters like quotes, backslash, + symbol, % and ? are not allowed',
	FOLDER_NAME_TOO_LONG:'Folder name is too long. Try again!',
	FOLDERNAME_EMPTY:'The Folder name cannot be empty',
	DUPLICATE_FOLDER_NAME:'Trying to duplicate an existing folder name. Please try again !',
	FOLDER_DESCRIPTION_TOO_LONG:'Folder description is too long. Try again!',
	NOT_PERMITTED:'You are not permitted to execute this operation.',
	ALL_FILTER_CREATION_DENIED:'Cannot create CustomView using name "All", try using different ViewName',
	OPERATION_DENIED:'You are denied to perform this operation',
	EMAIL_CHECK_MSG:'Disable portal access to save the email field as blank',
	IS_PARENT:'This Product has Sub Products, You are not allowed to choose a Parent for this Product',
	BLOCK_NAME_CANNOT_BE_BLANK:'Block name can not be blank',
	ARE_YOU_SURE_YOU_WANT_TO_DELETE:'Are you sure you want to delete ?',
	PLEASE_MOVE_THE_FIELDS_TO_ANOTHER_BLOCK:'Please move the fields to another block',
	ARE_YOU_SURE_YOU_WANT_TO_DELETE_BLOCK:'Are you sure you want to delete block?',
	LABEL_CANNOT_NOT_EMPTY:'Label cannot be Emtpy',
	LBL_TYPEALERT_1:'Sorry, you cannot map the',
	LBL_WITH:'with',
	LBL_TYPEALERT_2:'data type. Kindly map the same data types.',
	LBL_LENGTHALERT:'Sorry, you can cannot map fields with different character size. Kindly map the data with same or more character size.',
	LBL_DECIMALALERT:'Sorry, you can cannot map fields with different decimal places. Kindly map the data with same or more decimal places.',
	FIELD_IS_MANDATORY:'Mandatory Field',
	FIELD_IS_ACTIVE:'Field is available for use',
	FIELD_IN_QCREATE:'Present in Quick Create',
	FIELD_IS_MASSEDITABLE:'Available for Mass Edit',
	IS_MANDATORY_FIELD:'is Mandatory Field',
	AMOUNT_CANNOT_BE_EMPTY:'Amount cannot be Empty',
	LABEL_ALREADY_EXISTS:'Label already exists. Please specify a different Label',
	LENGTH_OUT_OF_RANGE:'Length of the Block should be less than 50 characters',
	LBL_SELECT_ONE_FILE:'Please select at least one File',
	LBL_UNABLE_TO_ADD_FOLDER:'Unable to add Folder. Please try again.',
	LBL_ARE_YOU_SURE_YOU_WANT_TO_DELETE_FOLDER:'Are you sure you want to delete the folder?',
	LBL_ERROR_WHILE_DELETING_FOLDER:'Error while deleting the folder.Please try again later.',
	LBL_FILE_CAN_BE_DOWNLOAD:'File is available for download',
	LBL_DOCUMENT_LOST_INTEGRITY:'This Documents is not available. It will be marked as Inactive',
	LBL_DOCUMENT_NOT_AVAILABLE:'This Document is not available for Download',
	LBL_FOLDER_SHOULD_BE_EMPTY:'Folder should be empty to remove it!',
	LBL_PLEASE_SELECT_FILE_TO_UPLOAD:'Please select the file to upload.',
	LBL_ARE_YOU_SURE_TO_MOVE_TO:'Are you sure you want to move the file(s) to ',
	LBL_FOLDER:' folder',
	LBL_UNABLE_TO_UPDATE:'Unable to update! Please try it again.',
	LBL_IMAGE_DELETED:'Image Deleted',
	ERR_FIELD_SELECTION:'Some error in field selection',
	NO_LINE_ITEM_SELECTED:'No line item is selected. Please select at least one line item.',
	LINE_ITEM:'Product',
	LIST_PRICE:'List Price',
	LBL_WIDGET_HIDDEN:'Widget Hidden',
	LBL_RESTORE_FROM_PREFERENCES:'You can restore it from your preferences',
	ERR_HIDING:'Error while hiding',
	MSG_TRY_AGAIN:'Please try again',
	MSG_ENABLE_SINGLEPANE_VIEW:'Singlepane View Enabled',
	MSG_DISABLE_SINGLEPANE_VIEW:'Singlepane View Disabled',
	MSG_FTP_BACKUP_DISABLED:'FTP Backup Disabled',
	MSG_LOCAL_BACKUP_DISABLED:'Local Backup Disabled',
	MSG_FTP_BACKUP_ENABLED:'FTP Backup Enabled',
	MSG_LOCAL_BACKUP_ENABLED:'Local Backup Enabled',
	MSG_CONFIRM_PATH:'confirm with the Path details',
	MSG_CONFIRM_FTP_DETAILS:'confirm with the FTP details',
	START_PERIOD_END_PERIOD_CANNOT_BE_EMPTY:'Start period or End period cannot be empty',
	LBL_ADD:'Add ',
	Module:'Module',
	DashBoard:'DashBoard',
	RSS:'RSS',
	Default:'Default',
	SPECIAL_CHARS:'\\ / < > + \' " ',
	no_valid_extension:'Not valid file extension.Allowed extensions are pdf,ps and tiff',
	BETWEEN:'between',
	BEFORE:'before',
	AFTER:'after',
	ERROR_DELETING_TRY_AGAIN:'Error while deleting.Please try again.',
	LBL_ENTER_WINDOW_TITLE:'Please enter Window Title.',
	LBL_SELECT_ONLY_FIELDS:'Please select only two fields.',
	LBL_ENTER_RSS_URL:'Please enter RSS URL',
	LBL_ENTER_URL:'Please enter URL',
	LBL_DELETED_SUCCESSFULLY:'Widget deleted sucessfully.',
	LBL_ADD_HOME_WIDGET:'Unable to add homestuff! Please try again',
	LBL_STATUS_CHANGING:'Change state in ',
	LBL_STATUS_CHANGING_MOTIVATION:' note :',
	LBL_STATUS_PLEASE_SELECT_A_MODULE:'Choose a Module',
	LBL_STATUS_PLEASE_SELECT_A_ROLE:'Choose a Role',
	OVERWRITE_EXISTING_CONTACT1:'Do you want to Overwrite the existing address with this selected contact(',
	OVERWRITE_EXISTING_CONTACT2:') address details?\nIf you click Cancel the item is still linked keeping independent addresses.',
	SELECT_SMSID:'Please Select a mailid',
	NOTVALID_SMSID:'Sms number not valid',
	NULL_SMSID:'No Sms number defined',
	LBL_MASS_EDIT_WITHOUT_WF_1:'You have selected more than ',
	LBL_MASS_EDIT_WITHOUT_WF_2:' items, this may overload the server. Proceed to update excluding the Workflow?',
	EXISTING_RECORD:'Record already exists width these dates: ',
	EXISTING_SAVE:'Do you want to save anymore?',
	EXISTING_SAVE_CONVERTLEAD:'If you click to OK the contact and the potential will be linked to the existing account.',
	LBL_MANDATORY_FIELDS_WF:'Please enter value for mandatory fields',
	LBL_DELETE_MSG:'Are you sure, you want to delete the webform?',
	LBL_DUPLICATE_NAME:'Webform already exists',
	ERR_SELECT_EITHER:'Select either Organization or Contact to convert the lead',
	ERR_SELECT_ACCOUNT:'Select Organization to proceed',
	ERR_SELECT_CONTACT:'Select Contact to proceed',
	ERR_MANDATORY_FIELD_VALUE:'Values for Mandatory Fields are missing',
	ERR_POTENTIAL_AMOUNT:'Potential Amount must be a number',
	ERR_EMAILID:'Enter valid Email Id',
	ERR_TRANSFER_TO_ACC:'Organization should be selected to transfer related records',
	ERR_TRANSFER_TO_CON:'Contact should be selected to transfer related records ',
	SURE_TO_DELETE_CUSTOM_MAP:'Are you sure you want to delete the Field Mapping?',
	LBL_CLOSE_DATE:'Close Date',
	LBL_EMAIL:'Email',
	MORE_THAN_500:'You selected more than 500 records. For this action it may take longer time. Are you sure want to proceed?',
	LBL_MAPPEDALERT:'The field has been already mapped',
	LBL_SELECT_DEL_FOLDER:'Select at least one folder',
	LBL_NO_EMPTY_FOLDERS:'There are no empty folders',
	LBL_OR:'or',
	LBL_SAVING_DRAFT:'Saving draft',
	ERR_SELECT_ATLEAST_ONE_MERGE_CRITERIA_FIELD:'Select at least one field for merge criteria',
	ERR_FIELDS_MAPPED_MORE_THAN_ONCE:'Following field is mapped more than once. Please check the mapping.',
	ERR_PLEASE_MAP_MANDATORY_FIELDS:'Please map the following mandatory fields',
	ERR_MAP_NAME_CANNOT_BE_EMPTY:'Map name cannot be empty',
	ERR_MAP_NAME_ALREADY_EXISTS:'Map name already exists. Please give a different name',
	LBL_UT208_PASSWORDEMPTY:'Type a password',
	LBL_UT208_INVALIDSRV:'Invalid server answer',
	LBL_UT208_WRONGPWD:'Wrong password',
	LBL_UT208_DIFFPWD:'Passwords are not equal',
	LBL_UT208_PWDCRITERIA:'Password must be at least 6 characters long',
	LBL_CHECK_BOUNCED_MESSAGES:'Check bounced messages',
	LBL_MAX_REPORT_SECMODS:'You reached the maximum number of related modules',
	LBL_FILTER:'Filter',
	LBL_TEMPLATE_MUST_HAVE_NAME:'You have to give a name to the template',
	LBL_MUST_TYPE_SUBJECT:'You have to type a subject',
	LBL_SELECT_RECIPIENTS:'Select at least one recipient',
	LBL_SELECT_TEMPLATE:'Select a template',
	LBL_FILL_FIELDS:'Fill the following fields',
	LBL_SEND_TEST_EMAIL:'You have to send the test email first',
	LBL_INVALID_EMAIL:'Invalid email address',
	LBL_TEST_EMAIL_SENT:'Test Email sent correctly',
	LBL_ERROR_SENDING_TEST_EMAIL:'Error while sending test email',
	LBL_ERROR_SAVING:'Error while saving',
	LBL_NEWSLETTER_SCHEDULED:'Newsletter created and scheduled for the specified time',
	SEND_MAIL_ERROR: 'Error: sending email failed',
	LBL_SAVE_LAST_CHANGES: 'Do you want to save the last changes?\nClick OK to save or Cancel to dismiss.',
	LBL_FPOFV_RULE_NAME:'Rule name',
	LBL_LEAST_ONE_CONDITION:'Insert at least one condition',
	LBL_LEAST_ONE_FIELD:'Insert at least one field', // crmv@190416
	LBL_FPOFV_RULE_NAME_DUPLICATED:'Rule name duplicated',
	//crmv@48693
	LBL_ADVSEARCH_STARTDATE: 'from',
	LBL_ADVSEARCH_ENDDATE: 'to',
	LBL_ADVSEARCH_DATE_CUSTOM: 'custom',
	LBL_ADVSEARCH_DATE_YESTARDAY: 'yesterday',
	LBL_ADVSEARCH_DATE_TODAY: 'today',
	LBL_ADVSEARCH_DATE_LASTWEEK: 'lastweek',
	LBL_ADVSEARCH_DATE_THISWEEK: 'thisweek',
	LBL_ADVSEARCH_DATE_LASTMONTH: 'lastmonth',
	LBL_ADVSEARCH_DATE_THISMONTH: 'thismonth',
	LBL_ADVSEARCH_DATE_LAST60DAYS: 'last60days',
	LBL_ADVSEARCH_DATE_LAST90DAYS: 'last90days',
	//crmv@48693e
	LBL_TEMPLATE_MUST_HAVE_UNSUBSCRIPTION_LINK: 'Missing link for unsubscribing. Proceed anyway?',
	LBL_TEMPLATE_MUST_HAVE_PREVIEW_LINK: 'Missing link for the preview. Proceed anyway?',
	//crmv@56962
	HAS_CHANGED: 'has changed to',
	ENDS_WITH: 'ends with',
	//crmv@56962e
	//crmv@68357
	ANSWER_SENT: 'The answer has been sent',
	CONFIRM_LINKED_EVENT_DELETION: 'Do you also want to delete the linked event?',
	//crmv@68357e
	//crmv@64542
	LBL_TOO_LONG: '%s is too long',
	LBL_NAME: 'Name',
	LBL_NAME_S: '%s name',
	LBL_FILL_ALL_FIELDS: 'Please fill all the required fields',
	LBL_FILTER_FIELD_MORE_THAN_ONCE: 'You selected the same field more than once. The fields must be all different',
	LBL_SELECT_AT_LEAST_ONE_FIELD: 'Please select at least one field',
	LBL_MMAKER_CONFIRM_RESET: 'Are you sure to restore the files to their original state? All modifications will be lost.',
	LBL_WANT_TO_SAVE_PENDING_CHANGES: 'Do you want to save the pending modifications?',
	LBL_SURE_TO_UNINSTALL_MODULE: 'Uninstalling the module will remove all of its records. Do you want to proceed?',
	LBL_TOO_MANY_UITYPE4: 'There is more than one Auto Numbering field. It\'s possible to create only one of them per module',
	LBL_SAMEMODULERELATED: 'The module %s is present in more than one relation field. It\'s possible to have only one relation for each module',
	//crmv@64542e
	//crmv@65455
	LBL_PLEASE_SELECT_MODULE: 'Please select one module',
	LBL_PLEASE_SELECT_VALUE: 'Please select a value',
	LBL_FIELD_IS_NUMERIC: 'The field %s must be a number',
	LBL_FIELD_IS_INVALID: 'The field %s is not correct',
	LBL_CSVPATH_MUST_NOT_BE_ABSOLUTE: 'The CSV path must not be absolute',
	LBL_VALUE_TOO_SMALL: 'Value too small',
	LBL_VALUE_TOO_BIG: 'Value too big',
	LBL_INVALID_VALUE: 'Invalid value',
	LBL_CONTINUE_WITHOUT_KEY_FIELD: 'No key field has been selected. On every import run, the records will be added to the CRM. Proceed?',
	LBL_DATA_IMPORT_SCHEDULED_NOW: 'The import has been queued. It will start automatically in a few minutes',
	LBL_DATA_IMPORT_ABORTED: 'The import has been canceled. If the process has already started, it will be interrupted in a few minutes',
	LBL_SELECT_TABLE_OR_QUERY: 'If you want to use a custom query, please select "None" as a table',
	LBL_CANT_USE_DEFAULT_MAPPED_FIELD: 'You can\'t use a default field if it is already mapped for the import',
	//crmv@65455e
	CONFIRM_EMPTY_FOLDER: 'Are you sure you want to empty this folder? All messages will be deleted.',
	// crmv@83305
	LBL_FOLLOW: 'Notify me of changes',
	LBL_UNFOLLOW: 'Don\'t notify me of changes',
	// crmv@83305e
	LBL_MASS_EDIT_ENQUEUE: 'You selected more than {max_records} items. The process will continue in background and you\'ll be notified at the end.', // crmv@91571
	GROUPAGE_DUPLICATED: 'Groupage duplated for the field: %s',
	//crmv@92272
	LBL_NEW_CONDITION_BUTTON_LABEL: 'New Condition',
	LBL_REMOVE_GROUP_CONDITION: 'Delete group',
	LBL_PMH_SELECT_RELATED_TO: 'Select a related record to the Process Helper or disable it',
	LBL_PM_CHECK_ACTIVE: 'The process is not yet active. Do you want to activate it now?',
	LBL_PM_NO_ENTITY_SELECTED: 'No entity selected',
	LBL_PM_NO_CHECK_SELECTED: 'No check set',
	//crmv@92272e
	LBL_FILESIZE_EXCEEDS_MAX_UPLOAD_SIZE: 'Sorry, the uploaded file exceeds the maximum file allowed size.',
	LBL_GROUPBY: 'Group by',
	LBL_SUMMARY: 'Summary',
	MODULE_RELATED_TO: 'Module related to',
	LBL_SEARCH: 'Search',
	LBL_CHOOSE_A_REPORT: 'Choose a report',
	LBL_BACK: 'Back',
	MISSING_COMPARATOR: 'Please choose a comparison condition',
	//crmv@OPER6288
	LBL_LABEL: 'Label',
	LBL_KANBAN_DRAG_HERE: 'Enable drag here',
	//crmv@OPER6288e
	LBL_DISABLE_MODULE: 'Disable the module %s?',
	LBL_REPORT_NAME: 'Report name',
	LBL_DESCRIPTION: 'Description',
	//crmv@100495
	LBL_NO_RUN_PROCESSES: 'No process runs',
	LBL_RUN_PROCESSES_OK: 'Process executed successfully',
	LBL_RUN_PROCESSES_ERROR: 'Error occurred in the execution of the process',
	//crmv@100495e
	LBL_PM_ELEMENTS_ACTORS: 'Participants',
	//crmv@100731
	LBL_PM_SELECT_RESOURCE: 'Please select the user',
	LBL_PM_SELECT_ENTITY: 'Please select an entity',
	//crmv@100731e
	//crmv@101503
	ERR_TARGET: 'No target created. The list is empty.',
	ERR_TARGET_XLS: 'No data exported. The list is empty.',
	//crmv@101503e
	NO_ADDRESS_SELECTED: 'No address selected',
	LBL_PLEASE_ADD_COLUMNS: 'Please add at least one column',
	LBL_PLEASE_NAME_ALL_COLUMNS: 'Please give a name to all the columns',
	LBL_PLEASE_CHOOSE_FIELDNAME: 'Please give a name to the field',
	HAS_EXACTLY_ROWS: 'has exactly',
	HAS_LESS_ROWS: 'has less than',
	HAS_MORE_ROWS: 'has more than',
	LBL_ROWS: 'rows',
	LBL_AUTO_TMP_NAME: '[AUTO TEMPLATE]',
	LBL_TODAY: 'Today',
	LBL_CANCEL: 'Cancel',
	LBL_NO_NETWORK: 'No network connection available.',
	LBL_TABLEFIELD_SUM: 'Sum',
	LBL_TABLEFIELD_MIN: 'Min',
	LBL_TABLEFIELD_MAX: 'Max',
	LBL_TABLEFIELD_AVERAGE: 'Average',
	LBL_TABLEFIELD_LAST_VALUE: 'Last',
	LBL_TABLEFIELD_SEQUENCE: 'Sequence',
	LBL_TABLEFIELD_CURR_VALUE: 'Current',
	LBL_TABLEFIELD_ALL: 'All',
	LBL_TABLEFIELD_AT_LEAST_ONE: 'At least one',
	LBL_SELECT_OPTION_DOTDOTDOT: 'Select Option...',
	LBL_ADD_PICKLIST_VALUE: 'Please make at least one new entry',
	HelpDeskFromMail: 'VTE From Mail',
	LBL_CONFIRM_CLOSE_POPUP: 'Are you sure you want to close the popup?',
	LBL_NO_VALUES_TO_DELETE: 'No values to delete',
	LBL_ADDTODO: 'To Do',
	SUCCESS: 'Success',
	LBL_EXTWS_NO_RETURN_FIELDS: 'You have to configure at least one returned field. You can add them manually or use the Test Web service button to do it automatically',
	LBL_EXTWS_DUP_RETURN_FIELDS: 'Returned fields must have distinct names',
	LBL_EXTWS_EMPTY_RETURN_FIELD: 'Specify an expression for all the return fields',
	LBL_DONT_USE: 'Don\'t use',
	DELETE_CONTACT: 'Deleting this contact will remove its related potentials. Are you sure you want to delete it?',
	DELETE_CONTACTS: 'Deleting these contacts will remove its related potentials. Are you sure you want to delete them?',
	DB_ROW_LIMIT_REACHED: 'The database doesn\'t allow to add more fields. Contact VTECRM customer service to raise the limit.',
	call: 'Call',
	meeting: 'Meeting',
	tracked: 'Tracked',
	select_template: 'Please select the pdf template',
	LBL_IMAP_SERVER_NAME: 'Imap Server Name',
	LBL_SMTP_SERVER_NAME: 'Smtp Server Name',
	LBL_FIND_PORTAL_DUPLICATES: 'A portal user already exists with this email',
	LBL_ERROR_PORTAL_DUPLICATES: 'Some error in searching for duplicate portal users',
	ARE_YOU_SURE_INCREMENT_VERSION: 'Are you sure you want to save a new version?',
	LBL_OLD_VERSION: 'Freeze',
	LBL_NEW_VERSION: 'Use last',
	LBL_INCREMENT_VERSION_ERR_1: 'Some running processes were detected. Do you want to freeze the execution to version %1 or use the latest version of the diagram?',
	LBL_INCREMENT_VERSION_ERR_2: 'Some running processes were detected. Do you want to freeze the execution to version %1 or use the latest version of the diagram?<br>Furthermore, pending changes were detected in the following configurations:%2Choosing FREEZE will automatically save all these pending changes.',
	ARE_YOU_SURE_INCREMENT_VERSION_FOR_DOWNLOAD: 'Pending changes have been identified. The export will force the saving of version. Do you want to proceed anyway?',
	LBL_REQ_FAILED_NO_CONNECTION: 'No network connection available at the moment. Please retry',
	// crmv@160733
	LBL_TYPE_A_COMMENT: 'Please type a comment',
	LBL_CONFIDENTIAL_INFO_ALREADY_PROVIDED: 'The requested information have already been provided',
	LBL_OPERATION_NOT_SUPPORTED_EDITVIEW: 'This operation is not supported in EdiView mode',
	// crmv@160733e
	LBL_CONFIRM_REMOTE_WIPE: 'All donwloaded data from this user on associated devices will be deleted. Proceed?',
	LBL_REMOTE_WIPE_OK: 'Operation completed. At the next access via app the user will be disconnected.',
	// crmv@158392
	PDFMAKER_DELETE_CONFIRMATION: 'Are you sure you want to delete the selected templates?',
	SELECT_ATLEAST_ONE: 'Please select at least one entity',
	// crmv@158392e
	// crmv@167019
	LBL_SAVE: 'Save',
	LBL_CANCEL_ALL: 'Cancel all',
	LBL_REVISION_DROP_LIMIT: 'You can\'t insert more than one file for reviewing a document.',
	LBL_REVISION_CONFIRM: 'Are you sure you want to revise the document?',
	// crmv@167019e
	// crmv@171115
	confirm_exit_from_panel: 'Do you want to exit? The data entered will not be saved.',
	// crmv@171115e
	// crmv@171507
	LBL_SET_ADVRULE_NAME: "Please set the rule name",
	LBL_SET_ADVRULE_OPERATOR: "Please set the operator for the rule ",
	LBL_SET_ONE_ADVRULE: "Please set at least one rule",
	LBL_SET_ADVRULES_IN_ORDER: "Please enter the rules in order",
	// crmv@171507e
	// crmv@172355
	LBL_CHART_NO_DATA: 'No data available.',
	LBL_CHART_NO_SUMMARY: 'Report doesn\'t have summary.',
	LBL_REPORT_REMOVE_CHARTS_1: 'Report doesn\'t have summary anymore, but you have 1 chart still associated. Delete it?',
	LBL_REPORT_REMOVE_CHARTS_N: 'Report doesn\'t have summary anymore, but you have {n} charts still associated. Delete them?',
	// crmv@172355e
	LBL_CONFIRM_SHARE_PARENT_HELP_0: 'No visibility exception will be activated for linked information',
	LBL_CONFIRM_SHARE_PARENT_HELP_1: 'Information visibility will be extended but no changes will be allowed',
	LBL_CONFIRM_SHARE_PARENT_HELP_2: 'Information visibility will be extended and changes will be allowed',
	update_ignored: 'Update ignored',
	update_postponed: 'Update postponed',
	update_canceled: 'Update canceled',
	LBL_YOU_MUST_SELECT_USERS: 'You should select some users',
	LBL_YOU_MUST_TYPE_A_MESSAGE: 'You should write a message',
	LBL_VTESYNC_SELECT_TYPE: 'You have to select an external system',
	LBL_VTESYNC_SELECT_MODS: 'You have to select at least one module',
	LBL_VTESYNC_SELECT_AUTH: 'You have to select an authentication method',
	LBL_VTESYNC_FILL_OAUTH2: 'You have to fill all fields needed for authentication',
	LBL_VTESYNC_OAUTH2_AUTH: 'You have to authorize the provided credentials',
	// crmv@187621
	LBL_NOTIFICATION_BODY: 'View',
	LBL_NOTIFICATION_TITLE_S_ModComments: 'new talk',
	LBL_NOTIFICATION_TITLE_P_ModComments: 'new talks',
	LBL_NOTIFICATION_TITLE_S_Messages: 'new message',
	LBL_NOTIFICATION_TITLE_P_Messages: 'new messages',
	LBL_NOTIFICATION_TITLE_S_Processes: 'new process',
	LBL_NOTIFICATION_TITLE_P_Processes: 'new processes',
	// crmv@187621e
	COLUMNS_CANNOT_BE_DUPLICATED: 'Columns cannot be duplicated',
	DELETE_RSSFEED_CONFIRMATION: 'Are you sure to delete the rss feed?',
	LBL_AUDIT_TRAIL_ENABLED: 'Audit Trail Enabled',
	LBL_AUDIT_TRAIL_DISABLED: 'Audit Trail Disabled',
	PLEASE_ENTER_TAG: 'Please enter a tag',
	SERVERNAME_CANNOT_BE_EMPTY: 'Server Name cannot be empty',
	LBL_BAD_CHARACTER_PICKLIST_VALUE: 'The following characters are not allowed',
	GRAPES_CO_WARNING: 'In order to make images upload works, you have to connect with the url %s',
	LBL_GRAPES_SYNTAX_ERROR: 'The HTML code contains syntax errors. The error generated by the system is: \"%s\". Please review your code and try again.',
	LBL_TRANS_SETTINGS_SAVED: 'Settings have been saved',
	LBL_TRANS_DELETED: 'Status Field has been removed successfully',
	LBL_LOAD_RELATIONS_ENQUEUE: 'You selected more than {max_records} items. The process will continue in background and you\'ll be notified at the end.',
	LBL_MASS_CREATE_ENQUEUE_TELEMARKETING: 'The targets you have selected contain more than {max_records} items. The Telemarketing synchronization process will continue in background and you\'ll be notified at the end.', // crmv@202577
	LBL_ATTACHMENT_NOT_EXIST: 'Attachment {name} doesn\'t exist, probably the message has been moved to another folder.',
    LBL_ATTACHMENT_DELETED: 'The message has been moved to another folder. You have to wait a few minutes for it to be synchronized.',
    // crmv@205899
	LBL_GRAPES_MODULE: 'Module',
	LBL_GRAPES_FIELD: 'Field',
	LBL_GRAPES_INSERT: 'Insert',
	LBL_GRAPES_EMPTY_PLACEHOLDER: '-- Select --',
	// crmv@205899e
	LBL_AUTHENTICATION_REQUIRED: 'Authentication is required',
	LBL_KLONDIKE_UNLINK_CONFIRM: 'Removing the link with Klondike you won\'t be able to access your Klondike with these buttons. Are you sure?', // crmv@215597
};
