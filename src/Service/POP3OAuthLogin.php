<?php
//namespace Combodo\iTop\Extension\OAuthEmailSynchroService\Service;
namespace Combodo\iTop\Extension\Service;

use Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProviderAbstract as OAuthClientProviderAbstractAlias;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;

class POP3OAuthLogin extends \Laminas\Mail\Protocol\Pop3 {

	/**
	 * Public constructor
	 *
	 * @param  string       $host           hostname or IP address of IMAP server, if given connect() is called
	 * @param  int|null     $port           port of IMAP server, null for default (143 or 993 for ssl)
	 * @param  string|bool  $ssl            use ssl? 'SSL', 'TLS' or false
	 * @param  bool         $novalidatecert set to true to skip SSL certificate validation
	 * @throws \Laminas\Mail\Protocol\Exception\ExceptionInterface
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
			}
			else {
				$this->oProvider->SetAccessToken($this->oProvider->GetVendorProvider()->getAccessToken('refresh_token', [
					'refresh_token' => $this->oProvider->GetAccessToken()->getRefreshToken()
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
		$response = $this->request(
			'AUTH XOAUTH2 '.base64_encode("user={$user}\1auth=Bearer {$sAccessToken}\1\1")
		);

		while (true) {
			$isPlus = $response === '+';
			if ($isPlus) {
				// Send empty client response.
				$this->request('');
			} else {
				if (
					preg_match('/^NO/i', $response) ||
					preg_match('/^BAD/i', $response)) {
					return false;
				}
				if (preg_match("/Welcome/i", $response)){
					$this->auth = true;
					return true;
				}
			}
			$response = $this->readResponse();
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