<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class TransactionMail extends Mailable
{
    use Queueable, SerializesModels;
    protected $data;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        if(isset($this->data['mail_type'])) {
            if($this->data['mail_type'] == "request_toreceiver") {
                return $this->from(env('MAIL_FROM_ADDRESS'), 'Harency')
                ->view('emails.transaction_request_to_receiver', ['data' => $this->data]);
            } elseif($this->data['mail_type'] == "request_tosender") {
                return $this->from(env('MAIL_FROM_ADDRESS'), 'Harency')
                ->view('emails.transaction_request_to_sender', ['data' => $this->data]);
            } elseif($this->data['mail_type'] == "payment_toreceiver") {
                return $this->from(env('MAIL_FROM_ADDRESS'), 'Harency')
                ->view('emails.transaction_payment_to_receiver', ['data' => $this->data]);
            }

        }
    }
}
