<?php

namespace App\Http\Helpers;

use App\Enums\CurrencyEnum;

class FormatNumber {

    /**
     * @param  mixed   $value
     * @param  bool    $roundUp
     * @param  int     $decimal
     * @return ?string
     */
    public static function format(mixed $value, bool $roundUp = false, int $decimal = 0): ?string
    {
        $_value = $roundUp ? round($value, $decimal) : $value;

		return is_null($value)
            ?  0
            : (floatval($value) == intval($value) ? number_format($_value) : number_format($_value, 2));
	}

    /**
     * @param  mixed         $value
     * @param  CurrencyEnum  $to
     * @param  bool          $roundUp
     * @param  int           $decimal
     * @return string
     */
    public static function formatWithCurrency(mixed $value, CurrencyEnum $to = CurrencyEnum::GBP, bool $roundUp = false, int $decimal = 0): string
    {
		return abs($value) == $value ? $to->value.static::format($value, $roundUp, $decimal) : '-'.$to->value.static::format(abs($value), $roundUp, $decimal);
	}

    /**
     * Convert amount to and from cents
     * 
     * @param  float         $amount
     * @param  CurrencyEnum  $to
     * @param  bool          $prependSymbol
     * @param  bool          $roundUp
     * @param  int           $decimal
     * @return float|string
     */
    public static function convertAmount(float $amount, CurrencyEnum $to, bool $prependSymbol = false, bool $roundUp = false, int $decimal = 0): float|string
    {
        if ($to == CurrencyEnum::Cents) {
            $amount = $amount * 100;
        } else if ($to == CurrencyEnum::GBP) {
            $amount = $prependSymbol
                ? static::formatWithCurrency($amount / 100, CurrencyEnum::GBP, $roundUp, $decimal)
                : $amount / 100;
        }

        return $amount;
    }
}