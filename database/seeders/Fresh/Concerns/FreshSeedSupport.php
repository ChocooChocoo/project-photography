<?php

namespace Database\Seeders\Fresh\Concerns;

use App\Models\UserModel;
use Database\Seeders\Concerns\SeedSupport;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Deterministic value pools for the fresh seed.
 *
 * Nothing here touches the database. Every name, sequence, and address is a
 * pure function of an index, so two runs of the seeder produce byte-identical
 * rows and a rerun updates rather than duplicates.
 *
 * Two invariants this trait exists to hold:
 *
 * 1. Identity ranges start at 4000 and use the +63918 mobile block, both of
 *    which the legacy seeders never touch, so a fresh seed can never collide
 *    with — and therefore never overwrite — a preserved tbl_users row.
 * 2. Barangays are read back out of the canonical Cavite dataset rather than
 *    typed by hand, so `db:verify-seed`'s "barangay belongs to its LGU" check
 *    cannot drift out of sync with the address literals below.
 */
trait FreshSeedSupport
{
    use SeedSupport;

    /** Sequence allocated to the single platform admin. */
    public const SEQ_ADMIN = 4000;

    /** Owners occupy 4001..4010. */
    public const SEQ_OWNER_BASE = 4000;

    /** Staff occupy 4101..4294 (stride 20 per studio leaves headroom). */
    public const SEQ_STAFF_BASE = 4100;

    public const SEQ_STAFF_STRIDE = 20;

    /** Clients occupy 4400..4429. */
    public const SEQ_CLIENT_BASE = 4400;

    /** Freelancers occupy 4500..4507. */
    public const SEQ_FREELANCER_BASE = 4500;

    /** Photographers attached to every studio owner. */
    public const PHOTOGRAPHERS_PER_STUDIO = 10;

    public const CLIENT_COUNT = 30;

    public const FREELANCER_COUNT = 8;

    /**
     * Memoised municipality => barangay list.
     *
     * @var array<string, array<int, string>>
     */
    private array $barangayCache = [];

    /**
     * Advances once per user created, so names vary across the whole dataset.
     */
    protected int $personCursor = 0;

    /**
     * Given names, disjoint from the rosters the legacy seeders use.
     *
     * @var array<int, string>
     */
    private const FIRST_NAMES = [
        'Adrian', 'Beatriz', 'Cristian', 'Daniela', 'Emilio', 'Fatima', 'Gabriel', 'Helena',
        'Ignacio', 'Jasmine', 'Kevin', 'Lorena', 'Marco', 'Nadia', 'Oscar', 'Paulina',
        'Quintin', 'Rafaela', 'Sebastian', 'Tricia', 'Ulysses', 'Veronica', 'Wilfredo', 'Ximena',
        'Yolanda', 'Zacarias', 'Alfonso', 'Bernadette', 'Cesar', 'Divina', 'Eduardo', 'Florencia',
        'Gerardo', 'Hazel', 'Isidro', 'Juliana', 'Katrina', 'Leandro', 'Manuela', 'Nicolas',
    ];

    /**
     * Middle names, cycled independently of the given-name pool.
     *
     * @var array<int, string>
     */
    private const MIDDLE_NAMES = [
        'Abad', 'Bautista', 'Cabral', 'Dizon', 'Enriquez', 'Fajardo',
        'Galang', 'Hidalgo', 'Ilagan', 'Jimeno', 'Kalaw', 'Landicho',
    ];

    /**
     * Surnames, deliberately disjoint from every legacy seeder roster so the
     * fresh dataset is visibly new even before the 4000-series sequence.
     *
     * @var array<int, string>
     */
    private const SURNAMES = [
        'Alcantara', 'Barrameda', 'Cuevas', 'Dimaculangan', 'Escaño', 'Fontanilla',
        'Guevarra', 'Hernandez', 'Inocencio', 'Jocson', 'Katigbak', 'Lumibao',
        'Maghirang', 'Nepomuceno', 'Ocampo', 'Panganiban', 'Quijano', 'Rivamonte',
        'Sarmiento', 'Tolentino', 'Umali', 'Villaflor', 'Wenceslao', 'Yabut',
        'Zabala', 'Adriano', 'Bulanadi', 'Cenizal', 'Dueñas', 'Espiritu',
        'Fabregas', 'Gonzaga', 'Hulipas', 'Isidro', 'Jarabejo', 'Kabigting',
        'Lagdameo', 'Manansala', 'Novilla', 'Orpilla',
    ];

    /**
     * Build a deterministic person from a flat index.
     *
     * @return array{first_name: string, middle_name: string, last_name: string}
     */
    protected function person(int $index): array
    {
        $firsts = count(self::FIRST_NAMES);
        $lasts = count(self::SURNAMES);

        return [
            'first_name' => self::FIRST_NAMES[$index % $firsts],
            'middle_name' => self::MIDDLE_NAMES[$index % count(self::MIDDLE_NAMES)],
            // Stride 7 is coprime with the pool size, so the surname advances on
            // every index instead of staying fixed for 40 consecutive people.
            'last_name' => self::SURNAMES[($index * 7 + intdiv($index, $firsts)) % $lasts],
        ];
    }

