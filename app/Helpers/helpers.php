<?php

if (!function_exists('numberToWords'))
{
    function numberToWords($number)
    {
        $number = floor($number);

        $ones = array(
            '',
            'one',
            'two',
            'three',
            'four',
            'five',
            'six',
            'seven',
            'eight',
            'nine',
            'ten',
            'eleven',
            'twelve',
            'thirteen',
            'fourteen',
            'fifteen',
            'sixteen',
            'seventeen',
            'eighteen',
            'nineteen'
        );

        $tens = array(
            '',
            '',
            'twenty',
            'thirty',
            'forty',
            'fifty',
            'sixty',
            'seventy',
            'eighty',
            'ninety'
        );

        if ($number < 20)
        {
            return ucfirst($ones[$number]) . ' only';
        }

        if ($number < 100)
        {
            return ucfirst(
                $tens[floor($number / 10)] . ' ' .
                $ones[$number % 10]
            ) . ' only';
        }

        if ($number < 1000)
        {
            return ucfirst(
                $ones[floor($number / 100)] .
                ' hundred ' .
                numberToWords($number % 100)
            );
        }

        if ($number < 1000000)
        {
            return ucfirst(
                numberToWords(floor($number / 1000)) .
                ' thousand ' .
                numberToWords($number % 1000)
            );
        }

        return number_format($number, 0) . ' only';
    }
}