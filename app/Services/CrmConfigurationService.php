<?php

namespace App\Services;

use App\Models\CrmConfiguration;
use App\Models\FinancialYear;
use Carbon\Carbon;

class CrmConfigurationService
{
    /** @var array<string, array<int, array{key:string,label:string,terminal?:bool}>> */
    private const DEFAULTS = [
        CrmConfiguration::TYPE_LEAD_STATUS => [
            ['key' => 'active', 'label' => 'Active'],
            ['key' => 'on_hold', 'label' => 'On Hold'],
            ['key' => 'closed', 'label' => 'Closed', 'terminal' => true],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'terminal' => true],
            ['key' => 'archived', 'label' => 'Archived', 'terminal' => true],
        ],
        CrmConfiguration::TYPE_CONTACT_STATUS => [
            ['key' => 'active', 'label' => 'Active'],
            ['key' => 'inactive', 'label' => 'Inactive', 'terminal' => true],
            ['key' => 'closed', 'label' => 'Closed', 'terminal' => true],
            ['key' => 'archived', 'label' => 'Archived', 'terminal' => true],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'terminal' => true],
        ],
        CrmConfiguration::TYPE_OPPORTUNITY_STATUS => [
            ['key' => 'open', 'label' => 'Open'],
            ['key' => 'won', 'label' => 'Won', 'terminal' => true],
            ['key' => 'lost', 'label' => 'Lost', 'terminal' => true],
            ['key' => 'closed', 'label' => 'Closed', 'terminal' => true],
            ['key' => 'archived', 'label' => 'Archived', 'terminal' => true],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'terminal' => true],
        ],
        CrmConfiguration::TYPE_PRIORITY => [
            ['key' => 'low', 'label' => 'Low'],
            ['key' => 'normal', 'label' => 'Normal'],
            ['key' => 'high', 'label' => 'High'],
            ['key' => 'urgent', 'label' => 'Urgent'],
        ],
        CrmConfiguration::TYPE_OPPORTUNITY_STAGE => [
            ['key' => 'discovery', 'label' => 'Discovery'],
            ['key' => 'requirement', 'label' => 'Requirement'],
            ['key' => 'proposal', 'label' => 'Proposal'],
            ['key' => 'negotiation', 'label' => 'Negotiation'],
            ['key' => 'won', 'label' => 'Won'],
            ['key' => 'lost', 'label' => 'Lost'],
        ],
        CrmConfiguration::TYPE_FOLLOW_UP_STATUS => [
            ['key' => 'pending', 'label' => 'Pending'],
            ['key' => 'in_progress', 'label' => 'In Progress'],
            ['key' => 'completed', 'label' => 'Completed', 'terminal' => true],
            ['key' => 'archived', 'label' => 'Archived', 'terminal' => true],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'terminal' => true],
        ],
        CrmConfiguration::TYPE_TASK_TYPE => [
            ['key' => 'call', 'label' => 'Call'],
            ['key' => 'meeting', 'label' => 'Meeting'],
            ['key' => 'email', 'label' => 'Email'],
            ['key' => 'visit', 'label' => 'Visit'],
            ['key' => 'reminder', 'label' => 'Reminder'],
            ['key' => 'deadline', 'label' => 'Deadline'],
        ],
        CrmConfiguration::TYPE_TASK_STATUS => [
            ['key' => 'pending', 'label' => 'Pending'],
            ['key' => 'in_progress', 'label' => 'In Progress'],
            ['key' => 'completed', 'label' => 'Completed', 'terminal' => true],
            ['key' => 'overdue', 'label' => 'Overdue'],
            ['key' => 'archived', 'label' => 'Archived', 'terminal' => true],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'terminal' => true],
        ],
        CrmConfiguration::TYPE_MEETING_STATUS => [
            ['key' => 'scheduled', 'label' => 'Scheduled'],
            ['key' => 'completed', 'label' => 'Completed', 'terminal' => true],
            ['key' => 'archived', 'label' => 'Archived', 'terminal' => true],
            ['key' => 'cancelled', 'label' => 'Cancelled', 'terminal' => true],
        ],
        CrmConfiguration::TYPE_NOTE_STATUS => [
            ['key' => 'active', 'label' => 'Active'],
            ['key' => 'archived', 'label' => 'Archived', 'terminal' => true],
        ],
    ];

    public function ensureDefaults(int $companyId): void
    {
        foreach (self::DEFAULTS as $type => $items) {
            foreach ($items as $index => $item) {
                CrmConfiguration::firstOrCreate(
                    [
                        'company_id' => $companyId,
                        'config_type' => $type,
                        'config_key' => $item['key'],
                    ],
                    [
                        'config_label' => $item['label'],
                        'sort_order' => $index + 1,
                        'is_active' => true,
                        'created_by' => auth()->id(),
                    ]
                );
            }
        }
    }

    public function options(int $companyId, string $type): \Illuminate\Support\Collection
    {
        $this->ensureDefaults($companyId);

        return CrmConfiguration::where('company_id', $companyId)
            ->where('config_type', $type)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    public function label(int $companyId, string $type, ?string $key): string
    {
        if (!$key) {
            return '-';
        }

        $option = $this->options($companyId, $type)->firstWhere('config_key', $key);

        return $option?->config_label ?? ucfirst(str_replace('_', ' ', $key));
    }

    public function validateKey(int $companyId, string $type, string $key): bool
    {
        return $this->options($companyId, $type)->contains('config_key', $key);
    }

    public function keys(int $companyId, string $type): array
    {
        return $this->options($companyId, $type)->pluck('config_key')->all();
    }

    public function terminalKeys(int $companyId, string $type): array
    {
        $defaults = collect(self::DEFAULTS[$type] ?? [])
            ->filter(fn (array $item) => !empty($item['terminal']))
            ->pluck('key')
            ->all();

        return array_values(array_intersect($this->keys($companyId, $type), $defaults));
    }

    public function activeKeys(int $companyId, string $type): array
    {
        return array_values(array_diff($this->keys($companyId, $type), $this->terminalKeys($companyId, $type)));
    }

    public function requireKey(int $companyId, string $type, string $semanticKey): string
    {
        $this->ensureDefaults($companyId);

        if (!$this->validateKey($companyId, $type, $semanticKey)) {
            throw new \Exception("CRM configuration '$semanticKey' is not available for this company.");
        }

        return $semanticKey;
    }

    public function resolveDefaultRelationshipStatus(int $companyId): string
    {
        $this->ensureDefaults($companyId);

        if ($this->validateKey($companyId, CrmConfiguration::TYPE_LEAD_STATUS, 'active')) {
            return 'active';
        }

        $activeKeys = $this->activeKeys($companyId, CrmConfiguration::TYPE_LEAD_STATUS);

        return $activeKeys[0] ?? 'active';
    }

    public function assertDateWithinActiveFinancialYear(FinancialYear $activeFy, string $date, string $message = null): void
    {
        $parsed = Carbon::parse($date);
        $startDate = Carbon::parse($activeFy->start_date);
        $endDate = Carbon::parse($activeFy->end_date);

        if ($parsed->lt($startDate) || $parsed->gt($endDate)) {
            throw new \Exception($message ?? 'Selected date must fall within the active financial year.');
        }
    }
}
