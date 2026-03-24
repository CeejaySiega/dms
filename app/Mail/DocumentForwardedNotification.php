<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentForwardedNotification extends Mailable
{
    use SerializesModels;

    public $document;
    public $senderName;
    public $forwarderName;
    public $newRecipientName;
    public $link;

    /**
     * Create a new message instance.
     */
    public function __construct($document, $senderName, $forwarderName, $newRecipientName, $link)
    {
        $this->document = $document;
        $this->senderName = $senderName;
        $this->forwarderName = $forwarderName;
        $this->newRecipientName = $newRecipientName;
        $this->link = $link;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Your Document Was Forwarded: ' . $this->document->tracking_code)
            ->view('emails.document-forwarded-notification');
    }
}
