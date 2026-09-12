<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (Company $company): void {
            $policy = app(\App\Services\FiscalDocumentPolicyService::class);
            if (! $policy->companyHasPermanentFiscalHistory($company)) return;

            $invoices = \App\Models\SalesInvoice::with('company')->where('company_id', $company->id)->get();
            foreach ($invoices as $invoice) {
                if (! $policy->wasFiscallyIssued($invoice)) continue;
                app(\App\Services\FiscalDocumentAuditService::class)
                    ->recordBlockedSalesInvoiceAction($invoice, 'company_delete', auth()->id(), 'company.model.delete');
            }
            throw new \RuntimeException(\App\Services\FiscalDocumentPolicyService::ISSUED_INVOICE_MUTATION_MESSAGE);
        });
    }

    protected $fillable = [
        'company_name',
        'mobile',
        'email',
        'status',
        'telephone',
        'fax_no',
        'website',
        'address',
        'address_line_2',
        'country',
        'country_id',
        'language',
        'pan_number',
        'vat_number',
        'logo_path',
        'signature_path',
        'selected_user_limit',
        'expiry_date',
        'selected_customer_limit'
    ];
    public function countryMaster()
    {
        return $this->belongsTo(Country::class, 'country_id');
    }
    public function financialYears()
{

    return $this->hasMany(
        FinancialYear::class
    );

}

    public function subscriptions()
    {
        return $this->hasMany(CompanySubscription::class);
    }

    public function activeSubscription()
    {
        return $this->hasOne(CompanySubscription::class)->where('status', 'active')->latestOfMany();
    }

    public function staffPermissions()
    {
        return $this->belongsToMany(Permission::class, 'company_permission');
    }

    public function whatsappSetting()
    {
        return $this->hasOne(CompanyWhatsappSetting::class);
    }

    public function irdCbmsSetting()
    {
        return $this->hasOne(CompanyIrdCbmsSetting::class);
    }

    public function taxSetting()
    {
        return $this->hasOne(CompanyTaxSetting::class);
    }
    public function cbmsApiConfiguration() { return $this->hasOne(CompanyCbmsApiConfiguration::class); }
    public function cbmsTransmissions() { return $this->hasMany(CbmsTransmission::class); }
}
