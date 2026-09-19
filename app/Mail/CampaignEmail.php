<?php

declare(strict_types=1);

namespace App\Mail;

use App\Models\Campaign;
use App\Models\CampaignRecipient;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CampaignEmail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Campaign $campaign,
        public CampaignRecipient $recipient,
        public string $renderedBody,
        public ?string $renderedSubject = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->renderedSubject ?: ($this->campaign->subject ?: 'SSHS Alumni Notification'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign',
        );
    }
}
