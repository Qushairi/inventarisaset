<?php

namespace App\Mail;

use App\Models\AssetReturn;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReturnRequestAdminMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AssetReturn $assetReturn
    ) {
    }

    public function envelope(): Envelope
    {
        $employeeName = $this->assetReturn->user?->name ?? 'Pegawai';

        return new Envelope(
            subject: '[DISDIK BENGKALIS] Pengajuan Pengembalian Aset Baru dari ' . $employeeName,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.admin.return-requested',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
