<?php

namespace App\Services;

class ValidationService
{
    /**
     * Image Validation
     */
    public static function image(
        int $max = 5120
    ): array
    {
        return [
            'nullable',
            'image',
            'mimes:jpg,jpeg,png',
            'max:' . $max,
        ];
    }

    /**
     * Required Image Validation
     */
    public static function requiredImage(
        int $max = 5120
    ): array
    {
        return [
            'required',
            'image',
            'mimes:jpg,jpeg,png',
            'max:' . $max,
        ];
    }

    /**
     * Document Validation
     */
    public static function document(
        int $max = 10240
    ): array
    {
        return [
            'nullable',
            'file',
            'mimes:pdf,jpg,jpeg,png',
            'max:' . $max,
        ];
    }

    /**
     * Required Document Validation
     */
    public static function requiredDocument(
        int $max = 10240
    ): array
    {
        return [
            'required',
            'file',
            'mimes:pdf,jpg,jpeg,png',
            'max:' . $max,
        ];
    }

    /**
     * Phone Validation
     */
    public static function phone(): string
    {
        return 'nullable|string|max:20';
    }

    /**
     * Email Validation
     */
    public static function email(): string
    {
        return 'nullable|email|max:255';
    }

    /**
     * Amount Validation
     */
    public static function amount(): string
    {
        return 'nullable|numeric|min:0';
    }


public static function requiredAmount(): string
{
    return 'required|numeric|min:0';
}

/**
 * Date Validation
 */
public static function date(): string
{
    return 'nullable|date';
}

/**
 * Required Date Validation
 */
public static function requiredDate(): string
{
    return 'required|date';
}

public static function requiredPhone(): string
{
    return 'required|string|max:20';
}

public static function requiredEmail(): string
{
    return 'required|email|max:255';
}
}