<?php
//namespace Combodo\iTop\Extension\OAuthEmailSynchroService\Service;
namespace Combodo\iTop\Extension\Service;

use Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProviderAbstract as OAuthClientProviderAbstractAlias;
use IssueLog;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class IMAPOAuthLogin extends \Laminas\Mail\Protocol\Imap{

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
		try {
			IssueLog::Info('Début login');

			if (empty($this->oProvider->GetAccessToken())) {
				throw new IdentityProviderException('Not prior authentication to OAuth', 255, []);
			}
			else {
				$this->oProvider->SetAccessToken($this->oProvider->GetVendorProvider()->getAccessToken('refresh_token', [
					'refresh_token' => $this->oProvider->GetAccessToken()->getRefreshToken(),
					'scope' => $this->oProvider->GetScope()
				]));
			}
		}
		catch (IdentityProviderException $e) {
			\IssueLog::Error('Failed to get oAuth credentials for incoming mails');
			return false;
		}
		$sAccessToken = $this->oProvider->GetAccessToken()->getToken();

		if (empty($sAccessToken)) {
			return false;
		}
		$this->sendRequest(
			'AUTHENTICATE',
			[
				'XOAUTH2',
				base64_encode("user={$user}\001auth=Bearer {$sAccessToken}\001\001")
			]
		);

		while (true) {
			IssueLog::Info('Boucle login');
			$response = '';

			$isPlus = $this->readLine($response, '+', true);
			if ($isPlus) {
				// Send empty client response.
				$this->sendRequest('');
			} else {
				if (
					preg_match('/^NO/i', $response) ||
					preg_match('/^BAD/i', $response)) {
					IssueLog::Info('Fin login: fail');
					return false;
				}
				if (preg_match("/^OK /i", $response)){
					IssueLog::Info('Fin login: réussi :)');
					$this->auth = true;
					return true;
				}
			}
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