<?php

namespace Combodo\iTop\Extension\Service;

use Combodo\iTop\Application\UI\Base\Component\Button\ButtonUIBlockFactory;
use Combodo\iTop\Application\UI\Base\Component\Panel\Panel;
use Combodo\iTop\Core\Authentication\Client\OAuth\IOAuthClientResultDisplay;
use Dict;
use League\OAuth2\Client\Token\AccessToken;
use utils;


class OAuthClientResultDisplayMailbox implements IOAuthClientResultDisplay
{
	public static function GetResultDisplayBlock()
	{
		$oMBResultPanel = new Panel(Dict::S('UI:OAuthEmailSynchro:Wizard:ResultConf:Panel:Title'), [], Panel::DEFAULT_COLOR_SCHEME, 'ibo-oauth-wizard--mailbox--panel');
		$oMBResultPanel->AddCSSClass('ibo-oauth-wizard--result--panel');
		$oMBResultPanel->SetIsCollapsible(true);
		$oMBResultPanel->AddHtml('<p>'.Dict::S('UI:OAuthEmailSynchro:Wizard:ResultConf:Panel:Description').'</p>');
		$oCreateButton = ButtonUIBlockFactory::MakeLinkNeutral('', 'Create a new Mailbox', '', '_blank', 'ibo-oauth-wizard--mailbox--button')
			->SetColor('primary');
		$oMBResultPanel->AddSubBlock($oCreateButton);

		return $oMBResultPanel;
	}

	public static function GetResultDisplayScript($sClientId, $sClientSecret, $sVendor, AccessToken $oAccessToken)
	{
		$sAccessToken = $oAccessToken->getToken();
		$sRefreshToken = $oAccessToken->getRefreshToken();
		$sURL = utils::GetAbsoluteUrlAppRoot()."pages/UI.php?operation=new&class=MailInboxOAuth&default[oauth_provider]=".$sVendor.
			"&default[oauth_client_id]=".$sClientId.
			"&default[oauth_client_secret]=".$sClientSecret.
			"&default[oauth_access_token]=".$sAccessToken.
			"&default[oauth_refresh_token]=".$sRefreshToken;

		return <<<JS

$('#ibo-oauth-wizard--mailbox--panel .ibo-panel--collapsible-toggler').click();
$('#ibo-oauth-wizard--mailbox--button').attr("href", "$sURL");
JS;

	}

	public static function GetResultDisplayTemplate()
	{
		// TODO: Implement GetResultDisplayTemplate() method.
	}
}