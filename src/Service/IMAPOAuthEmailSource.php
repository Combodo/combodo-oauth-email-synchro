<?php

namespace Combodo\iTop\Extension\OAuthEmailSynchro\Service;

use Combodo\iTop\Extension\EmailSynchro\Service\IMAPEmailSource;
use Combodo\iTop\Extension\OAuthEmailSynchro\Helper\ProviderHelper;
use IssueLog;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use MailInboxBase;

class IMAPOAuthEmailSource extends IMAPEmailSource
{
	public const LOG_CHANNEL = IMAPOAuthEmailLogger::LOG_CHANNEL;
	public const LOG_DEBUG_CLASS = 'IMAPOAuthEmailSource';
	public const CONFIG_AUTHENTICATION = 'oauth';

	/**
	 * @throws \Exception
	 */
	public function __construct(MailInboxBase $oMailbox)
	{
		$oProvider = ProviderHelper::getProviderForIMAP($oMailbox);
		$this->sAccessToken = '';

		try {
			$this->sAccessToken = ProviderHelper::GetAccessTokenForProvider($oProvider);
		} catch (IdentityProviderException $e) {
			IssueLog::Error('Failed to get IMAP oAuth credentials for incoming mails for provider '.$oProvider::GetVendorName(), static::LOG_CHANNEL, [
				'exception.message' => $e->getMessage(),
				'exception.stack'   => $e->getTraceAsString(),
			]);
		}

		if (empty($this->sAccessToken)) {
			IssueLog::Error('No OAuth token for IMAP for provider '.$oProvider::GetVendorName(), static::LOG_CHANNEL);
		}

		parent::__construct($oMailbox);
	}
}
