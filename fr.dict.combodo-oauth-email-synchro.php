<?php
/**
 * Localized data
 *
 * @copyright   Copyright (C) 2013 XXXXX
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

Dict::Add('FR FR', 'French', 'Français', array(
	'UI:OAuthEmailSynchro:Wizard:ResultConf:Panel:Title' => 'Créer une boite mail',
	'UI:OAuthEmailSynchro:Wizard:ResultConf:Panel:Description' => 'Créer une boite mail à synchroniser avec une boite mail distante utilisant cette connexion oAuth 2.0',
	'UI:OAuthEmailSynchro:Wizard:ResultConf:Panel:CreateNewMailbox' => 'Créer une nouvelle boite mail',

	'UI:OAuthEmailSynchro:Error:UnknownVendor' => 'Le provider Oauth 2.0 %1$s n\'existe pas',
));

//
// Class: MailInboxOAuth
//

Dict::Add('FR FR', 'French', 'Français', array(
	'Class:MailInboxOAuth' => 'Boite mail OAuth 2.0',
	'Class:MailInboxOAuth+' => '',
	'Class:MailInboxOAuth/Attribute:oauth_provider' => 'Provider Oauth 2.0',
	'Class:MailInboxOAuth/Attribute:oauth_provider+' => '',
	'Class:MailInboxOAuth/Attribute:oauth_client_id' => 'Id client OAuth 2.0',
	'Class:MailInboxOAuth/Attribute:oauth_client_id+' => '',
	'Class:MailInboxOAuth/Attribute:oauth_client_secret' => 'Secret client Oauth 2.0',
	'Class:MailInboxOAuth/Attribute:oauth_client_secret+' => '',
	'Class:MailInboxOAuth/Attribute:oauth_access_token' => 'Jeton d\'accès Oauth 2.0',
	'Class:MailInboxOAuth/Attribute:oauth_access_token+' => '',
	'Class:MailInboxOAuth/Attribute:oauth_refresh_token' => 'Jeton de renouvellement Oauth 2.0',
	'Class:MailInboxOAuth/Attribute:oauth_refresh_token+' => '',
	'Class:MailInboxOAuth/Attribute:remote_authent_oauth_id' => 'Client OAuth',
	'Class:MailInboxOAuth/Attribute:remote_authent_oauth_id+' => '',
));
