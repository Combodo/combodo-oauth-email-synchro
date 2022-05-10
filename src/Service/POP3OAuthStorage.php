<?php

namespace Combodo\iTop\Extension\Service;


use Laminas\Mail\Storage\Exception\ExceptionInterface;
use Laminas\Mail\Storage\Exception\InvalidArgumentException;
use Laminas\Mail\Storage\Exception\RuntimeException;
use Laminas\Mail\Storage\Pop3;

class POP3OAuthStorage extends Pop3
{

	public function __construct($params)
	{
		if (is_array($params)) {
			$params = (object)$params;
		}

		$this->has['flags'] = true;

		if ($params instanceof POP3OAuthLogin) {
			$this->protocol = $params;
			try {
				$this->selectFolder('INBOX');
			}
			catch (ExceptionInterface $e) {
				throw new  RuntimeException('cannot select INBOX, is this a valid transport?', 0, $e);
			}

			return;
		}

		if (!isset($params->user)) {
			throw new  InvalidArgumentException('POP3OAuthStorage need at least user in params');
		}

		$host = isset($params->host) ? $params->host : 'localhost';
		$password = isset($params->password) ? $params->password : '';
		$port = isset($params->port) ? $params->port : null;
		$ssl = isset($params->ssl) ? $params->ssl : false;

		$this->protocol = new POP3OAuthLogin($params->provider);

		if (isset($params->novalidatecert)) {
			$this->protocol->setNoValidateCert((bool)$params->novalidatecert);
		}

		$this->protocol->connect($host, $port, $ssl);
		$this->protocol->login($params->user, $password);
	}

	public function logout()
	{
		$this->protocol->logout();
	}

}