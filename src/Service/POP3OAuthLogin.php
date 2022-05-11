<?php

namespace Combodo\iTop\Extension\Service;

use Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProviderAbstract as OAuthClientProviderAbstractAlias;
use IssueLog;
use Laminas\Mail\Protocol\Exception\RuntimeException;
use Laminas\Mail\Protocol\Pop3;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class POP3OAuthLogin extends Pop3
{
	const LOG_CHANNEL = 'OAuth';

	/**
	 * Public constructor
	 *
	 * @param $oProvider
	 */
	public function __construct($oProvider)
	{
		parent::__construct();
		$this->setProvider($oProvider);
	}

	/**
	 * LOGIN username
	 *
	 * @var OAuthClientProviderAbstractAlias
	 */
	protected $oProvider;

	public function login($user, $password, $tryApop = true)
	{
		try {
			if (empty($this->oProvider->GetAccessToken())) {
				throw new IdentityProviderException('Not prior authentication to OAuth', 255, []);
			} else {
				$this->oProvider->SetAccessToken($this->oProvider->GetVendorProvider()->getAccessToken('refresh_token', [
					'refresh_token' => $this->oProvider->GetAccessToken()->getRefreshToken(),
				]));
			}
		}
		catch (IdentityProviderException $e) {
			IssueLog::Error('Failed to get oAuth credentials for POP3 for provider '.$this->oProvider::GetVendorName(), static::LOG_CHANNEL);

			return false;
		}
		$sAccessToken = $this->oProvider->GetAccessToken()->getToken();

		if (empty($sAccessToken)) {
			IssueLog::Error('No OAuth token for POP3 for provider '.$this->oProvider::GetVendorName(), static::LOG_CHANNEL);

			return false;
		}
		$sResponse = $this->request(
			'AUTH XOAUTH2 '.base64_encode("user=$user\1auth=Bearer $sAccessToken\1\1")
		);
		IssueLog::Debug("POP3 Oauth sending AUTH XOAUTH2 user=$user auth=Bearer $sAccessToken", static::LOG_CHANNEL);

		try {
			while (true) {
				if ($sResponse === '+') {
					// Send empty client response.
					$this->request('');
				} else {
					IssueLog::Debug("IMAP Oauth receiving $sResponse", static::LOG_CHANNEL);
					if (preg_match('/^NO/i', $sResponse) ||
						preg_match('/^BAD/i', $sResponse)) {
						IssueLog::Error('Unable to authenticate for POP3 for provider '.$this->oProvider::GetVendorName()." Error: $sResponse", static::LOG_CHANNEL);

						return false;
					}
					if (preg_match("/Welcome/i", $sResponse)) {
						return true;
					}
				}
				$sResponse = $this->readResponse();
			}
		}
		catch (RuntimeException $e) {
			IssueLog::Error('Timeout connection for POP3 for provider '.$this->oProvider::GetVendorName(), static::LOG_CHANNEL);
		}


		return false;
	}

	/**
	 * @param OAuthClientProviderAbstractAlias $oProvider
	 *
	 * @return void
	 */
	public function setProvider(OAuthClientProviderAbstractAlias $oProvider): void
	{
		$this->oProvider = $oProvider;
	}
}