<?php
/*
 * @copyright   Copyright (C) 2010-2024 Combodo SAS
 * @license     http://opensource.org/licenses/AGPL-3.0
 */

namespace Combodo\iTop\Extension\Helper;

class MessageHelper
{
	public static function GetMessageId($oMessage) {
		return $oMessage->getHeader('Message-ID', 'string');
	}
}