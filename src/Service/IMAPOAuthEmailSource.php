<?php

namespace Combodo\iTop\Extension\Service;

use Combodo\iTop\Extension\Helper\ImapOptionsHelper;
use Combodo\iTop\Extension\Helper\ProviderHelper;
use DirectoryTree\ImapEngine\Enums\ImapFetchIdentifier;
use DirectoryTree\ImapEngine\FolderInterface;
use DirectoryTree\ImapEngine\Mailbox;
use DirectoryTree\ImapEngine\MailboxInterface;
use EmailSource;
use Exception;
use IssueLog;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use MessageFromMailbox;

class IMAPOAuthEmailSource extends EmailSource
{
	const LOG_CHANNEL = 'OAuth';

	/** LOGIN username @var string */
	protected $sLogin;
	protected $sServer;
	protected $sTargetFolder;
	protected $sMailbox;
	/**
	 * @var MailboxInterface|null
	 */
	private $oMailbox;
	/**
	 * @var FolderInterface|null
	 */
	private $oFolder;
	private $bMessagesDeleted = false;

	/**
	 * Constructor.
	 *
	 * @param $oMailbox
	 *
	 * @throws \Exception
	 */
	public function __construct($oMailbox)
	{
		$sServer = $oMailbox->Get('server');
		$this->sServer = $sServer;
		$sLogin = $oMailbox->Get('login');
		$this->sLogin = $sLogin;
		$sMailbox = $oMailbox->Get('mailbox');
		$this->sMailbox = $sMailbox;
		$iPort = $oMailbox->Get('port');
		$this->sTargetFolder = $oMailbox->Get('target_folder');

		IssueLog::Debug("IMAPOAuthEmailSource Start for $this->sServer", static::LOG_CHANNEL);
		$oImapOptions = new ImapOptionsHelper();
		$sSSL = '';
		if ($oImapOptions->HasOption('ssl')) {
			$sSSL = 'ssl';
		} elseif ($oImapOptions->HasOption('tls')) {
			$sSSL = 'tls';
		}

		$oProvider = ProviderHelper::getProviderForIMAP($oMailbox);
		$sAccessToken = '';
		try
		{
			$sAccessToken = ProviderHelper::GetAccessTokenForProvider($oProvider);
		}
		catch (IdentityProviderException $e)
		{
			IssueLog::Error('Failed to get IMAP oAuth credentials for incoming mails for provider ' . $oProvider::GetVendorName() , static::LOG_CHANNEL, [
				'exception.message' => $e->getMessage(),
				'exception.stack'   => $e->getTraceAsString(),
			]);
		}

		if (empty($sAccessToken))
		{
			IssueLog::Error('No OAuth token for IMAP for provider '.$oProvider::GetVendorName(), static::LOG_CHANNEL);
		}

		$this->oMailbox = new Mailbox([
			'port' => $iPort,
			'username' => $sLogin,
			'password' => $sAccessToken,
			'encryption' => $sSSL,
			'authentication' => 'oauth',
			'host' => $sServer,
			'debug' => IMAPOAuthEmailLogger::class,
		]);

		$this->oMailbox->connect();

		// Calls parent with original arguments
		parent::__construct();
	}

	public function GetMessagesCount()
	{
		IssueLog::Debug("IMAPOAuthEmailSource Start GetMessagesCount for $this->sServer", static::LOG_CHANNEL);
		$iCount = $this->GetFolder()->status()['MESSAGES'] ?? 0;
		IssueLog::Debug("IMAPOAuthEmailSource $iCount message(s) found for $this->sServer", static::LOG_CHANNEL);

		return $iCount;
	}