    /**
     * Create or refresh one fresh-seed user.
     *
     * tbl_users is preserved, so this is the only write in the whole seed that
     * cannot be a truncate-and-insert. It is an upsert keyed on email, and it
     * refuses to touch a row whose mobile number is outside the fresh-seed
     * block — that would mean the address belongs to a preserved account, and
     * updating it would violate the "existing users unchanged" constraint.
     */
    protected function upsertFreshUser(int $sequence, string $role, string $userType, ?int $locationId): UserModel
    {
        $person = $this->person($this->personCursor++);
        $email = $this->gmail($person['first_name'], $person['last_name'], $sequence);
        $mobile = $this->freshMobile($sequence);

        $existing = UserModel::query()->where('email', $email)->first(['id', 'uuid', 'mobile_number']);

        if ($existing !== null && $existing->mobile_number !== $mobile) {
            throw new RuntimeException(
                "Refusing to overwrite preserved user [{$email}]: its mobile number is outside the fresh-seed range."
            );
        }

        return UserModel::updateOrCreate(
            ['email' => $email],
            [
                'uuid' => $existing->uuid ?? (string) Str::uuid(),
                'role' => $role,
                'user_type' => $userType,
                'first_name' => $person['first_name'],
                'middle_name' => $person['middle_name'],
                'last_name' => $person['last_name'],
                'mobile_number' => $mobile,
                'password' => Hash::make(self::SEED_PASSWORD),
                'status' => 'active',
                'email_verified' => true,
                'verification_token' => null,
                'token_expiry' => null,
                'location_id' => $locationId,
                // Media columns stay empty: the seed carries no images.
                'profile_photo' => null,
                'cover_photo' => null,
            ]
        );
    }

    /**
     * Mobile numbers in a prefix block no other seeder writes to.
     */
    protected function freshMobile(int $sequence): string
    {
        return '+63918'.str_pad((string) (400000 + $sequence), 6, '0', STR_PAD_LEFT);
    }

    /**
     * A real barangay of the given LGU, taken from the canonical dataset.
     *
     * The offset lets one municipality host several distinct addresses without
     * hardcoding names that could drift away from the dataset.
     */
    protected function barangayFor(string $municipality, int $offset = 0): string
    {
        if (! isset($this->barangayCache[$municipality])) {
            foreach (require database_path('data/cavite-locations.php') as $location) {
                $this->barangayCache[$location['municipality']] = $location['barangay'];
            }
        }

        if (! isset($this->barangayCache[$municipality])) {
            throw new RuntimeException("Unknown Cavite LGU [{$municipality}].");
        }

        $barangays = $this->barangayCache[$municipality];

        return $barangays[$offset % count($barangays)];
    }

    /**
     * The ten photography categories seeded by CategorySeeder, in order.
     *
     * @return array<int, string>
     */
    protected function categoryNames(): array
    {
        return [
            'Wedding Photography',
            'Event Photography',
            'Family Portrait',
            'Product Photography',
            'Street Photography',
            'Fashion Photography',
            'Documentary Photography',
            'Food Photography',
            'Real Estate Photography',
            'Pet Photography',
        ];
    }

    /**
     * Three service labels per category, used as the tbl_services JSON payload.
     *
     * @return array<int, string>
     */
    protected function servicesFor(string $categoryName): array
    {
        return match ($categoryName) {
            'Wedding Photography' => ['Ceremony Day Coverage', 'Prenup Session Direction', 'Reception Highlight Reel'],
            'Event Photography' => ['Corporate Program Coverage', 'Milestone Party Documentation', 'Stage and Guest Highlights'],
            'Family Portrait' => ['Studio Family Session', 'Outdoor Lifestyle Session', 'Generational Group Portraits'],
            'Product Photography' => ['Catalog Flat-Lay Set', 'Detail and Texture Studies', 'Marketplace Listing Set'],
            'Street Photography' => ['Guided Street Walk Session', 'Urban Candid Series', 'Neighbourhood Story Set'],
            'Fashion Photography' => ['Lookbook Production', 'Campaign Editorial Set', 'Designer Drop Coverage'],
            'Documentary Photography' => ['Long-Form Story Coverage', 'Community Feature Series', 'Archival Sequence Build'],
            'Food Photography' => ['Menu Plating Set', 'Restaurant Ambience Series', 'Recipe Step Sequence'],
            'Real Estate Photography' => ['Interior Walkthrough Set', 'Exterior and Facade Series', 'Listing Highlight Set'],
            default => ['Companion Portrait Session', 'Play and Motion Series', 'Owner and Pet Portraits'],
        };
    }

