# BudgetSMS PHP client

Official PHP client for the [BudgetSMS HTTP API](https://www.budgetsms.net/sms-http-api/): send SMS to 900+ operators worldwide, test calls without sending, check your credit, look up the network of a number (NP and HLR), fetch delivery statuses and your pricing.

- PHP 7.4 or newer, no dependencies (uses cURL when available, `file_get_contents` otherwise)
- One class, typed results, an exception with the API error code

## Install

```bash
composer require budgetsms/budgetsms-php
```

## Use

```php
use BudgetSMS\Client;
use BudgetSMS\ApiException;

$sms = new Client('USERNAME', 'USERID', 'HANDLE');   // from the control panel, API details

try {
    $result = $sms->sendSms('31612345678', 'Your verification code is 482913', 'MyCompany');
    echo "sent, message id {$result->smsId}, price {$result->price} for {$result->parts} part(s), credit left {$result->credit}\n";
} catch (ApiException $e) {
    echo $e->getMessage(), "\n";     // "BudgetSMS error 1001: Not enough credit"
    echo $e->apiCode, "\n";          // "1001"
}
```

While developing, use `testSms()` with the same arguments. It is a simulator: your credentials are checked and the result has the same shape, but nothing is sent or charged, the message id and price are random, and about one in five calls throws a random `2xxx` error so you can test your error handling. Real prices come from `pricing()`.

```php
$test = $sms->testSms('31612345678', 'Hello', 'MyCompany');

$credit = $sms->checkCredit();                 // 123.455

$network = $sms->np('31612345678');            // current network, also for ported numbers
echo $network->mcc, ' ', $network->mnc, ' ', $network->price;

$hlr = $sms->hlr('31612345678', 3);            // like np, plus reachability; a result up to 3 days old from the cache costs half

$status = $sms->dlrStatus($result->smsId);     // 1 = delivered, see https://www.budgetsms.net/sms-http-api/dlr-status/

foreach ($sms->pricing() as $operatorId => $row) {
    echo $row['countryname'], ' ', $row['operatorname'], ' ', $row['price'], "\n";
}
```

Numbers are written in international format without a leading `+` or `00`: `31612345678`. The sender is an alphanumeric name of at most 11 characters or a number of at most 16 digits; some countries require registration of alphanumeric senders, see the country pages on [budgetsms.net](https://www.budgetsms.net/sms-gateway-pricing/).

## Delivery reports

Prefer [Push DLR](https://www.budgetsms.net/sms-http-api/push-dlr/): enter a URL in the control panel and BudgetSMS calls it with `id`, `status` and `date` on every status change. `dlrStatus()` is the pull variant for an occasional check.

## Errors

`ApiException` carries the API error code in `$e->apiCode` and a readable message. The full list is at https://www.budgetsms.net/sms-http-api/error-code/. Transport problems use the code `transport`.

## License

MIT
