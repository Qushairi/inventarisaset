<?php

namespace App\Mail;

use App\Models\AssetReturn;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReturnVerifiedPegawaiMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public AssetReturn $assetReturn
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '[DISDIK BENGKALIS] Pengembalian Aset Telah Diverifikasi Admin',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.return-verified',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
