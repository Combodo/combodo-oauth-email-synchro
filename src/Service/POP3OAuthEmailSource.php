<?php

namespace Combodo\iTop\Extension\Service;

use Combodo\iTop\Extension\Helper\ImapOptionsHelper;
use Combodo\iTop\Extension\Helper\ProviderHelper;
use EmailSource;
use IssueLog;
use MessageFromMailbox;

class POP3OAuthEmailSource extends EmailSource
{
	const LOG_CHANNEL = 'OAuth';

	/**
	 * LOGIN username
	 *
	 * @var string
	 */
	protected $sLogin;
	protected $sServer;
	/**
	 * @var POP3OAuthStorage
	 */
	protected $oStorage;

	/**
	 * Constructor.
	 *
	 * @param $oMailbox
	 */
	public function __construct($oMailbox)
	{
		$sServer = $oMailbox->Get('server');
		$this->sServer = $sServer;
		$sLogin = $oMailbox->Get('login');
		$this->sLogin = $sLogin;
		$sMailbox = $oMailbox->Get('mailbox');
		$iPort = $oMailbox->Get('port');

		IssueLog::Debug("POP3OAuthEmailSource Start for $this->sServer", static::LOG_CHANNEL);
		$oImapOptions = new ImapOptionsHelper();
		$sSSL = '';
		if ($oImapOptions->HasOption('ssl')) {
			$sSSL = 'ssl';
		} elseif ($oImapOptions->HasOption('tls')) {
			$sSSL = 'tls';
		}
		$this->oStorage = new POP3OAuthStorage([
			'user'     => $sLogin,
			'host'     => $sServer,
			'port'     => $iPort,
			'ssl'      => $sSSL,
			'folder'   => $sMailbox,
			'provider' => ProviderHelper::getProviderForPOP3($oMailbox),
		]);
		IssueLog::Debug("POP3OAuthEmailSource End for $this->sServer", static::LOG_CHANNEL);

		// Call parent with original arguments
		parent::__construct();
	}

	public function GetMessagesCount()
	{
		IssueLog::Debug("POP3OAuthEmailSource Start GetMessagesCount for $this->sServer", static::LOG_CHANNEL);
		$iCount = $this->oStorage->countMessages();
		IssueLog::Debug("POP3OAuthEmailSource $iCount message(s) found for $this->sServer", static::LOG_CHANNEL);

		return $iCount;
	}

	public function GetMessage($index)
	{
		$iOffsetIndex = 1 + $index;
		IssueLog::Debug("POP3OAuthEmailSource Start GetMessage $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);
		$oMail = $this->oStorage->getMessage($iOffsetIndex);
		$oNewMail = new MessageFromMailbox($this->oStorage->getUniqueId($iOffsetIndex), $oMail->getHeaders()->toString(), $oMail->getContent());
		IssueLog::Debug("POP3OAuthEmailSource End GetMessage $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);

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

	public function GetListing()
	{
		$aReturn = [];
		foreach ($this->oStorage as $iMessageId => $oMessage) {
			IssueLog::Debug("POP3OAuthEmailSource GetListing $iMessageId for $this->sServer", static::LOG_CHANNEL);
			$aReturn[] = ['msg_id' => $iMessageId, 'uidl' => $this->oStorage->getUniqueId($iMessageId)];
		}

		return $aReturn;
	}

	public function Disconnect()
	{
		$this->oStorage->logout();
	}
}
