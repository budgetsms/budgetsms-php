<?php
declare(strict_types=1);

namespace BudgetSMS;

/**
 * Thrown when the API answers "ERR <code>" (see https://www.budgetsms.net/sms-http-api/error-code/) or when the API
 * cannot be reached (code "transport").
 */
final class ApiException extends \RuntimeException
{
    public const MESSAGES = [
        '1001' => 'Not enough credit', '1002' => 'Wrong credentials', '1003' => 'Account not active', '1004' => 'IP address not allowed for this account',
        '1005' => 'No handle provided', '1006' => 'No userid provided', '1007' => 'No username provided',
        '2001' => 'Message text is empty', '2002' => 'Numeric sender id longer than 16 digits', '2003' => 'Alphanumeric sender id longer than 11 characters',
        '2004' => 'Sender id empty or invalid', '2005' => 'Destination number too short', '2006' => 'Destination not numeric', '2007' => 'Destination empty',
        '2008' => 'Message text not OK (encoding)', '2009' => 'Parameter issue', '2010' => 'Destination formatted invalidly', '2011' => 'Destination invalid',
        '2012' => 'Message text too long', '2013' => 'Message invalid', '2014' => 'Custom id used before', '2015' => 'Charset problem', '2016' => 'Invalid UTF-8',
        '2017' => 'Invalid sms id', '3001' => 'No route to destination', '3002' => 'No routes set up', '3003' => 'Invalid destination',
        '4001' => 'System error related to custom id', '4002' => 'Temporary system error, retry in a few minutes', '4003' => 'Lookup failed at the carrier, nothing charged',
        '4004' => 'Temporary system error', '4005' => 'Permanent system error', '4006' => 'Gateway not reachable', '4007' => 'System error',
        '5001' => 'Send error', '5002' => 'Wrong SMS type', '5003' => 'Wrong operator', '6001' => 'Unknown error',
        '7001' => 'No HLR provider present', '7002' => 'Unexpected result from HLR provider', '7003' => 'Bad number format',
        '7901' => 'Unexpected error', '7902' => 'HLR provider error', '7903' => 'HLR provider error',
    ];

    /** The API error code ("1001"), or "transport" / "invalid-json" for problems before the API answered. */
    public string $apiCode;

    public function __construct(string $message, string $apiCode)
    {
        parent::__construct($message);
        $this->apiCode = $apiCode;
    }

    public static function fromResponse(string $body): self
    {
        $code = trim(substr($body, 3));
        if (strpos($body, 'ERR') !== 0 || $code === '') {
            return new self('Unexpected response: ' . $body, 'unexpected');
        }
        return new self('BudgetSMS error ' . $code . (isset(self::MESSAGES[$code]) ? ': ' . self::MESSAGES[$code] : ''), $code);
    }
}
