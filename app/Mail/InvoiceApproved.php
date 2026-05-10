<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Queue\SerializesModels;

class InvoiceApproved extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public object $invoice,
        public object $company,
        public string $pdfPath
    ) {}

    public function envelope(): Envelope
	{
    	return new Envelope(
        	from: new \Illuminate\Mail\Mailables\Address('accounts@bluearrow.ae', $this->company->name),
        	replyTo: [
            	new \Illuminate\Mail\Mailables\Address('accounts@bluearrow.ae', $this->company->name),
        	],
        	subject: "Invoice {$this->invoice->invoice_number} — {$this->company->name}",
    	);
	}

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.invoice-approved',
            with: [
                'invoice' => $this->invoice,
                'company' => $this->company,
            ]
        );
    }

    public function attachments(): array
    {
        return [
            Attachment::fromPath($this->pdfPath)
                ->as("invoice_{$this->invoice->invoice_number}.pdf")
                ->withMime('application/pdf'),
        ];
    }
}