<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class StudentFeedbackMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $studentName,
        public string $institutionName,
        public string $messageText,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Como podemos apoiar você, '.$this->studentName.'?',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.student-feedback',
            with: [
                'studentName' => $this->studentName,
                'institutionName' => $this->institutionName,
                'messageText' => $this->messageText,
            ],
        );
    }
}
