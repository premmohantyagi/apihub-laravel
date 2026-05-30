<?php

namespace ApiHub\Laravel\Exceptions;

/** Raised on 429 — the provider's rate limit was exceeded. */
class RateLimitException extends ApiHubException {}
