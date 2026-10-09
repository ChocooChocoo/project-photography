<?php

namespace App\Services;

use App\Models\BookingModel;
use App\Models\BookingContractModel;
use App\Models\StudioOwner\BookingAssignedPhotographerModel;
use App\Models\StudioOwner\BookingEquipmentModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class BookingEventRecorder
{
    public function record(int $bookingId, string $type, string $title, string $key, array $meta = []): void
    {
        // Legacy schemas may not have the additive timeline migration yet.
        if (! Schema::hasTable('tbl_booking_events')) {
            return;
        }
        \App\Models\BookingEventModel::firstOrCreate(['booking_id' => $bookingId, 'event_key' => $key], [
            'type' => $type, 'title' => $title, 'meta' => $meta,
            'occurred_at' => now(), 'actor_id' => Auth::id(),
        ]);
    }

    public function saved(Model $model): void
    {
        $bookingId = $model instanceof BookingModel ? $model->id : $model->booking_id;
        if (! $bookingId) {
            return;
        }
        $new = $model->wasRecentlyCreated;
        if ($model instanceof BookingModel) {
            if (($new || $model->wasChanged('payment_status')) && $model->payment_status === 'paid') {
                $this->record($bookingId, 'payment_settled', 'Payment settled', 'payment_settled');
            }
            if (($new || $model->wasChanged('status')) && in_array($model->status, ['in_progress', 'completed'], true)) {
                $this->record($bookingId, $model->status, $model->status === 'completed' ? 'Booking completed' : 'Booking in progress', 'booking:'.$model->status);
            }
        } elseif ($model instanceof BookingAssignedPhotographerModel) {
            $titles = ['assigned' => 'Photographer assigned', 'confirmed' => 'Photographer confirmed', 'on_site' => 'Photographer on site', 'in_progress' => 'Photographer started work', 'completed' => 'Photographer completed work'];
            if (($new || $model->wasChanged('status')) && isset($titles[$model->status])) {
                $this->record($bookingId, 'photographer_'.$model->status, $titles[$model->status], 'assignment:'.$model->id.':'.$model->status, ['assignment_id' => $model->id, 'photographer_id' => $model->photographer_id]);
            }
        } elseif ($model instanceof BookingEquipmentModel && $new) {
            $this->record($bookingId, 'equipment_assigned', 'Equipment assigned', 'equipment:'.$model->id, ['name' => $model->equipment_name, 'brand' => $model->brand]);
        } elseif ($model instanceof BookingContractModel) {
            foreach (['studio', 'client'] as $party) {
                if (($new || $model->wasChanged($party.'_signed_at')) && $model->{$party.'_signed_at'}) {
                    $this->record($bookingId, 'contract_signed', ucfirst($party === 'studio' ? 'Provider' : 'Client').' signed contract', 'contract:'.$model->id.':'.$party, ['party' => $party]);
                }
            }
        } elseif (($new || $model->wasChanged('gallery_status')) && $model->gallery_status === 'published') {
            $this->record($bookingId, 'gallery_published', 'Gallery published', 'gallery:'.$model->getTable().':'.$model->id);
        }
    }
}
