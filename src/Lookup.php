<?php
declare(strict_types=1);

namespace BudgetSMS;

/** Result of an NP or HLR lookup: "OK <mcc> <mnc> <price>" */
final class Lookup
{
    /** Mobile country code of the network the number currently belongs to, for example "204". */
    public string $mcc;
    /** Mobile network code, for example "08". */
    public string $mnc;
    /** Your price for one SMS part to that network. */
    public float $price;

    public static function fromResponse(string $afterOk): self
    {
        $parts = preg_split('/\s+/', trim($afterOk)) ?: [];
        $l = new self();
        $l->mcc = $parts[0] ?? '';
        $l->mnc = $parts[1] ?? '';
        $l->price = (float) ($parts[2] ?? 0);
        return $l;
    }

    public function mccmnc(): string
    {
        return $this->mcc . $this->mnc;
    }
}
