<?php

namespace App\Mail;

use App\Models\Repair;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RepairStatusNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $repair;
    public $isCompleted;

    /**
     * Create a new message instance.
     */
    public function __construct(Repair $repair, bool $isCompleted = false)
    {
        $this->repair = $repair;
        $this->isCompleted = $isCompleted;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $subject = $this->isCompleted 
            ? 'Su vehículo está listo para recoger - NTC Car Service' 
            : 'Estado de su reparación - NTC Car Service';

        return new Envelope(
            subject: $subject,
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        $view = $this->isCompleted 
            ? 'emails.repair-completed'
            : 'emails.repair-status';

        return new Content(
            view: $view,
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
