<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ApprovalStatusMail extends Mailable
{
    use Queueable, SerializesModels;

    public string $recipientName;
    public string $title;
    public string $messageText;
    public ?string $href;
    public ?string $reason;

    public function __construct(string $recipientName, string $title, string $messageText, ?string $href = null, ?string $reason = null)
    {
        $this->recipientName = $recipientName;
        $this->title = $title;
        $this->messageText = $messageText;
        $this->href = $href;
        $this->reason = $reason;
    }

    public function build()
    {
        $view = $this->reason ? 'emails.approval_rejected' : 'emails.approval_approved';

        return $this->subject($this->title)
            ->view($view);
    }
}
