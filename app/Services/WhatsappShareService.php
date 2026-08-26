<?php

namespace App\Services;

use App\Models\Company;
use App\Models\CompanyWhatsappSetting;
use App\Models\Customer;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Illuminate\Validation\ValidationException;

class WhatsappShareService
{
    public function isEnabled(Company $company): bool
    {
        return CompanyWhatsappSetting::query()
            ->where('company_id', $company->id)
            ->where('is_enabled', true)
            ->exists();
    }

    public function customerShareUrl(Company $company, Customer $customer, string|int|float $approvedCurrentBalance): string
    {
        if ((int) $customer->company_id !== (int) $company->id) {
            abort(404);
        }

        $this->assertEnabled($company);

        $recipient = $this->normalizeRecipient((string) $customer->mobile, $company->countryMaster?->iso_code);
        $message = $this->customerMessage($company, $customer, $approvedCurrentBalance);

        return 'https://wa.me/'.$recipient.'?text='.rawurlencode($message);
    }

    public function salesInvoiceShareUrl(
        Company $company,
        SalesInvoice $invoice,
        string $approvedBusinessDate,
        string|int|float $approvedPaidAmount,
        string|int|float $approvedDueAmount
    ): string {
        $customer = $invoice->customer;
        $this->assertDocumentParty($company, $invoice->company_id, $customer?->company_id);
        $this->assertEnabled($company);

        $recipient = $this->normalizeRecipient((string) $customer->mobile, $company->countryMaster?->iso_code);
        $message = $this->invoiceMessage(
            $company,
            $customer->name,
            'Invoice No',
            $invoice->invoice_no,
            'Invoice Date',
            $approvedBusinessDate,
            $approvedPaidAmount,
            $approvedDueAmount
        );

        return 'https://wa.me/'.$recipient.'?text='.rawurlencode($message);
    }

    public function purchaseInvoiceShareUrl(
        Company $company,
        PurchaseInvoice $invoice,
        string $approvedBusinessDate,
        string|int|float $approvedPaidAmount,
        string|int|float $approvedDueAmount
    ): string {
        $supplier = $invoice->supplier;
        $this->assertDocumentParty($company, $invoice->company_id, $supplier?->company_id);
        $this->assertEnabled($company);

        $recipient = $this->normalizeRecipient((string) $supplier->mobile, $company->countryMaster?->iso_code);
        $message = $this->invoiceMessage(
            $company,
            $supplier->name,
            'Purchase No',
            $invoice->invoice_no,
            'Purchase Date',
            $approvedBusinessDate,
            $approvedPaidAmount,
            $approvedDueAmount
        );

        return 'https://wa.me/'.$recipient.'?text='.rawurlencode($message);
    }

    public function normalizeRecipient(string $number, ?string $countryIsoCode = null): string
    {
        $trimmed = trim($number);
        $digits = preg_replace('/\D+/', '', $trimmed) ?? '';

        if (str_starts_with($trimmed, '+')) {
            return $this->validateInternationalDigits($digits);
        }

        if (str_starts_with($digits, '00')) {
            return $this->validateInternationalDigits(substr($digits, 2));
        }

        if (strtoupper((string) $countryIsoCode) === 'NP' && preg_match('/^0?9\d{9}$/', $digits)) {
            return '977'.ltrim($digits, '0');
        }

        throw ValidationException::withMessages([
            'mobile' => 'Use an international mobile number beginning with + or 00. Nepal mobile numbers may use the local 98XXXXXXXX format.',
        ]);
    }

    private function validateInternationalDigits(string $digits): string
    {
        if (! preg_match('/^[1-9]\d{7,14}$/', $digits)) {
            throw ValidationException::withMessages(['mobile' => 'The recipient mobile number is invalid.']);
        }

        return $digits;
    }

    private function assertDocumentParty(Company $company, mixed $documentCompanyId, mixed $partyCompanyId): void
    {
        if ((int) $documentCompanyId !== (int) $company->id || (int) $partyCompanyId !== (int) $company->id) {
            abort(404);
        }
    }

    private function assertEnabled(Company $company): void
    {
        if (! $this->isEnabled($company)) {
            throw ValidationException::withMessages([
                'whatsapp' => 'WhatsApp Share is disabled for this company.',
            ]);
        }
    }

    private function customerMessage(Company $company, Customer $customer, string|int|float $approvedCurrentBalance): string
    {
        $currentBalance = number_format((float) $approvedCurrentBalance, 2);

        return "Hello {$customer->name},\n\n"
            ."Greetings from {$company->company_name}.\n\n"
            ."Current Balance : {$currentBalance}\n\n"
            ."Thank you for being our valued customer.\n\n"
            ."Regards,\n{$company->company_name}";
    }

    private function invoiceMessage(
        Company $company,
        string $partyName,
        string $numberLabel,
        string $documentNumber,
        string $dateLabel,
        string $approvedBusinessDate,
        string|int|float $approvedPaidAmount,
        string|int|float $approvedDueAmount
    ): string {
        $paidAmount = number_format((float) $approvedPaidAmount, 2);
        $dueAmount = number_format((float) $approvedDueAmount, 2);

        return "Hello {$partyName},\n\n"
            ."Greetings from {$company->company_name}.\n\n"
            ."{$numberLabel} : {$documentNumber}\n"
            ."{$dateLabel} : {$approvedBusinessDate}\n"
            ."Paid Amount : {$paidAmount}\n"
            ."Due Amount : {$dueAmount}\n\n"
            ."Thank you.\n\n"
            ."Regards,\n{$company->company_name}";
    }
}
