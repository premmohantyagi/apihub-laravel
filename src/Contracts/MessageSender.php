<?php

namespace ApiHub\Laravel\Contracts;

use ApiHub\Laravel\Messaging\DTO\MessageResult;
use ApiHub\Laravel\Messaging\DTO\TextMessage;

/**
 * The unified interface every SMS / messaging driver implements.
 */
interface MessageSender
{
    public function send(TextMessage $message): MessageResult;
}
