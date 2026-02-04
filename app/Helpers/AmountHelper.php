<?php

namespace App\Helpers;

class AmountHelper
{
    public static function toWords($number)
    {
        $no = floor($number);
        $point = round($number - $no, 2) * 100;

        $words = [
            0 => '',
            1 => 'one',
            2 => 'two',
            3 => 'three',
            4 => 'four',
            5 => 'five',
            6 => 'six',
            7 => 'seven',
            8 => 'eight',
            9 => 'nine',
            10 => 'ten',
            11 => 'eleven',
            12 => 'twelve',
            13 => 'thirteen',
            14 => 'fourteen',
            15 => 'fifteen',
            16 => 'sixteen',
            17 => 'seventeen',
            18 => 'eighteen',
            19 => 'nineteen',
            20 => 'twenty',
            30 => 'thirty',
            40 => 'forty',
            50 => 'fifty',
            60 => 'sixty',
            70 => 'seventy',
            80 => 'eighty',
            90 => 'ninety'
        ];

        $digits = ['', 'hundred', 'thousand', 'lakh', 'crore'];

        $str = [];
        $i = 0;

        while ($no > 0) {
            $divider = ($i == 1) ? 10 : 100;
            $number = $no % $divider;
            $no = floor($no / $divider);

            if ($number) {
                $plural = (($counter = count($str)) && $number > 9) ? 's' : null;
                $hundred = ($counter == 1 && $str[0]) ? ' and ' : null;

                if ($number < 21) {
                    $str[] = $words[$number] . ' ' . $digits[$i] . $plural . $hundred;
                } else {
                    $str[] = $words[floor($number / 10) * 10] . ' ' .
                        $words[$number % 10] . ' ' .
                        $digits[$i] . $plural . $hundred;
                }
            } else {
                $str[] = null;
            }

            $i++;
        }

        $result = implode('', array_reverse($str));

        return ucfirst(trim($result)) . ' only';
    }
}
