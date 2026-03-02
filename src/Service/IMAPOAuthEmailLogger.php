<?php

namespace Combodo\iTop\Extension\OAuthEmailSynchro\Service;

use Combodo\iTop\Extension\EmailSynchro\Service\IMAPEmailLogger;

class IMAPOAuthEmailLogger extends IMAPEmailLogger
{
	public const LOG_CHANNEL = 'OAuth';
}
