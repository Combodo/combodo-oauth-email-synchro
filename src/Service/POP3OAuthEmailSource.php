<?php

namespace Combodo\iTop\Extension\Service;

use Combodo\iTop\Extension\Helper\ProviderHelper;
use EmailSource;
use MessageFromMailbox;
use MetaModel;

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
		$sProtocol = 'ssl';//$oMailbox->Get('protocol');
		$sServer = $oMailbox->Get('server');
		$this->sServer = $sServer;
		$sLogin = $oMailbox->Get('login');
		$this->sLogin = $sLogin;
		$sMailbox = $oMailbox->Get('mailbox');
		$iPort = $oMailbox->Get('port');

		// Always POP3 with oAuth
		$aImapOptions = MetaModel::GetModuleSetting('combodo-email-synchro', 'imap_options', array('imap'));
		$this->oStorage = new POP3OAuthStorage([
			'user'     => $sLogin,
			'host'     => $sServer,
			'port'     => $iPort,
			'ssl'      => $sProtocol,
			'folder'   => $sMailbox,
			'provider' => ProviderHelper::getProviderForPOP3($oMailbox),
		]);

		// Call parent with original arguments
		parent::__construct();
	}

	public function GetMessagesCount()
	{
		return $this->oStorage->countMessages();
	}

	public function GetMessage($index)
	{
		$iOffsetIndex = 1 + $index;
		$oMail = $this->oStorage->getMessage($iOffsetIndex);

		return new MessageFromMailbox($this->oStorage->getUniqueId($iOffsetIndex), $oMail->getHeaders()->toString(), $oMail->getContent());
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
			$aReturn[] = ['msg_id' => $iMessageId, 'uidl' => $this->oStorage->getUniqueId($iMessageId)];
		}

		return $aReturn;
	}

	public function Disconnect()
	{
		$this->oStorage->logout();
	}
}
