<?php
/**
 * Localized data
 *
 * @copyright   Copyright (C) 2013 XXXXX
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

Dict::Add('EN US', 'English', 'English', array(
	'UI:OAuthEmailSynchro:Wizard:ResultConf:Panel:Title' => 'Create a Mailbox',
	'UI:OAuthEmailSynchro:Wizard:ResultConf:Panel:Description' => 'Create a new Mailbox to fetch emails from a remote mail provider using this OAuth connection as authentication method',
	'UI:OAuthEmailSynchro:Wizard:ResultConf:Panel:CreateNewMailbox' => 'Create a new mailbox',

	'UI:OAuthEmailSynchro:Error:UnknownVendor' => 'OAuth provider %1$s does not exist',
));

//
// Class: MailInboxOAuth
//

Dict::Add('EN US', 'English', 'English', array(
	'Class:MailInboxOAuth' => 'OAuth 2.0 Mail Inbox',
	'Class:MailInboxOAuth+' => '',
	'Class:MailInboxOAuth/Attribute:oauth_provider' => 'Oauth provider',
	'Class:MailInboxOAuth/Attribute:oauth_provider+' => '',
	'Class:MailInboxOAuth/Attribute:oauth_client_id' => 'Oauth client id',
	'Class:MailInboxOAuth/Attribute:oauth_client_id+' => '',
	'Class:MailInboxOAuth/Attribute:oauth_client_secret' => 'Oauth client secret',
	'Class:MailInboxOAuth/Attribute:oauth_client_secret+' => '',
	'Class:MailInboxOAuth/Attribute:oauth_access_token' => 'Oauth access token',
	'Class:MailInboxOAuth/Attribute:oauth_access_token+' => '',
	'Class:MailInboxOAuth/Attribute:oauth_refresh_token' => 'Oauth refresh token',
	'Class:MailInboxOAuth/Attribute:oauth_refresh_token+' => '',
	'Class:MailInboxOAuth/Attribute:remote_authent_oauth_id' => 'Remote Authentication (OAuth)',
	'Class:MailInboxOAuth/Attribute:remote_authent_oauth_id+' => '',
));
