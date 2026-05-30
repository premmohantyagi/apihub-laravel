<?php

namespace ApiHub\Laravel\Exceptions;

/** Raised on 401/403 — missing, invalid or insufficient credentials. */
class AuthenticationException extends ApiHubException {}
