<?php
declare(strict_types=1);

namespace BudgetSMS;

/**
 * Client for the BudgetSMS HTTP API (https://www.budgetsms.net/sms-http-api/).
 *
 * Every call is one HTTPS GET request; the API answers with "OK <result>" or "ERR <code>". This class turns
 * the answer into typed values and throws an ApiException with the error code on "ERR".
 *
 *   $sms = new \BudgetSMS\Client('username', 'userid', 'handle');
 *   $result = $sms->sendSms('31612345678', 'Hello from BudgetSMS', 'MyCompany');
 *   echo $result->smsId;
 */
final class Client
{
    public const DEFAULT_BASE_URL = 'https://api.budgetsms.net/';
    public const VERSION = '1.0.0';

    /** @var array{username: string, userid: string, handle: string} */
    private array $auth;
    private string $baseUrl;
    private int $timeout;

    public function __construct(string $username, string $userid, string $handle, string $baseUrl = self::DEFAULT_BASE_URL, int $timeout = 20)
    {
        $this->auth = ['username' => $username, 'userid' => $userid, 'handle' => $handle];
        $this->baseUrl = rtrim($baseUrl, '/') . '/';
        $this->timeout = $timeout;
    }

    /**
     * Sends an SMS. The receiver is written in international format without + or 00 (31612345678); the sender is an
     * alphanumeric name of at most 11 characters or a number of at most 16 digits.
     * @param array{customid?: string} $options customid: your own id that may be used only once (refused with ERR 2014 when reused)
     */
    public function sendSms(string $to, string $message, string $from, array $options = []): SendResult
    {
        return $this->submit('sendsms', $to, $message, $from, $options);
    }

    /** Same as sendSms, but nothing is sent and nothing is charged: validates the call and returns the price. */
    public function testSms(string $to, string $message, string $from, array $options = []): SendResult
    {
        return $this->submit('testsms', $to, $message, $from, $options);
    }

    /** Current credit of the account in euro. */
    public function checkCredit(): float
    {
        return (float) $this->call('checkcredit');
    }

    /** Number portability lookup: the network a mobile number currently belongs to, also when ported. Charged at your NP price. */
    public function np(string $number): Lookup
    {
        return Lookup::fromResponse($this->call('np', ['to' => $number]));
    }

    /**
     * HLR lookup: like np, and also tells whether the handset is reachable. Charged at your HLR price; a result from the
     * cache (at most $cacheDays old, 0 to 7) costs half.
     */
    public function hlr(string $number, int $cacheDays = 0): Lookup
    {
        return Lookup::fromResponse($this->call('hlr', $cacheDays > 0 ? ['to' => $number, 'cache' => (string) $cacheDays] : ['to' => $number]));
    }

    /** Delivery status of a message (Pull DLR): 1 = delivered, see https://www.budgetsms.net/sms-http-api/dlr-status/ */
    public function dlrStatus(int $smsId): int
    {
        return (int) $this->call('checksms', ['smsid' => (string) $smsId]);
    }

    /**
     * Your price per SMS part for every operator, keyed by operator id:
     * [countryprefix, countryname, mcc, operatorname, mnc, price, old_price, last_modified]
     * @return array<int|string, array<string, string>>
     */
    public function pricing(): array
    {
        $body = $this->request('getpricing', []);
        if (strpos($body, 'ERR') === 0) {
            throw ApiException::fromResponse($body);
        }
        $data = json_decode($body, true);
        if (!is_array($data)) {
            throw new ApiException('Unexpected pricing response', 'invalid-json');
        }
        return $data;
    }

    /**
     * Calls any endpoint and returns the text after "OK ". Throws ApiException on "ERR <code>" or a transport problem.
     * @param array<string, string> $params
     */
    public function call(string $endpoint, array $params = []): string
    {
        $body = $this->request($endpoint, $params);
        if (strpos($body, 'OK') !== 0) {
            throw ApiException::fromResponse($body);
        }
        return trim(substr($body, 2));
    }

    private function submit(string $endpoint, string $to, string $message, string $from, array $options): SendResult
    {
        $params = ['from' => $from, 'to' => $to, 'msg' => $message, 'price' => '1', 'mccmnc' => '1', 'credit' => '1'];
        if (isset($options['customid']) && $options['customid'] !== '') {
            $params['customid'] = (string) $options['customid'];
        }
        return SendResult::fromResponse($this->call($endpoint, $params));
    }

    /** @param array<string, string> $params */
    private function request(string $endpoint, array $params): string
    {
        $url = $this->baseUrl . $endpoint . '/?' . http_build_query($this->auth + $params, '', '&', PHP_QUERY_RFC3986);
        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_CONNECTTIMEOUT => 5, CURLOPT_TIMEOUT => $this->timeout,
                CURLOPT_USERAGENT => 'budgetsms-php/' . self::VERSION,
            ]);
            $body = curl_exec($ch);
            if ($body === false) {
                throw new ApiException('BudgetSMS unreachable: ' . curl_error($ch), 'transport');
            }
            return trim((string) $body);
        }
        $context = stream_context_create(['http' => ['timeout' => $this->timeout, 'user_agent' => 'budgetsms-php/' . self::VERSION, 'ignore_errors' => true]]);
        $body = @file_get_contents($url, false, $context);
        if ($body === false) {
            throw new ApiException('BudgetSMS unreachable', 'transport');
        }
        return trim($body);
    }
}
