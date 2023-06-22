<?php
/**
 * Localized data
 *
 * @copyright   Copyright (C) 2013 XXXXX
 * @license     http://opensource.org/licenses/AGPL-3.0
 */
Dict::Add('DE DE', 'German', 'Deutsch', array(
	'UI:OAuthEmailSynchro:Wizard:ResultConf:Panel:Title' => 'Mailpostfach erstellen',
	'UI:OAuthEmailSynchro:Wizard:ResultConf:Panel:Description' => 'Erstellen eines neuen Mailpostfachs zum Abrufen von Mails unter der Nutzung  der Authentifizierung über oAuth',
	'UI:OAuthEmailSynchro:Wizard:ResultConf:Panel:CreateNewMailbox' => 'Neue Mailbox erstellen',
	'UI:OAuthEmailSynchro:Error:UnknownVendor' => 'Der oAuth-Provider %1$s existiert nicht',
));

//
// Class: MailInboxOAuth
//

Dict::Add('DE DE', 'German', 'Deutsch', array(
	'Class:MailInboxOAuth' => 'OAuth 2.0 Maileingangspostfach',
	'Class:MailInboxOAuth+' => '',
	'Class:MailInboxOAuth/Attribute:oauth_provider' => 'OAuth-Provider',
	'Class:MailInboxOAuth/Attribute:oauth_provider+' => '',
	'Class:MailInboxOAuth/Attribute:oauth_client_id' => 'OAuth-Client',
	'Class:MailInboxOAuth/Attribute:oauth_client_id+' => '',
));

//
// Class: MailInboxOAuth
//

Dict::Add('DE DE', 'German', 'Deutsch', array(
	'Class:MailInboxOAuth/Attribute:client_id' => 'Client ID',
	'Class:MailInboxOAuth/Attribute:client_id+' => 'Eine lange Zeichenfolge, die durch den oAuth2-Provider bereitgestellt wird',
	'Class:MailInboxOAuth/Attribute:client_secret' => 'Client Secret',
	'Class:MailInboxOAuth/Attribute:client_secret+' => 'Eine weitere lange Zeichenfolge, die durch den oAuth2-Provider bereitgestellt wird',
	'Class:MailInboxOAuth/Attribute:access_token' => 'Zugriffstoken',
	'Class:MailInboxOAuth/Attribute:access_token+' => '',
	'Class:MailInboxOAuth/Attribute:refresh_token' => 'Erneuerungs-Token',
	'Class:MailInboxOAuth/Attribute:refresh_token+' => '',
));
