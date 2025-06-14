<?php


namespace App\Support;

class HttpConstants
{
    public const HTTP_SUCCESS = 200;
    public const HTTP_CREATED = 201;
    public const HTTP_ACCEPTED = 202;
    public const HTTP_NO_CONTENT = 204;

    public const HTTP_BAD_REQUEST = 400;
    public const HTTP_UNAUTHENTICATED = 401;
    public const HTTP_PAYMENT_NEEDED = 402;
    public const HTTP_FORBIDDEN = 403;
    public const HTTP_NOT_FOUND = 404;
    public const HTTP_METHOD_NOT_ALLOWED = 405;
    public const HTTP_VALIDATION_ERROR = 422;
    public const HTTP_TOO_MANY_REQUESTS = 429;

    public const HTTP_SERVER_ERROR = 500;
    public const HTTP_SERVICE_UNAVAILABLE = 503;
}