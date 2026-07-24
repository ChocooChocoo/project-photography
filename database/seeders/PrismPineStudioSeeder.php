<?php

namespace Database\Seeders;

use App\Models\StudioOwner\StudiosModel;
use App\Models\UserModel;
use Database\Seeders\Concerns\SeedSupport;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Creates the Prism & Pine Creative Spaces studio and its owner.
 *
 * Seven seeders look this studio up by name - the six package seeders,
 * PrismPineStudioDataSyncSeeder, and StudioEmployeesSeeder (which needs a
 * studio outside the MultiStudioBundleSeeder roster) - but nothing created it,
 * so it only ever existed on the hand-maintained database.
 */
class PrismPineStudioSeeder extends Seeder
{
    use SeedSupport;

    public const STUDIO_NAME = 'Prism & Pine Creative Spaces';

    public function run(): void
    {
        $owner = $this->upsertOwner();
        $studio = $this->upsertStudio($owner->id);

        $ownerRoleId = DB::table('tbl_roles')->where('name', 'owner-super-admin')->value('id');

        if ($ownerRoleId) {
            DB::table('tbl_user_roles')->updateOrInsert(
                [
                    'user_id' => $owner->id,
                    'role_id' => $ownerRoleId,
                    'studio_id' => $studio->id,
                ],
                [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        $this->command?->info('Seeded studio: '.self::STUDIO_NAME);
    }

    private function upsertOwner(): UserModel
    {
        $email = $this->gmail('Daphne', 'Russo');

        return UserModel::updateOrCreate(
            ['email' => $email],
            [
                'uuid' => UserModel::where('email', $email)->value('uuid') ?: (string) Str::uuid(),
                'role' => 'owner',
                'user_type' => 'Photographer',
                'first_name' => 'Daphne',
                'middle_name' => 'Karina',
                'last_name' => 'Russo',
                'mobile_number' => '+639171234567',
                'password' => Hash::make(self::SEED_PASSWORD),
                'location_id' => $this->locationId('Dasmariñas'),
                'status' => 'active',
                'email_verified' => true,
                'verification_token' => null,
                'token_expiry' => null,
            ]
        );
    }

    private function upsertStudio(int $ownerId): StudiosModel
    {
        return StudiosModel::updateOrCreate(
            ['studio_name' => self::STUDIO_NAME],
            [
                'user_id' => $ownerId,
                'category_id' => null,
                'location_id' => $this->locationId('Dasmariñas'),
                'street' => 'Unit 402, 4th Floor, Valero Tower, 122 Valero St.',
                'barangay' => 'Salitran III',
                'attendance_latitude' => 14.3291121,
                'attendance_longitude' => 120.9410593,
                'attendance_radius_meters' => 30,
                'contact_number' => '09171234567',
                'studio_email' => 'prismandpinestudio@gmail.com',
                'facebook_url' => 'https://facebook.com/prismandpinestudio',
                'instagram_url' => 'https://instagram.com/prismandpine',
                'website_url' => 'https://www.prismandpine.com',
                'studio_type' => 'photography_studio',
                'year_established' => 2022,
                'studio_description' => 'A 120sqm minimalist, Scandinavian-inspired daylight studio. We specialize in editorial fashion, high-end portraits, and commercial product shoots. The space features floor-to-ceiling windows, a motorized cyclorama wall, and a dedicated makeup and dressing area.',
                'studio_logo' => 'studio_logo/seed-prism-and-pine.png',
                'starting_price' => '3500',
                'downpayment_percentage' => 50.00,
                'operating_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday'],
                'start_time' => '09:00:00',
                'end_time' => '18:00:00',
                'max_clients_per_day' => 5,
                'advance_booking_days' => 2,
                'business_permit' => 'studio_documents/seed-prismpine-business-permit.pdf',
                'owner_id_document' => 'studio_documents/seed-prismpine-owner-id.pdf',
                'status' => 'verified',
                'rejection_note' => null,
            ]
        );
    }
}
