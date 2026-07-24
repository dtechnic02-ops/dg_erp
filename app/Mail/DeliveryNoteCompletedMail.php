<?php

namespace App\Mail;

use App\Models\DeliveryNote;
use App\Services\DeliveryNoteService;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class DeliveryNoteCompletedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public DeliveryNote $deliveryNote
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Delivery Note ' . $this->deliveryNote->delivery_no,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.delivery-note-completed',
            with: [
                'deliveryNote' => $this->deliveryNote,
                'customerName' => $this->deliveryNote->customer->name ?? 'Customer',
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        if (!$this->deliveryNote->pdf_path) {
            return [];
        }

        $fullPath = public_path('companies/' . $this->deliveryNote->company_id . '/' . $this->deliveryNote->pdf_path);

        if (!is_file($fullPath)) {
            return [];
        }

        return [
            Attachment::fromPath($fullPath)
                ->as($this->deliveryNote->delivery_no . '.pdf')
                ->withMime('application/pdf'),
        ];
    }
}
