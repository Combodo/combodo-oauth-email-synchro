<?php
namespace Combodo\iTop\Extension\Service;
use Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProviderAbstract;
use Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProviderFactory;
use Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProviderGoogle;
use Combodo\iTop\Extension\Helper\ProviderHelper;
use EmailSource;
use IssueLog;
use Laminas\Mail\Protocol\Imap;
use MessageFromMailbox;
use MetaModel;

class IMAPOAuthEmailSource extends EmailSource{
	/**
	 * LOGIN username
	 *
	 * @var IMAPOAuthLogin
	 */
	protected $oTransport;
	protected $sLogin;
	/**
	 * 	 * @var IMAPOAuthStorage
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
		$sProtocol = 'ssl';//$oMailbox->Get('protocol');
		$sServer = $oMailbox->Get('server');
		$sPwd = $oMailbox->Get('password');
		$sLogin = $oMailbox->Get('login');
		$this->sLogin = $sLogin;
		$sMailbox = $oMailbox->Get('mailbox');
		$iPort = $oMailbox->Get('port');

		// Always IMAP with oAuth
		$aImapOptions = MetaModel::GetModuleSetting('combodo-email-synchro', 'imap_options', array('imap'));
		//$oTransport = new IMAPOAuthLogin($sServer, $iPort);
		$this->oStorage = new IMAPOAuthStorage([
			'user' => $sLogin,
			'host' => $sServer,
			'port' => $iPort,
			'ssl' => $sProtocol,
			'folder' => $sMailbox,
			'provider' => ProviderHelper::getProviderForIMAP($oMailbox)
		]);
		IssueLog::Info('Fin creation Email Source');
		//$this->oStorage->setProvider(\ProviderHelper::getProviderForIMAP($oMailbox));

		// Call parent with original arguments
		parent::__construct($sServer, $iPort, null);
	}
	
	public function GetMessagesCount()
	{
		IssueLog::Info('Début GetMessagesCount');
		$c = $this->oStorage->countMessages();
		IssueLog::Info('Fin GetMessagesCount');
		return $c;
		
	}

	public function GetMessage($index)
	{
		$iOffsetIndex = 1 + $index;
		IssueLog::Info('Début GetMessage de '.$iOffsetIndex);
		$oMail =  $this->oStorage->getMessage((int) $iOffsetIndex);
		$oNewMail = new MessageFromMailbox($this->oStorage->getUniqueId($iOffsetIndex), $oMail->getHeaders()->toString(), $oMail->getContent());
		IssueLog::Info('Fin GetMessage de '.$iOffsetIndex);
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
		$iIndex = 0;
		IssueLog::Info('Début GetListing');

		foreach ($this->oStorage as $iMessageId => $oMessage) {
			IssueLog::Info('GetListing de '.$iMessageId);
			$aReturn[] = ['msg_id' => $iMessageId, 'uidl' => $this->oStorage->getUniqueId($iMessageId)];
		}
		IssueLog::Info('Fin GetListing');
		return $aReturn;
	}

	public function Disconnect()
	{
		$this->oStorage->logout();
	}
}
