<?php
//namespace Combodo\iTop\Extension\OAuthEmailSynchroService\Service;
namespace Combodo\iTop\Extension\Service;

use Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProviderAbstract as OAuthClientProviderAbstractAlias;
use IssueLog;
use Laminas\Mail\Protocol\Exception;
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

	public function logout()
	{
		if (!$this->socket) {
			return;
		}

		// EXPUNGE at the end to keep the message id correct
		if (! $this->expunge()) {
			throw new \Laminas\Mail\Storage\Exception\RuntimeException('message marked as deleted, but could not expunge');
		}

		parent::logout();
	}

	/**
	 * @param OAuthClientProviderAbstractAlias $oProvider
	 *
	 * @return void
	 */
	public function setProvider(OAuthClientProviderAbstractAlias $oProvider)
	{
		$this->oProvider = $oProvider;
	}

	/**
	 * send a request
	 *
	 * @param  string $command your request command
	 * @param  array  $tokens  additional parameters to command, use escapeString() to prepare
	 * @param  string $tag     provide a tag otherwise an autogenerated is returned
	 * @throws Exception\RuntimeException
	 */
	public function sendRequest($command, $tokens = [], &$tag = null)
	{
		if (! $tag) {
			++$this->tagCount;
			$tag = 'TAG' . $this->tagCount;
		}

		$line = $tag . ' ' . $command;

		foreach ($tokens as $token) {
			if (is_array($token)) {
				IssueLog::Debug('IMAP Sending: '.$line . ' ' . $token[0], static::LOG_CHANNEL);
				if (fwrite($this->socket, $line . ' ' . $token[0] . "\r\n") === false) {
					throw new Exception\RuntimeException('cannot write - connection closed?');
				}
				if (! $this->assumedNextLine('+ ')) {
					throw new Exception\RuntimeException('cannot send literal string');
				}
				$line = $token[1];
			} else {
				$line .= ' ' . $token;
			}
		}

		IssueLog::Debug('IMAP Sending: '.$line, static::LOG_CHANNEL);
		if (is_null($this->socket)) {
			throw new Exception\RuntimeException('cannot write - connection closed');
		}
		if (fwrite($this->socket, $line . "\r\n") === false) {
			throw new Exception\RuntimeException('cannot write - connection closed?');
		}
	}

	/**
	 * get next line and split the tag. that's the normal case for a response line
	 *
	 * @param  string $tag tag of line is returned by reference
	 * @return string next line
	 */
	protected function nextTaggedLine(&$tag)
	{
		$line = $this->nextLine();
		IssueLog::Debug('IMAP Receive: '.trim($line), static::LOG_CHANNEL);

		// separate tag from line
		list($tag, $line) = explode(' ', $line, 2);

		return $line;
	}

	protected function nextLine()
	{
		if (is_null($this->socket)) {
			throw new Exception\RuntimeException('cannot read - connection closed?');
		}
		return parent::nextLine(); // TODO: Change the autogenerated stub
	}
}