	public function GetMessage($index)
	{
		$iOffsetIndex = 1 + $index;

		IssueLog::Debug(__METHOD__." Start: $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);
		try {
			$oMessage = $this->GetFolder()
				->messages()
				->withHeaders()
				->withBody()
				->findOrFail($iOffsetIndex, ImapFetchIdentifier::MessageNumber);


			if (!$oMessage) {
				return null;
			}
			$sUIDL = static::UseMessageIdAsUid() ? $oMessage->messageId() : $oMessage->uid();
		}
		catch (Exception $e) {
			IssueLog::Error(__METHOD__." $iOffsetIndex for $this->sServer throws an exception", static::LOG_CHANNEL, [
				'exception.message' => $e->getMessage(),
				'exception.stack'   => $e->getTraceAsString(),
			]);

			return null;
		}
		$oNewMail = new MessageFromMailbox($sUIDL, $oMessage->head(), $oMessage->body());
		IssueLog::Debug(__METHOD__." End: $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);

		return $oNewMail;
	}

	public function DeleteMessage($index)
	{
		$iOffsetIndex = 1 + $index;

		IssueLog::Debug(__METHOD__." Start: $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);
		try {
			$oMessage = $this->GetFolder()
				->messages()
				->find($iOffsetIndex, ImapFetchIdentifier::MessageNumber);

			if (!$oMessage) {
				return null;
			}

			$oMessage->delete();
			$this->bMessagesDeleted = true;

		}
		catch (Exception $e) {
			IssueLog::Error(__METHOD__." $iOffsetIndex for $this->sServer throws an exception", static::LOG_CHANNEL, [
				'exception.message' => $e->getMessage(),
				'exception.stack'   => $e->getTraceAsString(),
			]);

			return null;
		}
		IssueLog::Debug(__METHOD__." End: $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);

		 return true;
	}

	public function GetName()
	{
		return $this->sLogin;
	}

	public function GetSourceId()
	{
		return $this->sServer.'/'.$this->sLogin;
	}

	public function GetListing()
	{

		$aReturn = [];
		$oMessages = $this->GetFolder()
			->messages()
			->withHeaders()
			->get();
		foreach ($oMessages as $oMessage) {
			$aReturn[] = [
				'msg_id' => $oMessage->messageId(),
				'uidl'   => static::UseMessageIdAsUid()? $oMessage->messageId() : $oMessage->uid(),
			];
		}
		return $aReturn;
	}

	public function GetFolder() {
		if( $this->oFolder === null ) {
			$this->oFolder =  $this->oMailbox->folders()->find($this->sMailbox);
		}
		return $this->oFolder;
	}

	/**
	 * Move the message of the given index [0..Count] from the mailbox to another folder
	 *
	 * @param $index integer The index between zero and count
	 *
	 * @throws \DirectoryTree\ImapEngine\Exceptions\ImapCapabilityException
	 */
	public function MoveMessage($index)
	{
		$iOffsetIndex = 1 + $index;
		IssueLog::Debug(__METHOD__." Start: $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);
		try {
			$oMessage = $this->GetFolder()
				->messages()
				->find($iOffsetIndex, ImapFetchIdentifier::MessageNumber);

			if (!$oMessage) {
				return false;
			}

			// Use copy+delete instead of move as GMail won't expunge automatically and break our way of iterating over messages indexes
			$oMessage->copy($this->sTargetFolder);
			$oMessage->delete();
			$this->bMessagesDeleted = true;
		}
		catch (Exception $e) {
			IssueLog::Error(__METHOD__." $iOffsetIndex for $this->sServer throws an exception", static::LOG_CHANNEL, [
				'exception.message' => $e->getMessage(),
				'exception.stack'   => $e->getTraceAsString(),
			]);

			return false;
		}

		IssueLog::Debug(__METHOD__." End: $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);
		return true;
	}

	public function Disconnect()
	{
		// Expunge deleted messages before disconnecting
		if( $this->bMessagesDeleted ) {
			IssueLog::Debug(__METHOD__." Expunging deleted messages for $this->sServer", static::LOG_CHANNEL);
			$this->GetFolder()->expunge();
		}

		$this->oMailbox->disconnect();
	}

	public function GetMailbox()
	{
		return $this->sMailbox;
	}
}
