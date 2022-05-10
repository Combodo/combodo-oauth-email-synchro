<?php
namespace Combodo\iTop\Extension\Service;


use IssueLog;
use Laminas\Mail\Storage\Exception\ExceptionInterface;
use Laminas\Mail\Storage\Exception\InvalidArgumentException;
use Laminas\Mail\Storage\Exception\RuntimeException;

class IMAPOAuthStorage extends \Laminas\Mail\Storage\Imap{
	
	public function __construct($params){
		IssueLog::Info('Debut creation Storage');
		if (is_array($params)) {
			$params = (object) $params;
		}

		$this->has['flags'] = true;
		
		if ($params instanceof IMAPOAuthLogin) {
			$this->protocol = $params;
			try {
				$this->selectFolder('INBOX');
			} catch ( ExceptionInterface $e) {
				throw new  RuntimeException('cannot select INBOX, is this a valid transport?', 0, $e);
			}
			return;
		}

		if (! isset($params->user)) {
			throw new  InvalidArgumentException('need at least user in params');
		}

		$host     = isset($params->host) ? $params->host : 'localhost';
		$password = isset($params->password) ? $params->password : '';
		$port     = isset($params->port) ? $params->port : null;
		$ssl      = isset($params->ssl) ? $params->ssl : false;

		$this->protocol = new IMAPOAuthLogin($params->provider);

		if (isset($params->novalidatecert)) {
			$this->protocol->setNoValidateCert((bool)$params->novalidatecert);
		}

		$this->protocol->connect($host, $port, $ssl);
		if (! $this->protocol->login($params->user, $password)) {
			throw new  RuntimeException('cannot login, user or tokens');
		}
		$this->selectFolder(isset($params->folder) ? $params->folder : 'INBOX');
		IssueLog::Info('Fin creation Storage');
	}

	public function logout()
	{
		$this->protocol->logout();
	}

}