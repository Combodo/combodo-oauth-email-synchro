<?php
namespace Combodo\iTop\Extension\Service;
use Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProviderAbstract;
use Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProviderFactory;
use Combodo\iTop\Core\Authentication\Client\OAuth\OAuthClientProviderGoogle;
use Combodo\iTop\Extension\Helper\ProviderHelper;
use EmailSource;
use Laminas\Mail\Protocol\Imap;
use MessageFromMailbox;
use MetaModel;

class POP3OAuthEmailSource extends EmailSource{
	/**
	 * LOGIN username
	 *
	 * @var POP3OAuthLogin
	 */
	protected $oTransport;
	protected $sLogin;
	/**
	 * 	 * @var POP3OAuthStorage
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
		$sPwd = $oMailbox->Get('password');
		$sLogin = $oMailbox->Get('login');
		$this->sLogin = $sLogin;
		$sMailbox = $oMailbox->Get('mailbox');
		$iPort = $oMailbox->Get('port');

		// Always POP3 with oAuth
		$aImapOptions = MetaModel::GetModuleSetting('combodo-email-synchro', 'imap_options', array('imap'));
		//$oTransport = new IMAPOAuthLogin($sServer, $iPort);
		$this->oStorage = new POP3OAuthStorage([
			'user' => $sLogin,
			'host' => $sServer,
			'port' => $iPort,
			'ssl' => $sProtocol,
			'folder' => $sMailbox,
			'provider' => ProviderHelper::getProviderForPOP3($oMailbox)
		]);
		//$this->oStorage->setProvider(\ProviderHelper::getProviderForIMAP($oMailbox));

		// Call parent with original arguments
		parent::__construct($sServer, $iPort, null);
	}
	
	public function GetMessagesCount()
	{
		return $this->oStorage->countMessages();
	}

	public function GetMessage($index)
	{
		$iOffsetIndex = 1 + $index;
		$oMail =  $this->oStorage->getMessage((int) $iOffsetIndex);
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
		$iIndex = 0;
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
