<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentReceivedNotification extends Mailable
{
    use SerializesModels;

    public $document;
    public $senderName;
    public $recipientName;
    public $link;

    /**
     * Create a new message instance.
     */
    public function __construct($document, $senderName, $recipientName, $link)
    {
        $this->document      = $document;
        $this->senderName    = $senderName;
        $this->recipientName = $recipientName;
        $this->link          = $link;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('Your Document Has Been Received: ' . $this->document->tracking_code)
            ->view('emails.document-received-notification');
    }
}
