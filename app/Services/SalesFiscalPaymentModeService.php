<?php

namespace App\Services;

use App\Models\Account;
use App\Models\Company;
use App\Models\SalesInvoice;

class SalesFiscalPaymentModeService
{
    public const CREDIT = 'credit';
    public const CASH = 'cash';
    public const BANK = 'bank';
    public const DIGITAL = 'digital';
    public const MIXED = 'mixed';
    public const OTHER = 'other';

    public function __construct(private readonly NepalIrdCbmsModeService $mode)
    {
    }

    public function issuanceAttributes(
        Company $company,
        float $paidAmount,
        float $grandTotal,
        ?int $accountId
    ): array {
        if (! $this->mode->isActiveForCompany($company)) {
            return [];
        }

        if ($paidAmount <= 0) {
            $paymentMode = self::CREDIT;
        } elseif ($paidAmount < $grandTotal) {
            $this->authoritativeAccount($company, $accountId);
            $paymentMode = self::MIXED;
        } else {
            $paymentMode = $this->modeForAccount($this->authoritativeAccount($company, $accountId));
        }

        return [
            'fiscal_payment_mode' => $paymentMode,
            'fiscal_payment_mode_captured_at' => now(),
        ];
    }

    public function isComplete(SalesInvoice $invoice): bool
    {
        return $this->readinessErrors($invoice) === [];
    }

    public function readinessErrors(SalesInvoice $invoice): array
    {
        if ($invoice->fiscal_payment_mode_captured_at === null || $invoice->fiscal_payment_mode === null) {
            return ['The sales invoice has no authoritative fiscal payment mode snapshot.'];
        }

        if ($this->irdPresentation($invoice->fiscal_payment_mode) === null) {
            return ['The sales invoice contains an unsupported fiscal payment mode snapshot.'];
        }

        return [];
    }

    public function irdPresentation(?string $mode): ?array
    {
        return match ($mode) {
            self::CASH => ['category' => 'Cash', 'detail' => null, 'display' => 'Cash'],
            self::CREDIT => ['category' => 'Credit', 'detail' => null, 'display' => 'Credit'],
            self::BANK => ['category' => 'Other', 'detail' => 'Bank Transfer', 'display' => 'Other (Bank Transfer)'],
            self::DIGITAL => ['category' => 'Other', 'detail' => 'Digital Payment', 'display' => 'Other (Digital Payment)'],
            self::MIXED => ['category' => 'Other', 'detail' => 'Mixed', 'display' => 'Other (Mixed)'],
            self::OTHER => ['category' => 'Other', 'detail' => null, 'display' => 'Other'],
            default => null,
        };
    }

    public function canonicalModes(): array
    {
        return [self::CREDIT, self::CASH, self::BANK, self::DIGITAL, self::MIXED, self::OTHER];
    }

    private function authoritativeAccount(Company $company, ?int $accountId): Account
    {
        return Account::where('company_id', $company->id)
            ->where('status', 'active')
            ->findOrFail($accountId);
    }

    private function modeForAccount(Account $account): string
    {
        return match (strtolower(trim((string) $account->account_type))) {
            'cash' => self::CASH,
            'bank', 'atm' => self::BANK,
            'wallet' => self::DIGITAL,
            default => self::OTHER,
        };
    }
}
