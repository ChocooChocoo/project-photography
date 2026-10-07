<?php

namespace App\Services;

use App\Models\BookingContractModel;
use App\Models\BookingModel;
use App\Models\Freelancer\ProfileModel;
use App\Models\StudioOwner\StudiosModel;

class ContractService
{
    /**
     * Platform default used when a provider has not written a contract template.
     */
    public function defaultTemplate(): string
    {
        return <<<'TXT'
SERVICE CONTRACT

This contract is between {{provider_name}} (the "Provider") and {{client_name}} (the "Client") for the booking {{booking_reference}}.

1. SERVICES
The Provider will deliver the package "{{package}}" on {{event_dates}}, {{event_time}}, at {{location}}.

2. PAYMENT
The total amount is {{total}}, payable in full through the platform when the booking is made.

3. CANCELLATION
Cancellations follow the platform cancellation and refund rules. A cancellation after the Provider has prepared for the event may not be fully refundable.

4. TERMS
Both parties agree to the schedule above. The booking is confirmed only after both the Provider and the Client have signed this contract.
TXT;
    }

    /**
     * The provider's own template, or the platform default.
     */
    public function templateFor(string $type, $providerId): string
    {
        $template = $type === 'studio'
            ? StudiosModel::whereKey($providerId)->value('contract_template')
            : ProfileModel::where('user_id', $providerId)->value('contract_template');

        return filled($template) ? $template : $this->defaultTemplate();
    }

    /**
     * Merge booking details into a template and return safe HTML.
     *
     * @param  array<string, string>  $values
     */
    public function mergeToHtml(string $template, array $values): string
    {
        $text = preg_replace_callback('/\{\{\s*(\w+)\s*\}\}/', fn ($m) => $values[$m[1]] ?? '', $template);

        return nl2br(e($text));
    }

    /**
     * Placeholder values for a real booking.
     *
     * @return array<string, string>
     */
    public function valuesFor(BookingModel $booking): array
    {
        $booking->loadMissing(['client', 'packages']);

        $start = $booking->event_date;
        $end = $booking->event_end_date;
        $dates = $end ? $start->format('F j, Y').' to '.$end->format('F j, Y') : $start->format('F j, Y');

        $location = $booking->location_type === 'in-studio'
            ? 'the studio'
            : collect([$booking->venue_name, $booking->street, $booking->barangay, $booking->city])->filter()->implode(', ');

        return [
            'provider_name' => $this->providerName($booking),
            'client_name' => trim(($booking->client->first_name ?? '').' '.($booking->client->last_name ?? '')),
            'booking_reference' => $booking->booking_reference,
            'package' => $booking->packages->first()->package_name ?? 'your package',
            'event_dates' => $dates,
            'event_time' => date('g:i A', strtotime($booking->start_time)).' to '.date('g:i A', strtotime($booking->end_time)),
            'location' => $location ?: 'the agreed location',
            'total' => 'PHP '.number_format((float) $booking->total_amount, 2),
        ];
    }

    public function providerName(BookingModel $booking): string
    {
        return $this->providerNameFor($booking->booking_type, $booking->provider_id);
    }

    public function providerNameFor(string $type, $providerId): string
    {
        if ($type === 'studio') {
            return StudiosModel::whereKey($providerId)->value('studio_name') ?? 'the Studio';
        }

        $profile = ProfileModel::with('user')->where('user_id', $providerId)->first();

        return $profile?->brand_name ?: ($profile?->user?->full_name ?? 'the Freelancer');
    }

    /**
     * User who owns the provider side of the booking (studio owner or freelancer).
     */
    public function providerUserId(BookingModel $booking): ?int
    {
        return $booking->booking_type === 'studio'
            ? StudiosModel::whereKey($booking->provider_id)->value('user_id')
            : $booking->provider_id;
    }

    /**
     * Snapshot the merged contract for a long-term booking.
     */
    public function createFor(BookingModel $booking): BookingContractModel
    {
        $template = $this->templateFor($booking->booking_type, $booking->provider_id);

        return BookingContractModel::create([
            'booking_id' => $booking->id,
            'terms_html' => $this->mergeToHtml($template, $this->valuesFor($booking)),
            'status' => BookingContractModel::STATUS_PENDING,
        ]);
    }
}
