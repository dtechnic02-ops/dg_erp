<?php

namespace App\Http\Controllers\Concerns;

use App\Models\FinancialYear;
use App\Services\ValidationService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

trait HandlesTransactionDocumentationEdit
{
    protected function documentationEditRules(string $dateField): array
    {
        return [
            $dateField => ValidationService::requiredDate(),
            'note'     => ValidationService::text(),
        ];
    }

    protected function guardEditableTransaction(
        Model $model,
        string $cancelledMessage = 'Cancelled transaction cannot be edited.',
        int $cancelledStatus = 0
    ): void {
        if ((int) $model->status === $cancelledStatus) {
            throw new \Exception($cancelledMessage);
        }

        if (method_exists($model, 'trashed') && $model->trashed()) {
            throw new \Exception('Deleted transaction cannot be edited.');
        }
    }

    protected function guardActiveRefund(Model $refund, string $message): void
    {
        if (method_exists($refund, 'isActive') && !$refund->isActive()) {
            throw new \Exception($message);
        }
    }

    protected function assertActiveFinancialYear(int $companyId): FinancialYear
    {
        $activeFy = FinancialYear::where('company_id', $companyId)
            ->where('is_active', 1)
            ->first();

        if (!$activeFy) {
            throw new \Exception('Please activate financial year first.');
        }

        return $activeFy;
    }

    protected function assertTransactionFinancialYear(
        Model $model,
        FinancialYear $activeFy,
        string $message
    ): void {
        if ((int) $model->financial_year_id !== (int) $activeFy->id) {
            throw new \Exception($message);
        }
    }

    protected function assertFinancialYearIsActive(
        FinancialYear $financialYear,
        string $message = 'Inactive financial year cannot accept transactions.'
    ): void {
        if (!$financialYear->is_active) {
            throw new \Exception($message);
        }
    }

    protected function assertDateWithinFinancialYear(
        string $date,
        FinancialYear $activeFy,
        string $message = 'Selected date must fall within the active financial year.'
    ): void {
        $parsed = Carbon::parse($date);
        $startDate = Carbon::parse($activeFy->start_date);
        $endDate = Carbon::parse($activeFy->end_date);

        if ($parsed->lt($startDate) || $parsed->gt($endDate)) {
            throw new \Exception($message);
        }
    }

    protected function appendUpdatedBy(array $data, Model $model): array
    {
        if (in_array('updated_by', $model->getFillable(), true)) {
            $data['updated_by'] = auth()->id();
        }

        return $data;
    }

    protected function logDocumentationEdit(
        string $context,
        Model $model,
        array $extra = []
    ): void {
        Log::info($context, array_merge([
            'company_id' => auth()->user()->company_id ?? null,
            'user_id'    => auth()->id(),
            'model'      => get_class($model),
            'model_id'   => $model->id ?? null,
        ], $extra));
    }
}
