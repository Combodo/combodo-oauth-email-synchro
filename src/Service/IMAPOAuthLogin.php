<?php
//namespace Combodo\iTop\Extension\OAuthEmailSynchroService\Service;
namespace Combodo\iTop\Extension\Service;

use Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProviderAbstract as OAuthClientProviderAbstractAlias;
use IssueLog;
use Laminas\Mail\Protocol\Exception\RuntimeException;
use Laminas\Mail\Protocol\Imap;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class IMAPOAuthLogin extends Imap
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

	public function login($user, $password)
	{
		try
		{
			if (empty($this->oProvider->GetAccessToken()))
			{
				throw new IdentityProviderException('Not prior authentication to OAuth', 255, []);
			}
			else
			{
				$this->oProvider->SetAccessToken($this->oProvider->GetVendorProvider()->getAccessToken('refresh_token',
					[
						'refresh_token' => $this->oProvider->GetAccessToken()->getRefreshToken(),
						'scope' => $this->oProvider->GetScope()
					]));
			}
		} catch (IdentityProviderException $e)
		{
			IssueLog::Error('Failed to get IMAP oAuth credentials for incoming mails for provider '.$this->oProvider::GetVendorName(), static::LOG_CHANNEL);

			return false;
		}
		$sAccessToken = $this->oProvider->GetAccessToken()->getToken();

		if (empty($sAccessToken))
		{
			IssueLog::Error('No OAuth token for IMAP for provider '.$this->oProvider::GetVendorName(), static::LOG_CHANNEL);

			return false;
		}
		$this->sendRequest(
			'AUTHENTICATE',
			[
				'XOAUTH2',
				base64_encode("user=$user\001auth=Bearer $sAccessToken\001\001")
			]
		);
		IssueLog::Debug("IMAP Oauth sending AUTHENTICATE XOAUTH2 user=$user auth=Bearer $sAccessToken", static::LOG_CHANNEL);

		try {
			while (true) {
				$sResponse = '';

				$isPlus = $this->readLine($sResponse, '+', true);
				IssueLog::Debug("IMAP Oauth receiving $sResponse", static::LOG_CHANNEL);
				if ($isPlus) {
					// Send empty client sResponse.
					$this->sendRequest('');
				} else {
					if (preg_match('/^NO/i', $sResponse) ||
						preg_match('/^BAD/i', $sResponse)) {
						IssueLog::Error('Unable to authenticate for IMAP for provider '.$this->oProvider::GetVendorName()." Error: $sResponse", static::LOG_CHANNEL);

						return false;
					}
					if (preg_match("/^OK /i", $sResponse)) {
						return true;
					}
				}
			}
		} catch (RuntimeException $e) {
			IssueLog::Error('Timeout connection for IMAP for provider '.$this->oProvider::GetVendorName(), static::LOG_CHANNEL);
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