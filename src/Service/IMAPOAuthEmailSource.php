<?php

namespace Combodo\iTop\Extension\Service;

use Combodo\iTop\Extension\Helper\ImapOptionsHelper;
use Combodo\iTop\Extension\Helper\ProviderHelper;
use EmailSource;
use Exception;
use IssueLog;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use MessageFromMailbox;
use Webklex\PHPIMAP\ClientManager;

class IMAPOAuthEmailSource extends EmailSource
{
	const LOG_CHANNEL = 'OAuth';

	/** LOGIN username @var string */
	protected $sLogin;
	protected $sServer;
	protected $sTargetFolder;
	protected $sMailbox;
	protected $oClientManager;
	protected $oClient;
	protected $oFolder;
	protected $oMessages;

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



		$this->oClientManager = new ClientManager(
			[
				'accounts' => [
					'default' => [
						'host'     => $sServer,
						'port'     => $iPort,
						'encryption' => $sSSL,
						'validate_cert' => true,
						'username' => $sLogin,
						'password' => $sAccessToken,
						'authentication'    => "oauth",
					],
				],
				'default_account' => 'default',
			]
		);
		$oClient = $this->oClientManager->account('default');
		$oClient->connect();

		//Select the folder
		$oClient->openFolder($sMailbox);
		$this->oClient = $oClient;

		// Calls parent with original arguments
		parent::__construct();
	}

	public function GetMessagesCount()
	{
		IssueLog::Debug("IMAPOAuthEmailSource Start GetMessagesCount for $this->sServer", static::LOG_CHANNEL);
		// Use select as examine opens the the folder as readonly
		$iCount = $this->GetFolder()->select()['exists'] ?? 0;
		IssueLog::Debug("IMAPOAuthEmailSource $iCount message(s) found for $this->sServer", static::LOG_CHANNEL);

		return $iCount;
	}

	public function GetMessage($index)
	{
		$iOffsetIndex = 1 + $index;

		IssueLog::Debug(__METHOD__." Start: $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);
		try {
			// Headers only cached collection
			$oMessages = $this->GetMessages();
			$oMessage = $oMessages->get($index);
			if (!$oMessage) {
				return null;
			}

			$sUIDL = static::UseMessageIdAsUid() ? $oMessage->getMessageId()->toString() : $oMessage->getSequenceId();
			// Fetch the message as the body wasn't loaded
			$sBody = $oMessage->getTextBody();
		}
		catch (Exception $e) {
			IssueLog::Error(__METHOD__." $iOffsetIndex for $this->sServer throws an exception", static::LOG_CHANNEL, [
				'exception.message' => $e->getMessage(),
				'exception.stack'   => $e->getTraceAsString(),
			]);

			return null;
		}
		$oNewMail = new MessageFromMailbox($sUIDL, $oMessage->getHeader()->raw, $sBody);
		IssueLog::Debug(__METHOD__." End: $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);

		return $oNewMail;
	}

	public function DeleteMessage($index)
	{
		$iOffsetIndex = 1 + $index;

		IssueLog::Debug(__METHOD__." Start: $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);
		try {
			$oMessages = $this->GetMessages(); // header-only cached collection
			$oMessage = $oMessages->get($index);

			if (!$oMessage) {
				return null;
			}

			$oMessage->delete(true);
		} catch (Exception $e) {
			IssueLog::Error(__METHOD__." $iOffsetIndex for $this->sServer throws an exception", static::LOG_CHANNEL, [
				'exception.message' => $e->getMessage(),
				'exception.stack'   => $e->getTraceAsString(),
			]);

			return null;
		}
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
		foreach ($this->GetMessages() as $oMessage) {
			$aReturn[] = [
				'msg_id' => $oMessage->getMsgn(),
				'uidl'   => static::UseMessageIdAsUid() ? $oMessage->getMessageId()->toString() : $oMessage->getSequenceId(),
			];
		}
		return $aReturn;
	}

	public function GetMessages() {
		if ($this->oMessages === null) {
			IssueLog::Debug("Start loading messages collection for $this->sServer ", static::LOG_CHANNEL);

			// Use the query API and disable body/attachment/flags for the listing
			$this->oMessages = $this->GetFolder()
				->messages()
				->all()
				->setFetchBody(false)
				->leaveUnread()
				->get();

			IssueLog::Debug("Loaded messages collection for $this->sServer ", static::LOG_CHANNEL);
		}
		return $this->oMessages;
	}

	public function GetFolder() {
		if( $this->oFolder === null ) {
			$this->oFolder = $this->oClient->getFolderByPath($this->sMailbox);
		}
		return $this->oFolder;
	}

	/**
	 * Move the message of the given index [0..Count] from the mailbox to another folder
	 *
	 * @param $index integer The index between zero and count
	 */
	public function MoveMessage($index)
	{
		$iOffsetIndex = 1 + $index;
		IssueLog::Debug(__METHOD__." Start: $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);
		try {
			$oMessages = $this->GetMessages(); // header-only cached collection
			$oMessage = $oMessages->get($index);

			if (!$oMessage) {
				return false;
			}
		}
		catch (Exception $e) {
			IssueLog::Error(__METHOD__." $iOffsetIndex for $this->sServer throws an exception", static::LOG_CHANNEL, [
				'exception.message' => $e->getMessage(),
				'exception.stack'   => $e->getTraceAsString(),
			]);

			return false;
		}

		$oMessage->move($this->sTargetFolder);
		IssueLog::Debug(__METHOD__." End: $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);

		return true;
	}

	public function Disconnect()
	{
		$this->oClient->disconnect();
	}

	public function GetMailbox()
	{
		return $this->sMailbox;
	}
}
