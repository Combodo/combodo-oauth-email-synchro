<?php
namespace Combodo\iTop\Extension\Helper;

use Dict;
use Exception;
use GuzzleHttp\Client;

class ProviderHelper{
	public static function getProviderForIMAP($oMailbox)
	{
		$sProviderVendor = $oMailbox->Get('oauth_provider');
		$sProviderClass = "\Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProvider".$sProviderVendor;

		if (!class_exists($sProviderClass)) {
			throw new Exception(dict::Format('UI:OAuthEmailSynchro:Error:UnknownVendor', $sProviderVendor));
		}

		$aProviderVendorParams = [
			'clientId'     => $oMailbox->Get('client_id'),  // email_transport_smtp.oauth.client_id
			'clientSecret' => $oMailbox->Get('client_secret'),// email_transport_smtp.oauth.client_secret
			'redirectUri'  => $sProviderClass::GetRedirectUri(),
			'scope' => $sProviderClass::GetRequiredSMTPScope()
		];
		$aAccessTokenParams = [
			"access_token"  => $oMailbox->Get('access_token'), // email_transport_smtp.oauth.access_token
			"refresh_token" => $oMailbox->Get('refresh_token'), // email_transport_smtp.oauth.refresh_token
			'scope' => $sProviderClass::GetRequiredSMTPScope()
		];
		$aCollaborators = [
			'httpClient' => new Client(['verify' => false]),
		];


		return new $sProviderClass($aProviderVendorParams, $aCollaborators, $aAccessTokenParams);
	}
	public static function getProviderForPOP3($oMailbox)
	{
		$sProviderVendor = $oMailbox->Get('oauth_provider');
		$sProviderClass = "\Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProvider".$sProviderVendor;

		$aProviderVendorParams = [
			'clientId'     => $oMailbox->Get('client_id'),  // email_transport_smtp.oauth.client_id
			'clientSecret' => $oMailbox->Get('client_secret'),// email_transport_smtp.oauth.client_secret
			'redirectUri'  => $sProviderClass::GetRedirectUri(),
			'scope' => $sProviderClass::GetRequiredSMTPScope()
		];
		$aAccessTokenParams = [
			"access_token"  => $oMailbox->Get('access_token'), // email_transport_smtp.oauth.access_token
			"refresh_token" => $oMailbox->Get('refresh_token'), // email_transport_smtp.oauth.refresh_token
			'scope' => $sProviderClass::GetRequiredSMTPScope()
		];
		$aCollaborators = [
			'httpClient' => new Client(['verify' => false]),
		];


		return new $sProviderClass($aProviderVendorParams, $aCollaborators, $aAccessTokenParams);
	}


}