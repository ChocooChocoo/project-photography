<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeRegistrationMail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Employee data.
     *
     * @var array
     */
    public $employeeData;

    /**
     * Temporary password.
     *
     * @var string
     */
    public $temporaryPassword;

    /**
     * Create a new message instance.
     */
    public function __construct(array $employeeData, string $temporaryPassword)
    {
        $this->employeeData = $employeeData;
        $this->temporaryPassword = $temporaryPassword;
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        $roleDisplay = $this->getRoleDisplay();
        
        return new Envelope(
            subject: "Your {$roleDisplay} Account Has Been Created - " . config('app.name'),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.employee-registration',
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

    /**
     * Get role display name.
     *
     * @return string
     */
    private function getRoleDisplay(): string
    {
        $roles = [
            'studio-hr' => 'Human Resource',
            'studio-finance' => 'Finance',
            'studio-photographer' => 'Studio Photographer',
        ];

        return $roles[$this->employeeData['role']] ?? 'Employee';
    }
}