    /**
     * The package family name a studio sells under for a given category.
     */
    protected function packageFamilyFor(string $categoryName): string
    {
        return match ($categoryName) {
            'Wedding Photography' => 'Vows and Verses Collection',
            'Event Photography' => 'Gathering Day Collection',
            'Family Portrait' => 'Kindred Frames Collection',
            'Product Photography' => 'Shelf Ready Collection',
            'Street Photography' => 'City Cadence Collection',
            'Fashion Photography' => 'Runway Print Collection',
            'Documentary Photography' => 'Long Story Collection',
            'Food Photography' => 'Table Setting Collection',
            'Real Estate Photography' => 'Open House Collection',
            default => 'Companion Portraits Collection',
        };
    }

    /**
     * Ten studios, one per distinct Cavite LGU.
     *
     * `barangay` is resolved from the dataset at read time rather than stored,
     * so it always satisfies the studio-address invariant.
     *
     * @return array<int, array<string, mixed>>
     */
    protected function studioCatalog(): array
    {
        return [
            $this->studioEntry('meridian', 'Meridian Light Studio', 'Bacoor', '3F Cordova Row, Molino Boulevard', 14.4590, 120.9600, 2018, 'photography_studio', 'A daylight-first studio built around clean portrait work and unhurried session pacing for families and couples across the Bacoor corridor.'),
            $this->studioEntry('cascade', 'Cascade Frame Works', 'Imus', '2F Talaba Commercial Row, Nueno Avenue', 14.4297, 120.9367, 2019, 'photography_studio', 'A production-minded studio that pairs editorial direction with reliable scheduling for weddings, launches, and brand campaigns.'),
            $this->studioEntry('lantern', 'Lantern House Photography', 'Dasmariñas', 'Unit 5, Salawag Trade Center', 14.3294, 120.9367, 2017, 'mixed_media', 'A mixed-media house covering long-form documentary work alongside classic portrait sessions, with in-house editing and archiving.'),
            $this->studioEntry('quarry', 'Quarry Road Studio', 'General Trias', '1F Buenavista Arcade, Governors Drive', 14.3869, 120.8815, 2020, 'photography_studio', 'A compact studio focused on catalog and product work for local sellers, with fast turnaround and consistent lighting setups.'),
            $this->studioEntry('highland', 'Highland Story Collective', 'Silang', 'Km 42 Aguinaldo Highway, Biga Junction', 14.2306, 120.9747, 2016, 'photography_studio', 'An upland collective known for garden weddings and open-air family sessions, with weather-aware scheduling and backup dates.'),
            $this->studioEntry('saltair', 'Saltair Visual Studio', 'Tanza', '2F Daang Amaya Commercial Block', 14.3944, 120.8517, 2021, 'photography_studio', 'A coastal studio covering seaside ceremonies and lifestyle portraits, with travel-ready kits and on-location direction.'),
            $this->studioEntry('heritage', 'Heritage Corner Studio', 'Kawit', 'Ground Floor, Binakayan Heritage Row', 14.4442, 120.9022, 2015, 'mixed_media', 'A heritage-district studio pairing archival documentary coverage with formal portraiture for families and civic groups.'),
            $this->studioEntry('parallel', 'Parallel Lines Studio', 'Carmona', 'Unit 12, Maduya Business Court', 14.3132, 121.0578, 2022, 'photography_studio', 'A minimal, grid-driven studio built for fashion lookbooks and product campaigns, with modular set pieces and colour control.'),
            $this->studioEntry('northgate', 'Northgate Portrait House', 'Trece Martires City', '2F Osorio Civic Arcade', 14.2820, 120.8664, 2019, 'photography_studio', 'A portrait house serving civic ceremonies, graduations, and family milestones with structured session flows.'),
            $this->studioEntry('ridgeview', 'Ridgeview Frame Studio', 'Tagaytay City', 'Km 58 Ridge Access Road, Kaybagal', 14.1153, 120.9621, 2014, 'mixed_media', 'A ridge-line studio covering destination weddings and food features for the Tagaytay restaurant circuit, with cool-weather kits.'),
        ];
    }

    /**
     * Assemble one studio catalog row.
     *
     * @return array<string, mixed>
     */
    private function studioEntry(
        string $code,
        string $studioName,
        string $municipality,
        string $street,
        float $latitude,
        float $longitude,
        int $yearEstablished,
        string $studioType,
        string $description
    ): array {
        return [
            'code' => $code,
            'studio_name' => $studioName,
            'municipality' => $municipality,
            'street' => $street,
            'attendance_latitude' => $latitude,
            'attendance_longitude' => $longitude,
            'year_established' => $yearEstablished,
            'studio_type' => $studioType,
            'studio_description' => $description,
        ];
    }

    /**
     * Weekday labels in the order tbl_studios.operating_days stores them.
     *
     * @return array<int, string>
     */
    protected function operatingDaysFor(int $studioIndex): array
    {
        return $studioIndex % 2 === 0
            ? ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday']
            : ['tuesday', 'wednesday', 'thursday', 'friday', 'saturday'];
    }
}
