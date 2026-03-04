<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DocumentNotification extends Mailable
{
    use SerializesModels;

    public $document;
    public $recipientName;
    public $link;

    /**
     * Create a new message instance.
     */
    public function __construct($document, $recipientName, $link)
    {
        $this->document = $document;
        $this->recipientName = $recipientName;
        $this->link = $link;
    }

    /**
     * Build the message.
     */
    public function build()
    {
        return $this->subject('New Document in Your DMS Inbox: ' . $this->document->tracking_code)
            ->view('emails.document-notification');
    }
}
