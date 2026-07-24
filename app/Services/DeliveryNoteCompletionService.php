<?php

namespace App\Services;

use App\Mail\DeliveryNoteCompletedMail;
use App\Models\DeliveryAttachment;
use App\Models\DeliveryNote;
use App\Models\DeliveryNoteItem;
use App\Models\DeliverySignature;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Services\Concerns\GuardsSubscriptionModule;

class DeliveryNoteCompletionService
{
    use GuardsSubscriptionModule;
    public function __construct(
        private DeliveryNoteService $deliveryNoteService
    ) {
    }

    /**
     * @param array<int, array<string, mixed>> $itemRows
     */
    public function complete(DeliveryNote $deliveryNote, Request $request, array $itemRows): DeliveryNote
    {
        $this->assertSubscriptionModule((int) $deliveryNote->company_id, 'delivery');

        if (!$deliveryNote->isProcessable()) {
            throw new \Exception('This delivery note cannot be completed.');
        }

        $companyId = (int) $deliveryNote->company_id;
        $deliveryNote->loadMissing(['items', 'customer', 'employee', 'salesInvoice']);

        $hasDeliveredLine = false;
        $allPlannedDelivered = true;
        $itemsById = $deliveryNote->items->keyBy('id');

        foreach ($itemRows as $row) {
            $itemId = (int) ($row['delivery_note_item_id'] ?? 0);
            $selected = !empty($row['selected']);
            $deliveredQty = round((float) ($row['delivered_qty'] ?? 0), 2);

            if (!$selected || $deliveredQty <= 0 || !$itemsById->has($itemId)) {
                continue;
            }

            /** @var DeliveryNoteItem $item */
            $item = $itemsById->get($itemId);
            $plannedQty = round((float) $item->planned_qty, 2);

            if ($deliveredQty > $plannedQty) {
                throw new \Exception(
                    'Delivered quantity exceeds planned quantity for '
                    . $this->deliveryNoteService->resolveItemName($item)
                    . '. Maximum allowed: '
                    . number_format($plannedQty, 2)
                    . '.'
                );
            }

            $item->update([
                'delivered_qty' => $deliveredQty,
                'updated_by' => auth()->id(),
            ]);

            $hasDeliveredLine = true;

            if ($deliveredQty < $plannedQty) {
                $allPlannedDelivered = false;
            }
        }

        foreach ($deliveryNote->items as $item) {
            $plannedQty = round((float) $item->planned_qty, 2);
            $deliveredQty = round((float) $item->fresh()->delivered_qty, 2);

            if ($plannedQty > 0 && $deliveredQty < $plannedQty) {
                $allPlannedDelivered = false;
            }
        }

        if (!$hasDeliveredLine) {
            throw new \Exception('Select at least one item with delivered quantity greater than zero.');
        }

        $previousStatus = $deliveryNote->status;
        $finalStatus = $allPlannedDelivered
            ? DeliveryNote::STATUS_DELIVERED
            : DeliveryNote::STATUS_PARTIAL;

        $storagePath = $this->deliveryNoteService->ensureDeliveryStorageDirectory(
            $companyId,
            (int) $deliveryNote->id
        );

        $this->storePhotos($deliveryNote, $request, $storagePath);
        $this->storeSignature($deliveryNote, $request, $storagePath);

        $deliveryNote->update([
            'status' => $finalStatus,
            'completed_by' => auth()->id(),
            'completed_at' => now(),
            'updated_by' => auth()->id(),
        ]);

        $this->deliveryNoteService->recordStatusHistory(
            $deliveryNote,
            $previousStatus,
            $finalStatus,
            'Delivery completed by employee with proof of delivery.'
        );

        $pdfRelativePath = $this->generateAndStorePdf($deliveryNote, $storagePath);
        $deliveryNote->update(['pdf_path' => $pdfRelativePath]);

        $this->sendCustomerEmail($deliveryNote->fresh([
            'customer',
            'employee',
            'salesInvoice',
            'items.product',
            'items.service',
            'signature',
            'attachments',
        ]));

        return $deliveryNote->fresh();
    }

