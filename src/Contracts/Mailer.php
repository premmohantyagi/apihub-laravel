<?php

namespace ApiHub\Laravel\Contracts;

use ApiHub\Laravel\Email\DTO\EmailMessage;
use ApiHub\Laravel\Email\DTO\SendResult;

/**
 * The unified interface every email driver implements.
 */
interface Mailer
{
    public function send(EmailMessage $message): SendResult;
}
