<?php

namespace App\Services;

use Illuminate\Validation\Rule;

/**
 * ValidationService
 *
 * MASTER validation rule library for the entire ERP.
 *
 * Every method is static, stateless, and returns plain
 * Laravel validation rules (array or pipe string), so the
 * same rule can be reused unchanged across:
 *
 * - ERP (Blade forms)
 * - API
 * - Mobile
 * - Future Modules
 *
 * Controllers must reuse these methods instead of writing
 * inline validation rules. Never duplicate a rule that
 * already exists here.
 */
class ValidationService
{
    /* =========================================================
        IMAGE
        ========================================================= */

    /**
     * Image Validation
     *
     * Optional image upload (jpg, jpeg, png).
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
     *
     * Mandatory image upload (jpg, jpeg, png).
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

    /* =========================================================
        DOCUMENT
        ========================================================= */

    /**
     * Document Validation
     *
     * Optional file upload (pdf, jpg, jpeg, png).
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
     *
     * Mandatory file upload (pdf, jpg, jpeg, png).
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

    /* =========================================================
        PHONE
        ========================================================= */

    /**
     * Phone Validation
     *
     * Optional phone number, up to 20 characters.
     */
    public static function phone(): string
    {
        return 'nullable|string|max:20';
    }

    /**
     * Required Phone Validation
     *
     * Mandatory phone number, up to 20 characters.
     */
    public static function requiredPhone(): string
    {
        return 'required|string|max:20';
    }

    /* =========================================================
        EMAIL
        ========================================================= */

    /**
     * Email Validation
     *
     * Optional, valid email format, up to 255 characters.
     */
    public static function email(): string
    {
        return 'nullable|email|max:255';
    }

    /**
     * Required Email Validation
     *
     * Mandatory, valid email format, up to 255 characters.
     */
    public static function requiredEmail(): string
    {
        return 'required|email|max:255';
    }

    /* =========================================================
        URL
        ========================================================= */

    /**
     * URL Validation
     *
     * Optional, valid URL format, up to 255 characters.
     * Used for Website fields (Customer, Supplier, Brand, etc.).
     */
    public static function url(): string
    {
        return 'nullable|url|max:255';
    }

    /**
     * Required URL Validation
     *
     * Mandatory, valid URL format, up to 255 characters.
     */
    public static function requiredUrl(): string
    {
        return 'required|url|max:255';
    }

    /* =========================================================
        NUMERIC (AMOUNT / QUANTITY)
        ========================================================= */

    /**
     * Amount Validation
     *
     * Optional non-negative numeric value.
     * Used for money fields (balance, price, payment, etc.).
     * Kept unchanged for backward compatibility.
     */
    public static function amount(): string
    {
        return 'nullable|numeric|min:0';
    }

    /**
     * Required Amount Validation
     *
     * Mandatory non-negative numeric value.
     * Kept unchanged for backward compatibility.
     */
    public static function requiredAmount(): string
    {
        return 'required|numeric|min:0';
    }

    /**
     * Quantity Validation
     *
     * Optional non-negative whole number.
     * Used for stock/quantity fields (current_stock, stock_alert,
     * opening_stock, etc.) as distinct from money amounts.
     */
    public static function quantity(): string
    {
        return 'nullable|integer|min:0';
    }

    /**
     * Required Quantity Validation
     *
     * Mandatory non-negative whole number.
     */
    public static function requiredQuantity(): string
    {
        return 'required|integer|min:0';
    }

    /* =========================================================
        DATE
        ========================================================= */

    /**
     * Date Validation
     *
     * Optional, valid date.
     */
    public static function date(): string
    {
        return 'nullable|date';
    }

    /**
     * Required Date Validation
     *
     * Mandatory, valid date.
     */
    public static function requiredDate(): string
    {
        return 'required|date';
    }

    /* =========================================================
        TEXT
        ========================================================= */

    /**
     * Short Text Validation
     *
     * Optional single-line text (name, title, label fields).
     */
    public static function string(
        int $max = 255
    ): string
    {
        return 'nullable|string|max:' . $max;
    }

    /**
     * Required Short Text Validation
     *
     * Mandatory single-line text (name, title, label fields).
     */
    public static function requiredString(
        int $max = 255
    ): string
    {
        return 'required|string|max:' . $max;
    }

