<?php

namespace Combodo\iTop\Extension\Service;

use Combodo\iTop\Extension\Helper\ProviderHelper;
use EmailSource;
use IssueLog;
use MessageFromMailbox;
use MetaModel;

class IMAPOAuthEmailSource extends EmailSource
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
	 *     * @var IMAPOAuthStorage
	 */
	protected $oStorage;

	/**
	 * Constructor.
	 *
	 * @param $oMailbox
	 */
	public function __construct($oMailbox)
	{
		IssueLog::Info('Debut creation Email Source');
		$sProtocol = $oMailbox->Get('protocol');
		$sServer = $oMailbox->Get('server');
		$this->sServer = $sServer;
		$sLogin = $oMailbox->Get('login');
		$this->sLogin = $sLogin;
		$sMailbox = $oMailbox->Get('mailbox');
		$iPort = $oMailbox->Get('port');

		// Always IMAP with oAuth
		$aImapOptions = MetaModel::GetModuleSetting('combodo-email-synchro', 'imap_options', array('imap'));
		$this->oStorage = new IMAPOAuthStorage([
			'user'     => $sLogin,
			'host'     => $sServer,
			'port'     => $iPort,
			'ssl'      => $sProtocol,
			'folder'   => $sMailbox,
			'provider' => ProviderHelper::getProviderForIMAP($oMailbox),
		]);
		IssueLog::Info('Fin creation Email Source');

		// Calls parent with original arguments
		parent::__construct();
	}

	public function GetMessagesCount()
	{
		IssueLog::Debug("IMAPOAuthEmailSource Start GetMessagesCount for $this->sServer", static::LOG_CHANNEL);
		$c = $this->oStorage->countMessages();
		IssueLog::Debug("IMAPOAuthEmailSource End GetMessagesCount for $this->sServer", static::LOG_CHANNEL);

		return $c;

	}

	public function GetMessage($index)
	{
		$iOffsetIndex = 1 + $index;
		IssueLog::Debug("IMAPOAuthEmailSource Start GetMessage $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);
		$oMail = $this->oStorage->getMessage($iOffsetIndex);
		$oNewMail = new MessageFromMailbox($this->oStorage->getUniqueId($iOffsetIndex), $oMail->getHeaders()->toString(), $oMail->getContent());
		IssueLog::Debug("IMAPOAuthEmailSource End GetMessage $iOffsetIndex for $this->sServer", static::LOG_CHANNEL);

		return $oNewMail;
	}

	public function DeleteMessage($index)
	{
		$this->oStorage->removeMessage($index);
	}

	public function GetName()
	{
		return $this->sLogin;
	}

	public function GetListing()
	{
		$aReturn = [];

		foreach ($this->oStorage as $iMessageId => $oMessage) {
			IssueLog::Debug("IMAPOAuthEmailSource GetListing $iMessageId for $this->sServer", static::LOG_CHANNEL);
			$aReturn[] = ['msg_id' => $iMessageId, 'uidl' => $this->oStorage->getUniqueId($iMessageId)];
		}

		return $aReturn;
	}

	public function Disconnect()
	{
		$this->oStorage->logout();
	}
}
