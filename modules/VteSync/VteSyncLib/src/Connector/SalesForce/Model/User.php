<?php
/*************************************
 * SPDX-FileCopyrightText: 2009-2020 Vtenext S.r.l. <info@vtenext.com> 
 * SPDX-License-Identifier: AGPL-3.0-only  
 ************************************/

namespace VteSyncLib\Connector\SalesForce\Model;

use VteSyncLib\Model\GenericUser;
use VteSyncLib\Model\CommonUser;

class User extends GenericUser {

	protected $createdTime;
	protected $modifiedTime;

	public static function fromRawData($data) {
		$id = $data['Id'];
		$modTime = new \DateTime($data['LastModifiedDate']);
		$etag = strval($modTime->getTimestamp());
		$fields = array(
			'username' => $data['Username'],
			'title' => $data['Title'],
			'lastname' => $data['LastName'],
			'firstname' => $data['FirstName'],
			'email' => $data['Email'],
			'phone' => $data['Phone'],
			'mobile' => $data['MobilePhone'],
			'fax' => $data['Fax'],
			'department' => $data['Department'],
			'active' => $data['IsActive'] == '1',
			'timezone' => $data['TimeZoneSidKey'],
			'language' => $data['LanguageLocaleKey'],
			// address
			'street' => $data['Address']['street'],
			'city' => $data['Address']['city'],
			'postalcode' => $data['Address']['postalCode'],
			'state' => $data['Address']['state'],
			'country' => $data['Address']['country'],
		);
		$user = new self($id, $etag, $fields);
		$user->rawData = $data;
		$user->createdTime = new \DateTime($data['CreatedDate']);
		$user->modifiedTime = $modTime;
		return $user;
	}
	
	public static function fromCommonUser(CommonUser $cuser) {
	}
	
	public function toCommonUser() {
		if (empty($this->modifiedTime)) {
			return false;
		}
		$cuser = new CommonUser('SalesForce', 'Users', $this->id, $this->etag, $this->fields, $this->createdTime, $this->modifiedTime);
		return $cuser;
	}
}

