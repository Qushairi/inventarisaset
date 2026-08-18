<?php

namespace App\Mail;

use App\Models\Loan;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LoanRequestAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Loan $loan
    ) {
    }

    public function envelope(): Envelope
    {
        $employeeName = $this->loan->user?->name ?? 'Pegawai';

        return new Envelope(
            subject: '[DISDIK BENGKALIS] Pengajuan Peminjaman Aset Baru dari ' . $employeeName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.loan-requested',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
