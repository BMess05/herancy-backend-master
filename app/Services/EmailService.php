<?php
namespace App\Services;
use App\Mail\EmailConfirmation;
use Illuminate\Support\Facades\Mail;

class EmailService {
    protected $sendTo;
    protected $mailClass;

    public function setTo($send_to) : self {
        $this->sendTo = $send_to;
        return $this;
    }

    public function setHtml($mailClass) : self {
        $this->mailClass = $mailClass;
        return $this;
    }

    public function send() : bool {
        Mail::to($this->sendTo)->send($this->mailClass);
        return true;
    }
}

?>