    /**
     * Long Text Validation
     *
     * Optional multi-line text (description, note, address fields).
     */
    public static function text(
        int $max = 1000
    ): string
    {
        return 'nullable|string|max:' . $max;
    }

    /**
     * Required Long Text Validation
     *
     * Mandatory multi-line text (description, note, address fields).
     */
    public static function requiredText(
        int $max = 1000
    ): string
    {
        return 'required|string|max:' . $max;
    }

    /* =========================================================
        BOOLEAN
        ========================================================= */

    /**
     * Boolean Validation
     *
     * Optional true/false flag.
     */
    public static function boolean(): string
    {
        return 'nullable|boolean';
    }

    /**
     * Required Boolean Validation
     *
     * Mandatory true/false flag.
     */
    public static function requiredBoolean(): string
    {
        return 'required|boolean';
    }

    /* =========================================================
        ENUM

        Generic allowed-values validation.

        ValidationService validates DATA TYPES and ALLOWED
        VALUES only. It does not know what "status" or any
        other business field means — the caller supplies the
        list of values allowed for that specific field.

        Examples:

        ValidationService::requiredEnum(['active', 'inactive']);
        ValidationService::requiredEnum(['yes', 'no']);
        ValidationService::requiredEnum(['draft', 'approved', 'cancelled']);
        ========================================================= */

    /**
     * Enum Validation
     *
     * Optional value, restricted to the given list of
     * allowed values.
     *
     * @param array $values Allowed values for this field.
     */
    public static function enum(array $values): array
    {
        return [
            'nullable',
            Rule::in($values),
        ];
    }

    /**
     * Required Enum Validation
     *
     * Mandatory value, restricted to the given list of
     * allowed values.
     *
     * @param array $values Allowed values for this field.
     */
    public static function requiredEnum(array $values): array
    {
        return [
            'required',
            Rule::in($values),
        ];
    }

    /* =========================================================
        PRODUCT
        ========================================================= */

    /**
     * Barcode Validation
     *
     * Optional barcode, up to the given length.
     */
    public static function barcode(
        int $max = 100
    ): string
    {
        return 'nullable|string|max:' . $max;
    }

    /**
     * SKU Validation
     *
     * Optional SKU code, up to the given length.
     */
    public static function sku(
        int $max = 100
    ): string
    {
        return 'nullable|string|max:' . $max;
    }

    /* =========================================================
        UNIQUE
        ========================================================= */

    /**
     * Unique Per Company Validation
     *
     * Builds a Laravel Rule::unique() instance scoped to the
     * current company, so duplicate records are only blocked
     * within the same company (multi-tenant safe).
     *
     * Reused by every module that needs a company-scoped
     * unique column (Brand, Product, Category, Unit, etc.),
     * instead of hand-writing the same where()/ignore() block
     * in every controller.
     *
     * @param string   $table     Table name.
     * @param string   $column    Column to check for uniqueness.
     * @param int      $companyId Current company id to scope by.
     * @param int|null $ignoreId  Record id to ignore (update case).
     */
    public static function uniquePerCompany(
        string $table,
        string $column,
        int $companyId,
        ?int $ignoreId = null
    ) {
        $rule = Rule::unique($table, $column)
            ->where(
                function ($query) use ($companyId) {
                    $query->where('company_id', $companyId);
                }
            );

        if ($ignoreId) {
            $rule = $rule->ignore($ignoreId);
        }

        return $rule;
    }

    /* =========================================================
        CANCEL
        ========================================================= */

    /**
     * Cancel Date Validation
     *
     * Mandatory cancel date used by invoice/payment/return/refund
     * cancellation forms across Sales and Purchase modules.
     */
    public static function cancelDate(): string
    {
        return self::requiredDate();
    }

    /**
     * Cancel Reason Validation
     *
     * Mandatory cancel reason text.
     */
    public static function cancelReason(int $max = 500): string
    {
        return self::requiredString($max);
    }

    /* =========================================================
        COMPANY ISOLATION
        ========================================================= */

    /**
     * Exists For Company Validation
     *
     * Ensures a referenced record belongs to the current company.
     */
    public static function existsForCompany(
        string $table,
        int $companyId,
        string $column = 'id'
    ) {
        return Rule::exists($table, $column)->where(
            function ($query) use ($companyId) {
                $query->where('company_id', $companyId);
            }
        );
    }
}
