<?php
declare(strict_types=1);

namespace BudgetSMS;

/** Result of sendSms or testSms: "OK <smsid> <price> <parts> <mccmnc> <credit>" */
final class SendResult
{
    /** Message id; keep it to match the delivery report (Push DLR) or to ask the status (dlrStatus). */
    public int $smsId;
    /** Total price of the message in euro. */
    public ?float $price = null;
    /** Number of SMS parts the message was split into. */
    public ?int $parts = null;
    /** Mobile country code + mobile network code of the receiver's network, for example "20408". */
    public ?string $mccmnc = null;
    /** Remaining credit of the account after this message. */
    public ?float $credit = null;

    public static function fromResponse(string $afterOk): self
    {
        $parts = preg_split('/\s+/', trim($afterOk)) ?: [];
        $r = new self();
        $r->smsId = (int) ($parts[0] ?? 0);
        if (isset($parts[1]) && isset($parts[2])) {
            $r->price = (float) $parts[1];
            $r->parts = (int) $parts[2];
        }
        if (isset($parts[3])) {
            $r->mccmnc = $parts[3];
        }
        if (isset($parts[4])) {
            $r->credit = (float) $parts[4];
        }
        return $r;
    }
}
