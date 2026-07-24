<x-mail::message>
# Delivery Completed

Dear {{ $customerName }},

Your delivery **{{ $deliveryNote->delivery_no }}** has been completed on {{ $deliveryNote->completed_at?->format('d-m-Y H:i') ?? now()->format('d-m-Y H:i') }}.

**Sales Invoice:** {{ $deliveryNote->salesInvoice->invoice_no ?? '-' }}  
**Delivery Employee:** {{ $deliveryNote->employee->full_name ?? '-' }}

Please find the official delivery note PDF attached to this email.

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
