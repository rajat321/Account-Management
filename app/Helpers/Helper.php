<?php 

namespace App\Helpers;

class Helper
{
    public static function generateAccountNumber()
    {
        $number = mt_rand(100000000000, 999999999999);
        $checkDigit = self::calculateCheckDigit($number);
        return $number . $checkDigit;
    }

    public static function calculateCheckDigit($number)
    {
        $digits = str_split(strrev($number));
        $sum = 0;

        foreach ($digits as $index => $digit) {
            $digit = (int)$digit;
            if ($index % 2 == 0) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }
        return (10 - ($sum % 10)) % 10;
    }

    public static function isValid($number)
    {
        return self::calculateCheckDigit(substr($number, 0, -1)) == substr($number, -1);
    }
}
