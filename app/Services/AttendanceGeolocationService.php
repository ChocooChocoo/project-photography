<?php

namespace App\Services;

use App\Models\StudioOwner\StudiosModel;

/**
 * Handle server-side distance checks for attendance geofencing.
 */
class AttendanceGeolocationService
{
    /**
     * Determine whether the given coordinates fall within the studio geofence.
     *
     * @param StudiosModel $studio
     * @param float $latitude
     * @param float $longitude
     * @return array<string, mixed>
     */
    public function validateStudioGeofence(StudiosModel $studio, float $latitude, float $longitude): array
    {
        if (is_null($studio->attendance_latitude) || is_null($studio->attendance_longitude)) {
            return [
                'allowed' => false,
                'status' => 'MISSING_STUDIO_PIN',
                'distance_meters' => null,
                'radius_meters' => (int) ($studio->attendance_radius_meters ?? 100),
                'message' => 'Attendance is unavailable because your studio geofence has not been configured yet.',
            ];
        }

        $distanceMeters = $this->calculateDistanceMeters(
            (float) $studio->attendance_latitude,
            (float) $studio->attendance_longitude,
            $latitude,
            $longitude
        );
        $radiusMeters = (int) ($studio->attendance_radius_meters ?? 100);
        $allowed = $distanceMeters <= $radiusMeters;

        return [
            'allowed' => $allowed,
            'status' => $allowed ? 'WITHIN_GEOFENCE' : 'OUTSIDE_GEOFENCE',
            'distance_meters' => $distanceMeters,
            'radius_meters' => $radiusMeters,
            'message' => $allowed
                ? 'Location verified successfully.'
                : 'You must be within ' . $radiusMeters . ' meters of the studio to submit attendance.',
        ];
    }

    /**
     * Calculate straight-line distance between two coordinates.
     */
    public function calculateDistanceMeters(float $originLatitude, float $originLongitude, float $targetLatitude, float $targetLongitude): int
    {
        $earthRadiusMeters = 6371000;
        $latitudeDelta = deg2rad($targetLatitude - $originLatitude);
        $longitudeDelta = deg2rad($targetLongitude - $originLongitude);

        $a = sin($latitudeDelta / 2) * sin($latitudeDelta / 2)
            + cos(deg2rad($originLatitude))
            * cos(deg2rad($targetLatitude))
            * sin($longitudeDelta / 2)
            * sin($longitudeDelta / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return (int) round($earthRadiusMeters * $c);
    }
}