    private function storePhotos(DeliveryNote $deliveryNote, Request $request, string $storagePath): void
    {
        $companyId = (int) $deliveryNote->company_id;

        $photoMap = [
            'photo_1' => DeliveryAttachment::TYPE_PHOTO,
            'photo_2' => DeliveryAttachment::TYPE_ADDITIONAL_PHOTO,
        ];

        foreach ($photoMap as $field => $documentType) {
            if (!$request->hasFile($field)) {
                continue;
            }

            /** @var UploadedFile $file */
            $file = $request->file($field);
            $filename = $documentType . '_' . time() . '_' . Str::random(6) . '.' . $file->getClientOriginalExtension();
            $file->move($storagePath, $filename);

            DeliveryAttachment::create([
                'company_id' => $companyId,
                'delivery_note_id' => $deliveryNote->id,
                'document_type' => $documentType,
                'file_path' => $filename,
                'original_name' => $file->getClientOriginalName(),
                'created_by' => auth()->id(),
            ]);
        }
    }

    private function storeSignature(DeliveryNote $deliveryNote, Request $request, string $storagePath): void
    {
        $companyId = (int) $deliveryNote->company_id;
        $signatureData = trim((string) $request->input('signature_data', ''));

        if ($signatureData === '') {
            throw new \Exception('Customer signature is required.');
        }

        if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $signatureData, $matches)) {
            throw new \Exception('Invalid signature format.');
        }

        $extension = $matches[1] === 'jpeg' || $matches[1] === 'jpg' ? 'jpg' : 'png';
        $binary = base64_decode(substr($signatureData, strpos($signatureData, ',') + 1));

        if ($binary === false) {
            throw new \Exception('Unable to read signature image.');
        }

        $filename = 'signature_' . time() . '.' . $extension;
        file_put_contents($storagePath . DIRECTORY_SEPARATOR . $filename, $binary);

        DeliverySignature::create([
            'company_id' => $companyId,
            'delivery_note_id' => $deliveryNote->id,
            'customer_name' => $deliveryNote->customer->name ?? null,
            'receiver_name' => trim((string) $request->input('receiver_name')),
            'receiver_mobile' => trim((string) $request->input('receiver_mobile')),
            'signature_path' => $filename,
            'created_by' => auth()->id(),
        ]);
    }

    private function generateAndStorePdf(DeliveryNote $deliveryNote, string $storagePath): string
    {
        $companyId = (int) $deliveryNote->company_id;
        $lineItems = $this->deliveryNoteService->buildShowLineItems($companyId, $deliveryNote);
        $company = auth()->user()->company;

        $pdf = Pdf::loadView('company.delivery-notes.pdf', compact(
            'deliveryNote',
            'lineItems',
            'company'
        ))->setPaper('a4');

        $filename = 'delivery_' . $deliveryNote->delivery_no . '.pdf';
        $fullPath = $storagePath . DIRECTORY_SEPARATOR . $filename;
        $pdf->save($fullPath);

        DeliveryAttachment::create([
            'company_id' => $companyId,
            'delivery_note_id' => $deliveryNote->id,
            'document_type' => DeliveryAttachment::TYPE_PDF,
            'file_path' => $filename,
            'original_name' => $filename,
            'created_by' => auth()->id(),
        ]);

        return 'deliveries/' . $deliveryNote->id . '/' . $filename;
    }

    private function sendCustomerEmail(DeliveryNote $deliveryNote): void
    {
        $email = trim((string) ($deliveryNote->customer->email ?? ''));

        if ($email === '') {
            return;
        }

        Mail::to($email)->send(new DeliveryNoteCompletedMail($deliveryNote));
    }
}
