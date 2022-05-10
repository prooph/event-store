<?php

/**
 * This file is part of prooph/event-store.
 * (c) 2014-2022 Alexander Miertsch <kontakt@codeliner.ws>
 * (c) 2015-2022 Sascha-Oliver Prolic <saschaprolic@googlemail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Prooph\EventStore\Transport\Http;

class HttpStatusCode
{
    // INFORMATIONAL CODES
    public const Continue = 100;

    public const SwitchingProtocols = 101;

    public const Processing = 102;

    public const EarlyHints = 103;

    // SUCCESS CODES
    public const Ok = 200;

    public const Created = 201;

    public const Accepted = 202;

    public const NonAuthoritativeInformation = 203;

    public const NoContent = 204;

    public const ResetContent = 205;

    public const PartialContent = 206;

    public const MultiStatus = 207;

    public const AlreadyReported = 208;

    public const ImUsed = 226;

    // REDIRECTION CODES
    public const MultipleChoices = 300;

    public const MovedPermanently = 301;

    public const Found = 302;

    public const SeeOther = 303;

    public const NotModified = 304;

    public const UseProxy = 305;

    public const SwitchProxy = 306; // Deprecated to 306 => '(Unused)'

    public const TemporaryRedirect = 307;

    public const PermanentRedirect = 308;

    // CLIENT ERROR
    public const BadRequest = 400;

    public const Unauthorized = 401;

    public const PaymentRequired = 402;

    public const Forbidden = 403;

    public const NotFound = 404;

    public const MethodNotAllowed = 405;

    public const NotAcceptable = 406;

    public const ProxyAuthenticationRequired = 407;

    public const RequestTimeout = 408;

    public const Conflict = 409;

    public const Gone = 410;

    public const LengthRequired = 411;

    public const PreconditionFailed = 412;

    public const PayloadTooLarge = 413;

    public const UriTooLong = 414;

    public const UnsupportedMediaType = 415;

    public const RangeNotSatisfiable = 416;

    public const ExpectationFailed = 417;

    public const ImATeapot = 418;

    public const MisdirectedRequest = 421;

    public const UnprocessableEntity = 422;

    public const Locked = 423;

    public const FailedDependency = 424;

    public const TooEarly = 425;

    public const UpgradeRequired = 426;

    public const PreconditionRequired = 428;

    public const TooManyRequests = 429;

    public const RequestHeaderFieldsTooLarge = 431;

    public const ConnectionClosedWithoutResponse = 444;

    public const UnavailableForLegalReasons = 451;

    // SERVER ERROR
    public const ClientClosedRequest = 499;

    public const InternalServerError = 500;

    public const NotImplemented = 501;

    public const BadGateway = 502;

    public const ServiceUnavailable = 503;

    public const GatewayTimeout = 504;

    public const HttpVersionNotSupported = 505;

    public const VariantAlsoNegotiates = 506;

    public const InsufficientStorage = 507;

    public const LoopDetected = 508;

    public const NotExtended = 510;

    public const NetworkAuthenticationRequired = 511;

    public const NetworkConnectTimeoutError = 599;
}
