<?php

namespace App\Services\Cbms;

use App\Models\CbmsTransmission;
use App\Models\CompanyCbmsApiConfiguration;

class CbmsTransmissionProvenanceService
{
    public function __construct(private readonly CbmsHttpTransport $transport) {}

    public function forCompany(int $companyId): array
    {
        $environment = CompanyCbmsApiConfiguration::query()
            ->where('company_id', $companyId)
            ->value('environment');

        if (! in_array($environment, CbmsTransmission::ENVIRONMENTS, true)) {
            $environment = CbmsTransmission::ENVIRONMENT_LEGACY;
        }

        $transportKind = $this->transport->kind();
        if (! in_array($transportKind, CbmsTransmission::TRANSPORT_KINDS, true)) {
            $transportKind = CbmsTransmission::TRANSPORT_LEGACY;
        }

        return ['environment' => $environment, 'transport_kind' => $transportKind];
    }

    public function matches(CbmsTransmission $transmission): bool
    {
        return $this->forCompany((int) $transmission->company_id) === [
            'environment' => $transmission->environment,
            'transport_kind' => $transmission->transport_kind,
        ];
    }
}
