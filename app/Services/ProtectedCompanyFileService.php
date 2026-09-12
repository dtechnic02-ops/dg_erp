<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProtectedCompanyFileService
{
    /** @var array<string, array{model: class-string<Model>, fields: array<string>, permission: string}> */
    public const RESOURCES = [
        'employee' => ['model' => \App\Models\EmployeeAccount::class, 'fields' => ['photo', 'cv_attachment', 'id_document', 'contract_document'], 'permission' => 'employee.view'],
        'employee-payment' => ['model' => \App\Models\EmployeePayment::class, 'fields' => ['attachment'], 'permission' => 'salary.payment.view'],
        'expense' => ['model' => \App\Models\Expense::class, 'fields' => ['attachment'], 'permission' => 'view_expense'],
        'income' => ['model' => \App\Models\Income::class, 'fields' => ['attachment'], 'permission' => 'view_income'],
        'contra' => ['model' => \App\Models\Contra::class, 'fields' => ['attachment'], 'permission' => 'view_accounts'],
        'journal' => ['model' => \App\Models\Journal::class, 'fields' => ['attachment'], 'permission' => 'view_journal'],
        'party-account' => ['model' => \App\Models\PartyAccount::class, 'fields' => ['photo', 'id_card', 'document'], 'permission' => 'view_loan_account'],
        'loan-account' => ['model' => \App\Models\LoanAccount::class, 'fields' => ['attachment'], 'permission' => 'view_loan_account'],
        'loan-payment' => ['model' => \App\Models\LoanPayment::class, 'fields' => ['attachment'], 'permission' => 'view_loan_payment'],
        'loan-saving-ledger' => ['model' => \App\Models\LoanSavingLedger::class, 'fields' => ['attachment'], 'permission' => 'view_loan_saving_ledger'],
        'purchase-payment' => ['model' => \App\Models\PurchasePayment::class, 'fields' => ['receipt_file'], 'permission' => 'view_purchase'],
        'sales-payment' => ['model' => \App\Models\SalesPayment::class, 'fields' => ['receipt_file'], 'permission' => 'view_sales_payment'],
        'purchase-return' => ['model' => \App\Models\PurchaseReturn::class, 'fields' => ['damage_photo'], 'permission' => 'view_purchase'],
        'sales-return' => ['model' => \App\Models\SalesReturn::class, 'fields' => ['damage_photo'], 'permission' => 'view_sales'],
        'purchase-return-refund' => ['model' => \App\Models\PurchaseReturnRefund::class, 'fields' => ['attachment'], 'permission' => 'view_purchase'],
        'sales-return-refund' => ['model' => \App\Models\SalesReturnRefund::class, 'fields' => ['attachment'], 'permission' => 'view_sales'],
        'delivery-note' => ['model' => \App\Models\DeliveryNote::class, 'fields' => ['pdf_path'], 'permission' => 'view_delivery'],
        'delivery-attachment' => ['model' => \App\Models\DeliveryAttachment::class, 'fields' => ['file_path'], 'permission' => 'view_delivery'],
        'delivery-signature' => ['model' => \App\Models\DeliverySignature::class, 'fields' => ['signature_path'], 'permission' => 'view_delivery'],
        'customer' => ['model' => \App\Models\Customer::class, 'fields' => ['image_path'], 'permission' => 'view_customer'],
    ];

    public function response(string $type, int $id, string $field, bool $download = false): BinaryFileResponse
    {
        $definition = self::RESOURCES[$type] ?? null;
        abort_unless($definition && in_array($field, $definition['fields'], true), 404);

        $user = auth()->user();
        abort_unless($user && $user->hasPermission($definition['permission'], $user->company_id), 403);

        /** @var Model|null $record */
        $record = $definition['model']::query()
            ->where('company_id', (int) $user->company_id)
            ->find($id);
        abort_unless($record, 404);

        $storedPath = (string) $record->getAttribute($field);
        if ($type === 'delivery-note') {
            $storedPath = 'companies/'.(int) $record->company_id.'/'.$storedPath;
        } elseif (in_array($type, ['delivery-attachment', 'delivery-signature'], true)) {
            $storedPath = 'companies/'.(int) $record->company_id.'/deliveries/'.(int) $record->delivery_note_id.'/'.$storedPath;
        }
        abort_if($storedPath === '' || $this->isUnsafePath($storedPath), 404);
        abort_unless(str_starts_with(str_replace('\\', '/', $storedPath), 'companies/'.(int) $user->company_id.'/'), 404);

        $privatePath = FileUploadService::privatePath($storedPath);
        $absolute = Storage::disk('local')->path($privatePath);

        // Read-only compatibility for files awaiting the controlled migration command.
        if (!is_file($absolute)) {
            $absolute = public_path($storedPath);
        }
        if (!is_file($absolute)) {
            $absolute = Storage::disk('public')->path($storedPath);
        }
        abort_unless(is_file($absolute), 404);

        $name = basename($storedPath);
        return $download ? response()->download($absolute, $name) : response()->file($absolute);
    }

    public function isUnsafePath(string $path): bool
    {
        $normalized = str_replace('\\', '/', trim($path));
        return $normalized === '' || str_contains($normalized, "\0") || str_contains($normalized, '../')
            || str_starts_with($normalized, '/') || preg_match('/^[A-Za-z]:\//', $normalized) === 1;
    }
}
