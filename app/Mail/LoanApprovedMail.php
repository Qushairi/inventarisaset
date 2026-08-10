<?php

namespace App\Mail;

use App\Models\Loan;
use App\Models\SuratPeminjaman;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoanApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Loan $loan,
        public mixed $suratPeminjaman = null,
        public ?string $pdfBinary = null
    ) {
    }

    public function envelope(): Envelope
    {
        $docNumber = $this->suratPeminjaman?->number ?? ('#' . $this->loan->id);

        return new Envelope(
            subject: '[DISDIK BENGKALIS] Pengajuan Peminjaman Aset Disetujui - ' . $docNumber,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.loan-approved',
        );
    }

    public function attachments(): array
    {
        if (! $this->pdfBinary) {
            return [];
        }

        $docNumber = $this->suratPeminjaman?->number ?? ('SPA-' . $this->loan->id);
        $filename = 'Surat_Peminjaman_Aset_' . str_replace(['/', '\\', ' '], '_', $docNumber) . '.pdf';

        return [
            Attachment::fromData(fn () => $this->pdfBinary, $filename)
                ->withMime('application/pdf'),
        ];
    }
}
