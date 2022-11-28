<?php

namespace Combodo\iTop\Extension\Service;

use Combodo\iTop\Extension\Helper\ImapOptionsHelper;
use Combodo\iTop\Extension\Helper\MessageHelper;
use Combodo\iTop\Extension\Helper\ProviderHelper;
use EmailSource;
use Exception;
use IssueLog;
use MessageFromMailbox;

class IMAPOAuthEmailSource extends EmailSource
{
	const LOG_CHANNEL = 'OAuth';

	/** LOGIN username @var string */
	protected $sLogin;
	protected $sServer;
	/** * @var IMAPOAuthStorage */
	protected $oStorage;
	protected $sTargetFolder;
	protected $sMailbox;

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
		$this->oStorage = new IMAPOAuthStorage([
			'user'     => $sLogin,
			'host'     => $sServer,
			'port'     => $iPort,
			'ssl'      => $sSSL,
			'folder'   => $sMailbox,
			'provider' => ProviderHelper::getProviderForIMAP($oMailbox),
		]);
		IssueLog::Debug("IMAPOAuthEmailSource End for $this->sServer", static::LOG_CHANNEL);

		// Calls parent with original arguments
		parent::__construct();
	}

	public function GetMessagesCount()
	{
		IssueLog::Debug("IMAPOAuthEmailSource Start GetMessagesCount for $this->sServer", static::LOG_CHANNEL);
		$iCount = $this->oStorage->countMessages();
		IssueLog::Debug("IMAPOAuthEmailSource $iCount message(s) found for $this->sServer", static::LOG_CHANNEL);

		return $iCount;

	}

	public function GetMessage($index)
	{
		$iOffsetIndex = 1 + $index;

		$sUIDL = $this->oStorage->getUniqueId($iOffsetIndex);
		$sUIDLTrace = $sUIDL;
		IssueLog::Debug(__METHOD__." Start: $iOffsetIndex (UID $sUIDLTrace) for $this->sServer", static::LOG_CHANNEL);
		try {
			$oMail = $this->oStorage->getMessage($iOffsetIndex);
			$bUseMessageId = static::UseMessageIdAsUid();
			if ($bUseMessageId) {
				$sUIDL = MessageHelper::GetMessageId($oMail);
			}
		}
		catch (Exception $e) {
			IssueLog::Error(__METHOD__." $iOffsetIndex (UID $sUIDLTrace) for $this->sServer throws an exception", static::LOG_CHANNEL, [
				'exception.message' => $e->getMessage(),
				'exception.stack'   => $e->getTraceAsString(),
			]);

			return null;
		}
		$oNewMail = new MessageFromMailbox($sUIDL, $oMail->getHeaders()->toString(), $oMail->getContent());
		IssueLog::Debug(__METHOD__." End: $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);

		return $oNewMail;
	}

	public function DeleteMessage($index)
	{
		$this->oStorage->removeMessage(1 + $index);
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
		$iMessageCount = $this->oStorage->countMessages();

		if ($iMessageCount === 0) {
			IssueLog::Debug(__METHOD__." for {$this->sServer}: no messages", static::LOG_CHANNEL);

			return [];
		}

		// Iterates manually over the message iterator
		// We aren't using foreach as we need to catch each exception ! (N°5633)
		// We must iterate nevertheless for IMAPOAuthStorage::getUniqueId to work (will return a string during an iteration but an array if not iterating)
		$aReturn = [];
		$bUseMessageId = static::UseMessageIdAsUid();
		$this->oStorage->rewind();
		while ($this->oStorage->valid()) {
			$iMessageId = $this->oStorage->key();
			IssueLog::Debug(__METHOD__." messageId={$iMessageId} for $this->sServer", static::LOG_CHANNEL);
			try {
				$oMessage = $this->oStorage->current();
				if ($bUseMessageId) {
					$sMessageUidl = MessageHelper::GetMessageId($oMessage);
				} else {
					$sMessageUidl = $this->oStorage->getUniqueId($iMessageId);
				}
				$aReturn[] = ['msg_id' => $iMessageId, 'uidl' => $sMessageUidl];
			}
			catch (Exception $e) {
				IssueLog::Error(__METHOD__." messageId={$iMessageId} for {$this->sServer}: an exception occurred", static::LOG_CHANNEL, [
					'exception.message' => $e->getMessage(),
					'exception.stack'   => $e->getTraceAsString(),
				]);
				$aReturn[] = ['msg_id' => $iMessageId, 'uidl' => null];
				continue;
			}
			finally {
				$this->oStorage->next();
			}
		}

		return $aReturn;
	}

	/**
	 * Move the message of the given index [0..Count] from the mailbox to another folder
	 *
	 * @param $index integer The index between zero and count
	 */
	public function MoveMessage($index)
	{
		$this->oStorage->moveMessage(1 + $index, $this->sTargetFolder);

		return true;
	}

	public function Disconnect()
	{
		$this->oStorage->close();
	}

	public function GetMailbox()
	{
		return $this->sMailbox;
	}
}