/*
            [rawData:protected] => Array
                (
                    [attributes] => Array
                        (
                            [type] => User
                            [url] => /services/data/v45.0/sobjects/User/0051i000000IZgeAAG
                        )

                    [Id] => 0051i000000IZgeAAG
                    [Username] => integration@00d1i0000008l9feaq.com
                    [LastName] => User
                    [FirstName] => Integration
                    [Name] => Integration User
                    [CompanyName] => Self
                    [Division] => 
                    [Department] => 
                    [Title] => 
                    [Street] => 1 Market
                    [City] => San Francisco
                    [State] => CA
                    [PostalCode] => 94015
                    [Country] => PL
                    [Latitude] => 
                    [Longitude] => 
                    [GeocodeAccuracy] => 
                    [Address] => Array
                        (
                            [city] => San Francisco
                            [country] => PL
                            [geocodeAccuracy] => 
                            [latitude] => 
                            [longitude] => 
                            [postalCode] => 94015
                            [state] => CA
                            [street] => 1 Market
                        )

                    [Email] => integration@example.com
                    [EmailPreferencesAutoBcc] => 1
                    [EmailPreferencesAutoBccStayInTouch] => 
                    [EmailPreferencesStayInTouchReminder] => 1
                    [SenderEmail] => 
                    [SenderName] => 
                    [Signature] => 
                    [StayInTouchSubject] => 
                    [StayInTouchSignature] => 
                    [StayInTouchNote] => 
                    [Phone] => 
                    [Fax] => 
                    [MobilePhone] => 
                    [Alias] => integ
                    [CommunityNickname] => integration1.4407085834085586E12
                    [BadgeText] => 
                    [IsActive] => 1
                    [TimeZoneSidKey] => Europe/Dublin
                    [UserRoleId] => 
                    [LocaleSidKey] => en_IE_EURO
                    [ReceivesInfoEmails] => 
                    [ReceivesAdminInfoEmails] => 
                    [EmailEncodingKey] => ISO-8859-1
                    [ProfileId] => 00e1i000000MMn3AAG
                    [UserType] => Standard
                    [LanguageLocaleKey] => en_US
                    [EmployeeNumber] => 
                    [DelegatedApproverId] => 
                    [ManagerId] => 
                    [LastLoginDate] => 
                    [LastPasswordChangeDate] => 
                    [CreatedDate] => 2019-02-18T16:46:25.000+0000
                    [CreatedById] => 0051i000000ps4UAAQ
                    [LastModifiedDate] => 2019-02-18T16:46:25.000+0000
                    [LastModifiedById] => 0051i000000ps4UAAQ
                    [SystemModstamp] => 2019-02-18T16:49:01.000+0000
                    [OfflineTrialExpirationDate] => 
                    [OfflinePdaTrialExpirationDate] => 
                    [UserPermissionsMarketingUser] => 
                    [UserPermissionsOfflineUser] => 
                    [UserPermissionsCallCenterAutoLogin] => 
                    [UserPermissionsMobileUser] => 
                    [UserPermissionsSFContentUser] => 
                    [UserPermissionsKnowledgeUser] => 
                    [UserPermissionsInteractionUser] => 
                    [UserPermissionsSupportUser] => 
                    [UserPermissionsJigsawProspectingUser] => 
                    [UserPermissionsSiteforceContributorUser] => 
                    [UserPermissionsSiteforcePublisherUser] => 
                    [UserPermissionsWorkDotComUserFeature] => 
                    [ForecastEnabled] => 
                    [UserPreferencesActivityRemindersPopup] => 1
                    [UserPreferencesEventRemindersCheckboxDefault] => 1
                    [UserPreferencesTaskRemindersCheckboxDefault] => 1
                    [UserPreferencesReminderSoundOff] => 
                    [UserPreferencesDisableAllFeedsEmail] => 
                    [UserPreferencesDisableFollowersEmail] => 
                    [UserPreferencesDisableProfilePostEmail] => 
                    [UserPreferencesDisableChangeCommentEmail] => 
                    [UserPreferencesDisableLaterCommentEmail] => 
                    [UserPreferencesDisProfPostCommentEmail] => 
                    [UserPreferencesContentNoEmail] => 
                    [UserPreferencesContentEmailAsAndWhen] => 
                    [UserPreferencesApexPagesDeveloperMode] => 
                    [UserPreferencesHideCSNGetChatterMobileTask] => 
                    [UserPreferencesDisableMentionsPostEmail] => 
                    [UserPreferencesDisMentionsCommentEmail] => 
                    [UserPreferencesHideCSNDesktopTask] => 
                    [UserPreferencesHideChatterOnboardingSplash] => 
                    [UserPreferencesHideSecondChatterOnboardingSplash] => 
                    [UserPreferencesDisCommentAfterLikeEmail] => 
                    [UserPreferencesDisableLikeEmail] => 
                    [UserPreferencesSortFeedByComment] => 
                    [UserPreferencesDisableMessageEmail] => 
                    [UserPreferencesJigsawListUser] => 
                    [UserPreferencesDisableBookmarkEmail] => 
                    [UserPreferencesDisableSharePostEmail] => 
                    [UserPreferencesEnableAutoSubForFeeds] => 
                    [UserPreferencesDisableFileShareNotificationsForApi] => 
                    [UserPreferencesShowTitleToExternalUsers] => 
                    [UserPreferencesShowManagerToExternalUsers] => 
                    [UserPreferencesShowEmailToExternalUsers] => 
                    [UserPreferencesShowWorkPhoneToExternalUsers] => 
                    [UserPreferencesShowMobilePhoneToExternalUsers] => 
                    [UserPreferencesShowFaxToExternalUsers] => 
                    [UserPreferencesShowStreetAddressToExternalUsers] => 
                    [UserPreferencesShowCityToExternalUsers] => 
                    [UserPreferencesShowStateToExternalUsers] => 
                    [UserPreferencesShowPostalCodeToExternalUsers] => 
                    [UserPreferencesShowCountryToExternalUsers] => 
                    [UserPreferencesShowProfilePicToGuestUsers] => 
                    [UserPreferencesShowTitleToGuestUsers] => 
                    [UserPreferencesShowCityToGuestUsers] => 
                    [UserPreferencesShowStateToGuestUsers] => 
                    [UserPreferencesShowPostalCodeToGuestUsers] => 
                    [UserPreferencesShowCountryToGuestUsers] => 
                    [UserPreferencesDisableFeedbackEmail] => 
                    [UserPreferencesDisableWorkEmail] => 
                    [UserPreferencesPipelineViewHideHelpPopover] => 
                    [UserPreferencesHideS1BrowserUI] => 
                    [UserPreferencesDisableEndorsementEmail] => 
                    [UserPreferencesPathAssistantCollapsed] => 
                    [UserPreferencesCacheDiagnostics] => 
                    [UserPreferencesShowEmailToGuestUsers] => 
                    [UserPreferencesShowManagerToGuestUsers] => 
                    [UserPreferencesShowWorkPhoneToGuestUsers] => 
                    [UserPreferencesShowMobilePhoneToGuestUsers] => 
                    [UserPreferencesShowFaxToGuestUsers] => 
                    [UserPreferencesShowStreetAddressToGuestUsers] => 
                    [UserPreferencesLightningExperiencePreferred] => 1
                    [UserPreferencesPreviewLightning] => 
                    [UserPreferencesHideEndUserOnboardingAssistantModal] => 
                    [UserPreferencesHideLightningMigrationModal] => 
                    [UserPreferencesHideSfxWelcomeMat] => 
                    [UserPreferencesHideBiggerPhotoCallout] => 
                    [UserPreferencesGlobalNavBarWTShown] => 
                    [UserPreferencesGlobalNavGridMenuWTShown] => 
                    [UserPreferencesCreateLEXAppsWTShown] => 
                    [UserPreferencesFavoritesWTShown] => 
                    [UserPreferencesRecordHomeSectionCollapseWTShown] => 
                    [UserPreferencesRecordHomeReservedWTShown] => 
                    [UserPreferencesFavoritesShowTopFavorites] => 
                    [UserPreferencesExcludeMailAppAttachments] => 
                    [UserPreferencesSuppressTaskSFXReminders] => 
                    [UserPreferencesSuppressEventSFXReminders] => 
                    [UserPreferencesPreviewCustomTheme] => 
                    [UserPreferencesHasCelebrationBadge] => 
                    [UserPreferencesUserDebugModePref] => 
                    [UserPreferencesNewLightningReportRunPageEnabled] => 
                    [ContactId] => 
                    [AccountId] => 
                    [CallCenterId] => 
                    [Extension] => 
                    [FederationIdentifier] => 
                    [AboutMe] => 
                    [FullPhotoUrl] => https://c.eu19.content.force.com/profilephoto/005/F
                    [SmallPhotoUrl] => https://c.eu19.content.force.com/profilephoto/005/T
                    [IsExtIndicatorVisible] => 
                    [OutOfOfficeMessage] => 
                    [MediumPhotoUrl] => https://c.eu19.content.force.com/profilephoto/005/M
                    [DigestFrequency] => N
                    [DefaultGroupNotificationFrequency] => N
                    [JigsawImportLimitOverride] => 
                    [LastViewedDate] => 2019-03-04T09:08:34.000+0000
                    [LastReferencedDate] => 2019-03-04T09:08:34.000+0000
                    [BannerPhotoUrl] => /profilephoto/005/B
                    [SmallBannerPhotoUrl] => /profilephoto/005/D
                    [MediumBannerPhotoUrl] => /profilephoto/005/E
                    [IsProfilePhotoActive] => 

*/