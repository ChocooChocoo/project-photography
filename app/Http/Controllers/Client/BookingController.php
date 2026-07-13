<?php

namespace App\Http\Controllers\Client;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\StudioOwner\StudiosModel;
use App\Models\Freelancer\ProfileModel;
use App\Models\StudioOwner\PackagesModel as StudioPackagesModel;
use App\Models\Freelancer\PackagesModel as FreelancerPackagesModel;
use App\Models\Admin\CategoriesModel;
use App\Models\BookingModel;
use App\Models\PaymentModel;
use App\Models\BookingPackageModel;
use App\Models\SystemRevenueModel;
use App\Models\ClientBudgetModel;
use App\Services\StripeService;
use App\Traits\Notifiable;
use Carbon\Carbon;

class BookingController extends Controller
{
    use Notifiable;

    protected $stripeService;

    public function __construct(StripeService $stripeService)
    {
        $this->stripeService = $stripeService;
    }

    /*
    * Show booking form with dynamic data
    */
    public function create($type, $id)
    {
        $user = Auth::user();
        
        if ($type === 'studio') {
            $provider = StudiosModel::whereIn('status', ['approved', 'active', 'verified'])
                ->with(['category', 'packages', 'schedules'])
                ->findOrFail($id);
            
            // Get studio categories - only categories with active packages
            $categories = CategoriesModel::whereHas('packages', function($query) use ($id) {
                    $query->where('studio_id', $id)->where('status', 'active');
                })
                ->where('status', 'active')
                ->orderBy('category_name', 'asc')
                ->get();

            // Extract operating days from studio schedule
            $schedule = $provider->schedules->first();
            $operatingDays = $schedule->operating_days ?? [];
            if (is_string($operatingDays)) {
                $operatingDays = json_decode($operatingDays, true) ?? [];
            }
            
            // Get downpayment percentage
            $downpaymentPercentage = $provider->downpayment_percentage ?? 30;
            
            // ========== STUDIO DEPOSIT INFO ==========
            $depositPolicy = 'required'; // Studios always require downpayment
            $depositType = 'percentage';
            $depositAmount = $downpaymentPercentage;
            $depositDisplay = $downpaymentPercentage . '% downpayment required';
        } else {
            $provider = ProfileModel::with(['user', 'categories', 'schedule'])
                ->whereHas('user', function($query) {
                    $query->where('status', 'active');
                })
                ->where('user_id', $id)
                ->firstOrFail();
            
            // Get freelancer categories - only categories with active packages
            $categories = CategoriesModel::whereHas('freelancerPackages', function($query) use ($id) {
                    $query->where('user_id', $id)->where('status', 'active');
                })
                ->where('status', 'active')
                ->orderBy('category_name', 'asc')
                ->get();

            // Extract operating days from freelancer schedule
            $schedule = $provider->schedule;
            $operatingDays = $schedule->operating_days ?? [];
            if (is_string($operatingDays)) {
                $operatingDays = json_decode($operatingDays, true) ?? [];
            }
            
            // ========== FREELANCER DEPOSIT LOGIC ==========
            // Get deposit policy from freelancer profile
            $depositPolicy = $provider->deposit_policy ?? 'not_required';
            $depositType = $provider->deposit_type ?? null;
            $depositAmount = $provider->deposit_amount ?? 0;
            
            // Calculate downpayment percentage for display (only relevant for percentage type)
            if ($depositPolicy === 'required' && $depositType === 'percentage') {
                $downpaymentPercentage = $depositAmount;
                $depositDisplay = $downpaymentPercentage . '% downpayment required';
            } elseif ($depositPolicy === 'required' && $depositType === 'fixed') {
                $downpaymentPercentage = 0; // Not applicable for fixed amount
                $depositDisplay = '₱' . number_format($depositAmount, 2) . ' fixed deposit required';
            } else {
                $downpaymentPercentage = 0;
                $depositDisplay = 'No deposit required (full payment)';
            }
            // ========== End of Freelancer Deposit Logic ==========
        }

        // Get all active municipalities for dropdown
        $municipalities = \App\Models\Admin\LocationModel::where('status', 'active')
            ->whereNotNull('municipality')
            ->orderBy('municipality', 'asc')
            ->pluck('municipality')
            ->unique()
            ->values();

        // Get available dates for the next 60 days
        $availableDates = $this->getAvailableDates($type, $id);
        
        // ========== START: DSS - Load client budgets for this session ==========
        $clientBudgets = [];
        if (Auth::check()) {
            $clientBudgets = ClientBudgetModel::where('client_id', Auth::id())
                ->where('status', 'active')
                ->get()
                ->keyBy('category_id');
        }
        // ========== END: DSS - Load client budgets ==========
        
        return view('client.booking-forms', compact(
            'type', 
            'id', 
            'provider', 
            'categories', 
            'user', 
            'availableDates',
            'municipalities',
            'operatingDays',
            'downpaymentPercentage',
            'depositPolicy',
            'depositType',
            'depositAmount',
            'depositDisplay',
            'clientBudgets' // ADD THIS
        ));
    }

    /**
     * Get available dates for booking
     */
    public function getAvailableDates($type, $providerId)
    {
        $dates = [];
        $startDate = Carbon::today();
        $endDate = Carbon::today()->addDays(60);
        
        $currentDate = $startDate->copy();
        
        while ($currentDate->lte($endDate)) {
            // Check if date is available (for now, mark all as available)
            // You can implement logic to check against existing bookings
            $dates[] = [
                'date' => $currentDate->format('Y-m-d'),
                'available' => true,
                'is_weekend' => $currentDate->isWeekend(),
            ];
            
            $currentDate->addDay();
        }
        
        return $dates;
    }

    /**
     * Check date availability with schedule and time overlap validation
     */
    public function checkAvailability(Request $request)
    {
        $request->validate([
            'type'         => 'required|in:studio,freelancer',
            'provider_id'  => 'required|integer',
            'date'         => 'required|date',
            'start_time'   => 'required|date_format:H:i',
            'end_time'     => 'required|date_format:H:i|after:start_time',
        ]);

        $selectedDate = Carbon::parse($request->date);
        $dayOfWeek    = strtolower($selectedDate->format('l'));

        // Check if date is in the past
        if ($selectedDate->isPast()) {
            return response()->json([
                'success'      => false,
                'available'    => false,
                'message'      => 'Selected date is in the past.',
                'is_past_date' => true,
            ]);
        }

        // Get provider schedule
        if ($request->type === 'studio') {
            $provider = StudiosModel::with('schedules')->find($request->provider_id);

            if (!$provider || !$provider->schedules || $provider->schedules->isEmpty()) {
                return response()->json([
                    'success'     => false,
                    'available'   => false,
                    'message'     => 'Studio has no schedule available.',
                    'no_schedule' => true,
                ]);
            }

            $schedule      = $provider->schedules->first();
            $operatingDays = $schedule->operating_days ?? [];

            if (is_string($operatingDays)) {
                $operatingDays = json_decode($operatingDays, true) ?? [];
            }

            if (!in_array($dayOfWeek, $operatingDays)) {
                return response()->json([
                    'success'           => false,
                    'available'         => false,
                    'message'           => 'Studio is closed on ' . ucfirst($dayOfWeek) . '.',
                    'not_operating_day' => true,
                ]);
            }

            $maxBookings = $provider->max_clients_per_day ?? 3;

        } else {
            $provider = ProfileModel::with('schedule')->where('user_id', $request->provider_id)->first();

            if (!$provider || !$provider->schedule) {
                return response()->json([
                    'success'     => false,
                    'available'   => false,
                    'message'     => 'Freelancer has no schedule available.',
                    'no_schedule' => true,
                ]);
            }

            $schedule      = $provider->schedule;
            $operatingDays = $schedule->operating_days ?? [];

            if (is_string($operatingDays)) {
                $operatingDays = json_decode($operatingDays, true) ?? [];
            }

            if (!in_array($dayOfWeek, $operatingDays)) {
                return response()->json([
                    'success'           => false,
                    'available'         => false,
                    'message'           => 'Freelancer is not available on ' . ucfirst($dayOfWeek) . '.',
                    'not_operating_day' => true,
                ]);
            }

            $maxBookings = $schedule->booking_limit ?? 3;
        }

        // Check max bookings limit for the day
        $existingBookingsCount = BookingModel::where('booking_type', $request->type)
            ->where('provider_id', $request->provider_id)
            ->where('event_date', $request->date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->count();

        if ($existingBookingsCount >= $maxBookings) {
            return response()->json([
                'success'           => false,
                'available'         => false,
                'existing_bookings' => $existingBookingsCount,
                'max_bookings'      => $maxBookings,
                'message'           => 'This date is fully booked (' . $existingBookingsCount . '/' . $maxBookings . ' bookings).',
            ]);
        }

        // Check time overlap against existing bookings on the same date
        $hasTimeOverlap = BookingModel::where('booking_type', $request->type)
            ->where('provider_id', $request->provider_id)
            ->where('event_date', $request->date)
            ->whereIn('status', ['pending', 'confirmed'])
            ->where(function ($query) use ($request) {
                $query->where(function ($q) use ($request) {
                    // New booking starts during an existing booking
                    $q->where('start_time', '<=', $request->start_time)
                    ->where('end_time', '>', $request->start_time);
                })->orWhere(function ($q) use ($request) {
                    // New booking ends during an existing booking
                    $q->where('start_time', '<', $request->end_time)
                    ->where('end_time', '>=', $request->end_time);
                })->orWhere(function ($q) use ($request) {
                    // New booking completely covers an existing booking
                    $q->where('start_time', '>=', $request->start_time)
                    ->where('end_time', '<=', $request->end_time);
                });
            })
            ->exists();

        if ($hasTimeOverlap) {
            return response()->json([
                'success'           => false,
                'available'         => false,
                'existing_bookings' => $existingBookingsCount,
                'max_bookings'      => $maxBookings,
                'message'           => 'The selected time slot overlaps with an existing booking.',
                'time_overlap'      => true,
            ]);
        }

        return response()->json([
            'success'           => true,
            'available'         => true,
            'existing_bookings' => $existingBookingsCount,
            'max_bookings'      => $maxBookings,
            'operating_day'     => true,
            'message'           => 'Available (' . $existingBookingsCount . '/' . $maxBookings . ' bookings)',
        ]);
    }

    /**
     * Store booking with validation before storing
     */
    public function store(Request $request)
    {
        // Validate all input first - MODIFIED to handle location-based validation
        $rules = [
            'type' => 'required|in:studio,freelancer',
            'provider_id' => 'required|integer',
            'category_id' => 'required|exists:tbl_categories,id',
            'package_id' => 'required|integer',
            'event_date' => 'required|date|after:today',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
            'location_type' => 'required|in:in-studio,on-location',
            'special_requests' => 'nullable|string|max:1000',
            'full_name' => 'required|string|max:255',
            'contact_number' => 'required|string|max:20',
            'email' => 'required|email|max:255',
        ];

        // ==== FIXED: Conditional validation based on location type ====
        // For on-location bookings, validate location fields
        if ($request->location_type === 'on-location') {
            // Check if multiple locations are enabled
            if ($request->has('locations') && is_array($request->locations) && count($request->locations) > 0) {
                // Validate multiple locations
                $rules['locations'] = 'required|array|min:1';
                $rules['locations.*.venue_name'] = 'nullable|string|max:255';
                $rules['locations.*.city'] = 'required|string|max:255';
                $rules['locations.*.barangay'] = 'required|string|max:255'; // Barangay REQUIRED for on-location
                $rules['locations.*.street'] = 'nullable|string|max:255';
                
                // Validate max locations based on package
                $packageValidation = $this->validatePackage($request);
                if ($packageValidation['valid']) {
                    $package = $packageValidation['package'];
                    if (!$this->packageAllowsMultipleLocations($package, $request->type)) {
                        return response()->json([
                            'success' => false,
                            'message' => 'Multiple locations are not allowed for this package.',
                        ], 400);
                    }

                    $maxLocations = $package->max_locations ?? 1;
                    
                    if (count($request->locations) > $maxLocations) {
                        return response()->json([
                            'success' => false,
                            'message' => "Maximum of {$maxLocations} locations allowed for this package.",
                        ], 400);
                    }
                }
            } else {
                // Single location validation for on-location bookings
                $rules['venue_name'] = 'nullable|string|max:255';
                $rules['street'] = 'nullable|string|max:255';
                $rules['barangay'] = 'required|string|max:255'; // Barangay REQUIRED for on-location
                $rules['city'] = 'required|string|max:255';
            }
        } else {
            // For in-studio bookings, location fields are NOT required
            // Only validate if they are present (optional)
            $rules['venue_name'] = 'nullable|string|max:255';
            $rules['street'] = 'nullable|string|max:255';
            $rules['barangay'] = 'nullable|string|max:255'; // Barangay OPTIONAL for in-studio
            $rules['city'] = 'nullable|string|max:255'; // City OPTIONAL for in-studio
            
            // Clear any multiple locations data for in-studio bookings
            $request->merge(['locations' => null]);
        }
        // ==== END: Conditional validation based on location type ====

        // ==== FIXED: Conditional payment_type validation for freelancers ====
        // For studios, payment_type is always required
        if ($request->type === 'studio') {
            $rules['payment_type'] = 'required|in:downpayment,full_payment';
        } else {
            // For freelancers, check if they have a deposit policy
            $freelancer = \App\Models\Freelancer\ProfileModel::where('user_id', $request->provider_id)->first();
            
            // If freelancer has no deposit policy or policy is 'not_required', payment_type should be 'full_payment'
            // But we don't need to validate it from the request - we'll set it in the backend
            if (!$freelancer || $freelancer->deposit_policy !== 'required') {
                // No validation rule for payment_type - we'll set it manually
            }
        }
        // ==== END: Conditional payment_type validation ====

        $request->validate($rules);

        try {
            // 1. Validate date availability first
            $availabilityCheck = $this->checkDateAvailability($request);
            if (!$availabilityCheck['success'] || !$availabilityCheck['available']) {
                return response()->json([
                    'success' => false,
                    'message' => $availabilityCheck['message'] ?? 'Selected date is not available.',
                    'availability_error' => true,
                ], 400);
            }

            // 2. Validate package exists and belongs to the correct provider and category
            $packageValidation = $this->validatePackage($request);
            if (!$packageValidation['valid']) {
                return response()->json([
                    'success' => false,
                    'message' => $packageValidation['message'] ?? 'Invalid package selected.',
                    'package_error' => true,
                ], 400);
            }

            // 3. Get package details after validation
            $package = $packageValidation['package'];

            if (!$this->isLocationTypeAllowedForPackage($package, $request->type, $request->location_type)) {
                return response()->json([
                    'success' => false,
                    'message' => 'The selected location type is not available for this package.',
                    'location_type_error' => true,
                ], 400);
            }

            // 4. Calculate amounts based on deposit policy
            $totalAmount = $package->package_price;
            
            // ========== FIX: Implement deposit logic based on provider type ==========
            if ($request->type === 'studio') {
                // Studio logic (unchanged)
                $studio = StudiosModel::find($request->provider_id);
                $downpaymentPercentage = $studio->downpayment_percentage ?? 30;
                $paymentType = 'downpayment';
                $downPayment = ($totalAmount * $downpaymentPercentage) / 100;
                $remainingBalance = $totalAmount - $downPayment;
                $depositPolicy = $downpaymentPercentage . '%';
                $paymentStatus = 'pending';
                $bookingStatus = 'pending';
                
            } else {
                // Freelancer logic based on deposit policy
                $freelancer = ProfileModel::where('user_id', $request->provider_id)->first();
                
                if ($freelancer && $freelancer->deposit_policy === 'required') {
                    // Deposit is required
                    $paymentType = 'downpayment';
                    
                    if ($freelancer->deposit_type === 'percentage') {
                        // Percentage-based deposit
                        $downpaymentPercentage = $freelancer->deposit_amount ?? 30;
                        $downPayment = ($totalAmount * $downpaymentPercentage) / 100;
                        $depositPolicy = $downpaymentPercentage . '%';
                        
                    } elseif ($freelancer->deposit_type === 'fixed') {
                        // Fixed amount deposit
                        $downPayment = $freelancer->deposit_amount ?? 0;
                        $downpaymentPercentage = 0; // Not applicable
                        $depositPolicy = 'Fixed: ₱' . number_format($downPayment, 2);
                        
                        // Ensure fixed deposit doesn't exceed total amount
                        if ($downPayment > $totalAmount) {
                            $downPayment = $totalAmount;
                        }
                    } else {
                        // Fallback to 30% if type not set
                        $downpaymentPercentage = 30;
                        $downPayment = ($totalAmount * 30) / 100;
                        $depositPolicy = '30%';
                    }
                    
                    $remainingBalance = $totalAmount - $downPayment;
                    $paymentStatus = 'pending';
                    $bookingStatus = 'pending';
                    
                } else {
                    // No deposit required - full payment
                    $paymentType = 'full_payment';
                    $downPayment = $totalAmount;
                    $remainingBalance = 0;
                    $downpaymentPercentage = 100;
                    $depositPolicy = '100% (Full payment)';
                    $paymentStatus = 'pending';
                    $bookingStatus = 'pending';
                }
            }
            // ========== End of deposit logic ==========

            // 5. Prepare booking data
            $bookingData = [
                'booking_reference' => BookingModel::generateBookingReference(),
                'client_id' => Auth::id(),
                'booking_type' => $request->type,
                'provider_id' => $request->provider_id,
                'category_id' => $request->category_id,
                'event_date' => $request->event_date,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'location_type' => $request->location_type,
                'special_requests' => $request->special_requests,
                'total_amount' => $totalAmount,
                'down_payment' => $downPayment,
                'remaining_balance' => $remainingBalance,
                'deposit_policy' => $depositPolicy,
                'payment_type' => $paymentType,
                'status' => $bookingStatus,
                'payment_status' => $paymentStatus,
                'expires_at' => $bookingStatus === 'pending' ? now()->addHours(48) : null,
            ];

            // Handle locations based on location type
            if ($request->location_type === 'on-location') {
                // For on-location bookings, handle multiple or single locations
                if ($request->has('locations') && is_array($request->locations) && count($request->locations) > 0) {
                    // Multiple locations
                    $bookingData['multiple_locations'] = $request->locations;
                    // Clear single location fields
                    $bookingData['venue_name'] = null;
                    $bookingData['street'] = null;
                    $bookingData['barangay'] = null;
                    $bookingData['city'] = null;
                    $bookingData['province'] = 'Cavite';
                } else {
                    // Single on-location
                    $bookingData['venue_name'] = $request->venue_name;
                    $bookingData['street'] = $request->street;
                    $bookingData['barangay'] = $request->barangay;
                    $bookingData['city'] = $request->city;
                    $bookingData['province'] = 'Cavite';
                    $bookingData['multiple_locations'] = null;
                }
            } else {
                // For in-studio bookings, location fields are optional
                $bookingData['venue_name'] = null;
                $bookingData['street'] = null;
                $bookingData['barangay'] = null;
                $bookingData['city'] = null;
                $bookingData['province'] = 'Cavite';
                $bookingData['multiple_locations'] = null;
            }

            // Create booking
            $booking = BookingModel::create($bookingData);

            $normalizedCoverageScope = $this->normalizeCoverageScope($package->coverage_scope);

            // 6. Create booking package record
            BookingPackageModel::create([
                'booking_id' => $booking->id,
                'package_id' => $package->id,
                'package_type' => $request->type,
                'package_name' => $package->package_name,
                'package_price' => $package->package_price,
                'package_inclusions' => json_encode($package->package_inclusions),
                'duration' => $package->duration,
                'maximum_edited_photos' => $package->maximum_edited_photos,
                'coverage_scope' => $normalizedCoverageScope,
            ]);

            // 7. Create initial payment record
            $payment = PaymentModel::create([
                'booking_id' => $booking->id,
                'payment_reference' => PaymentModel::generatePaymentReference(),
                'amount' => $downPayment,
                'payment_method' => 'pending',
                'status' => 'pending',
            ]);

            // Get the provider's user ID based on booking type
            $providerUserId = null;
            $providerName = '';
            $providerType = '';

            if ($request->type === 'studio') {
                $studio = StudiosModel::find($request->provider_id);
                if ($studio) {
                    $providerUserId = $studio->user_id; // The studio owner's user ID
                    $providerName = $studio->studio_name;
                    $providerType = 'studio';
                }
            } elseif ($request->type === 'freelancer') {
                $freelancer = ProfileModel::where('user_id', $request->provider_id)->first();
                if ($freelancer) {
                    $providerUserId = $freelancer->user_id; // The freelancer's user ID
                    $providerName = $freelancer->brand_name ?: $freelancer->user->full_name;
                    $providerType = 'freelancer';
                }
            }

            // Create notification if we have a valid provider user ID
            if ($providerUserId) {
                // Format event date for display
                $formattedDate = \Carbon\Carbon::parse($request->event_date)->format('F d, Y');
                $formattedTime = date('h:i A', strtotime($request->start_time)) . ' - ' . date('h:i A', strtotime($request->end_time));
                
                // Get package name
                $packageName = $package->package_name;
                
                // Get client name
                $clientName = $request->full_name;
                
                // Determine the route based on provider type
                $routeName = $request->type === 'studio' 
                    ? 'owner.booking.index' 
                    : 'freelancer.booking.index';
                
                // Create notification data array with booking details
                $notificationData = [
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference,
                    'client_name' => $clientName,
                    'client_id' => Auth::id(),
                    'event_date' => $request->event_date,
                    'event_time' => $request->start_time . ' - ' . $request->end_time,
                    'package_name' => $packageName,
                    'package_price' => $package->package_price,
                    'down_payment' => $downPayment,
                    'remaining_balance' => $remainingBalance,
                    'total_amount' => $totalAmount,
                    'payment_type' => $paymentType,
                    'deposit_policy' => $depositPolicy,
                    'location_type' => $request->location_type,
                    'category_id' => $request->category_id,
                    'provider_type' => $providerType,
                    'provider_name' => $providerName,
                    'route' => route($routeName, [], false)
                ];
                
                // Add location details if available
                if ($request->location_type === 'on-location') {
                    if ($request->has('locations') && is_array($request->locations) && count($request->locations) > 0) {
                        $notificationData['locations'] = $request->locations;
                        $notificationData['location_count'] = count($request->locations);
                        $notificationData['has_multiple_locations'] = true;
                    } else {
                        $notificationData['city'] = $request->city;
                        $notificationData['barangay'] = $request->barangay;
                        $notificationData['street'] = $request->street;
                        $notificationData['venue_name'] = $request->venue_name;
                        $notificationData['has_multiple_locations'] = false;
                    }
                }
                
                // Add special requests if any
                if ($request->special_requests) {
                    $notificationData['has_special_requests'] = true;
                    $notificationData['special_requests'] = $request->special_requests;
                }
                
                // Create the notification with appropriate icon and color
                $this->createNotification(
                    $providerUserId,                                            // recipient: provider's user ID
                    'new_booking',                                              // notification type
                    '📸 New Booking Received!',                                 // title with emoji for attention
                    "A new booking has been made by {$clientName} for {$packageName} on {$formattedDate}.", // message
                    $notificationData,                                           // data payload
                    'calendar-check',                                            // icon (using Lucide icon name)
                    'primary'                                                    // color
                );
                
                \Log::info('New booking notification created', [
                    'provider_user_id' => $providerUserId,
                    'provider_type' => $providerType,
                    'booking_id' => $booking->id,
                    'booking_reference' => $booking->booking_reference
                ]);
            }

            return response()->json([
                'success' => true,
                'booking' => $booking,
                'payment' => $payment,
                'message' => 'Booking created successfully',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create booking: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Validate date availability
     */
    private function checkDateAvailability($request)
    {
        try {
            $selectedDate = Carbon::parse($request->event_date);
            $dayOfWeek = strtolower($selectedDate->format('l'));
            
            // Check if date is in the past
            if ($selectedDate->isPast()) {
                return [
                    'success' => false,
                    'available' => false,
                    'message' => 'Selected date is in the past.'
                ];
            }

            // Get provider schedule
            if ($request->type === 'studio') {
                $provider = StudiosModel::with('schedules')->find($request->provider_id);
                
                if (!$provider || !$provider->schedules || $provider->schedules->isEmpty()) {
                    return [
                        'success' => false,
                        'available' => false,
                        'message' => 'Studio has no schedule available.'
                    ];
                }
                
                $schedule = $provider->schedules->first();
                $operatingDays = $schedule->operating_days ?? [];
                
                if (is_string($operatingDays)) {
                    $operatingDays = json_decode($operatingDays, true) ?? [];
                }
                
                if (!in_array($dayOfWeek, $operatingDays)) {
                    return [
                        'success' => false,
                        'available' => false,
                        'message' => 'Studio is closed on ' . ucfirst($dayOfWeek) . '.'
                    ];
                }
                
                $maxBookings = $provider->max_clients_per_day ?? 3;
            } else {
                $provider = ProfileModel::with('schedule')->where('user_id', $request->provider_id)->first();
                
                if (!$provider || !$provider->schedule) {
                    return [
                        'success' => false,
                        'available' => false,
                        'message' => 'Freelancer has no schedule available.'
                    ];
                }
                
                $schedule = $provider->schedule;
                $operatingDays = $schedule->operating_days ?? [];
                
                if (is_string($operatingDays)) {
                    $operatingDays = json_decode($operatingDays, true) ?? [];
                }
                
                if (!in_array($dayOfWeek, $operatingDays)) {
                    return [
                        'success' => false,
                        'available' => false,
                        'message' => 'Freelancer is not available on ' . ucfirst($dayOfWeek) . '.'
                    ];
                }
                
                $maxBookings = $schedule->booking_limit ?? 3;
            }

            // Get existing bookings
            $existingBookings = BookingModel::where('booking_type', $request->type)
                ->where('provider_id', $request->provider_id)
                ->where('event_date', $request->event_date)
                ->whereIn('status', ['pending', 'confirmed'])
                ->count();

            $available = $existingBookings < $maxBookings;

            return [
                'success' => true,
                'available' => $available,
                'message' => $available ? 
                    'Date is available.' : 
                    'Date is fully booked.'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'available' => false,
                'message' => 'Error checking date availability.'
            ];
        }
    }

    /**
     * Validate package selection
     */
    private function validatePackage($request)
    {
        try {
            if ($request->type === 'studio') {
                $package = StudioPackagesModel::where('id', $request->package_id)
                    ->where('studio_id', $request->provider_id)
                    ->where('category_id', $request->category_id)
                    ->where('status', 'active')
                    ->first();
            } else {
                $package = FreelancerPackagesModel::where('id', $request->package_id)
                    ->where('user_id', $request->provider_id)
                    ->where('category_id', $request->category_id)
                    ->where('status', 'active')
                    ->first();
            }

            if (!$package) {
                return [
                    'valid' => false,
                    'message' => 'Selected package is not available or does not exist for this category.'
                ];
            }

            return [
                'valid' => true,
                'package' => $package
            ];

        } catch (\Exception $e) {
            return [
                'valid' => false,
                'message' => 'Error validating package.'
            ];
        }
    }

    /**
     * Check if the requested booking location type is supported by the selected package.
     */
    private function isLocationTypeAllowedForPackage($package, string $type, string $locationType): bool
    {
        if ($type === 'freelancer') {
            return $locationType === 'on-location';
        }

        $packageLocations = $this->normalizePackageLocations($package->package_location ?? []);
        $requestedLocation = $locationType === 'in-studio' ? 'In-Studio' : 'On-Location';

        return in_array($requestedLocation, $packageLocations, true);
    }

    /**
     * Check if the selected package supports multiple event locations.
     */
    private function packageAllowsMultipleLocations($package, string $type): bool
    {
        if (!(bool) ($package->allow_multiple_locations ?? false) || (int) ($package->max_locations ?? 1) <= 1) {
            return false;
        }

        if ($type === 'freelancer') {
            return true;
        }

        return in_array('On-Location', $this->normalizePackageLocations($package->package_location ?? []), true);
    }

    /**
     * Normalize package location values from JSON/string/array storage into known labels.
     */
    private function normalizePackageLocations($packageLocation): array
    {
        if (is_string($packageLocation)) {
            $decodedLocation = json_decode($packageLocation, true);
            $packageLocation = is_array($decodedLocation) ? $decodedLocation : [$packageLocation];
        }

        if (!is_array($packageLocation)) {
            $packageLocation = $packageLocation ? [$packageLocation] : [];
        }

        return collect($packageLocation)
            ->map(function ($location) {
                return trim((string) $location, "\"' ");
            })
            ->filter(function ($location) {
                return in_array($location, ['In-Studio', 'On-Location'], true);
            })
            ->values()
            ->all();
    }

    /**
     * Normalize coverage scope into the string shape expected by booking snapshots.
     */
    private function normalizeCoverageScope($coverageScope): ?string
    {
        if (is_array($coverageScope)) {
            $coverageScope = array_values(array_filter(array_map(function ($value) {
                if (is_scalar($value) || $value === null) {
                    $text = trim((string) $value);
                    return $text !== '' ? $text : null;
                }

                return null;
            }, $coverageScope)));

            return !empty($coverageScope) ? implode(', ', $coverageScope) : null;
        }

        if (is_string($coverageScope)) {
            $trimmedCoverageScope = trim($coverageScope);
            return $trimmedCoverageScope !== '' ? $trimmedCoverageScope : null;
        }

        if (is_scalar($coverageScope)) {
            $stringValue = trim((string) $coverageScope);
            return $stringValue !== '' ? $stringValue : null;
        }

        return null;
    }

    /**
     * Initialize Payment with proper redirect URLs
     */
    public function initializePayment(Request $request)
    {
        $request->validate([
            'booking_id' => 'required|exists:tbl_bookings,id',
        ]);

        $booking = BookingModel::with('client')->findOrFail($request->booking_id);
        
        // Get the pending payment (either for initial or balance payment)
        $payment = $booking->payments()->where('status', 'pending')->latest()->first();

        if (!$payment) {
            return response()->json([
                'success' => false,
                'message' => 'No pending payment found',
            ], 404);
        }

        try {
            // Determine payment description
            $totalPaid = $booking->payments()->where('status', 'succeeded')->sum('amount');
            $isBalancePayment = $totalPaid > 0;

            // Get the downpayment percentage for display
            $downpaymentText = '30%'; // Default
            if ($booking->booking_type === 'studio') {
                $studio = StudiosModel::find($booking->provider_id);
                if ($studio && $studio->downpayment_percentage) {
                    $downpaymentText = $studio->downpayment_percentage . '%';
                }
            }

            $description = $isBalancePayment 
                ? 'Balance Payment - ' . $booking->booking_reference 
                : 'Booking ' . ($booking->payment_type === 'downpayment' ? $downpaymentText . ' Deposit' : 'Payment') . ' - ' . $booking->booking_reference;

            // Create Stripe checkout session
            $checkoutSession = $this->stripeService->createCheckoutSession(
                $payment->amount,
                $booking->booking_reference,
                'PHP',
                $description
            );

            if ($checkoutSession) {
                // Prepare payment details array
                $paymentDetails = $payment->payment_details ?? [];
                $paymentDetails['checkout_session_created'] = true;
                $paymentDetails['checkout_url'] = $checkoutSession['url'];
                $paymentDetails['session_id'] = $checkoutSession['id'];
                $paymentDetails['amount'] = $payment->amount;
                $paymentDetails['created_at'] = now()->toDateTimeString();
                $paymentDetails['mode'] = config('services.stripe.mode', 'test');
                $paymentDetails['is_balance_payment'] = $isBalancePayment;
                
                // Update payment with checkout session ID
                $payment->update([
                    'stripe_session_id' => $checkoutSession['id'],
                    'payment_method' => 'stripe_checkout',
                    'payment_details' => $paymentDetails,
                ]);

                Log::info('Stripe Checkout Session Created', [
                    'booking_id' => $booking->id,
                    'session_id' => $checkoutSession['id'],
                    'checkout_url' => $checkoutSession['url'],
                    'is_balance_payment' => $isBalancePayment,
                ]);

                return response()->json([
                    'success' => true,
                    'type' => 'checkout',
                    'redirect_url' => $checkoutSession['url'],
                    'session_id' => $checkoutSession['id'],
                    'booking_reference' => $booking->booking_reference,
                    'mode' => config('services.stripe.mode', 'test'),
                    'is_balance_payment' => $isBalancePayment,
                    'note' => $isBalancePayment 
                        ? 'You are paying the remaining balance for your booking.' 
                        : 'You will be redirected to Stripe for payment.',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Payment initialization failed. Please try again or contact support.',
                'test_mode_note' => config('services.stripe.mode', 'test') === 'test' ? 
                    'Note: You are in test mode. Use test card: 4242424242424242' : 
                    '',
            ], 500);

        } catch (\Exception $e) {
            Log::error('Payment initialization error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Payment initialization failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
    * Verify payment after redirect from Stripe
    */
    public function verifyPayment($reference, Request $request)
    {
        if (!$reference) {
            return view('client.payment-callback-error', [
                'message' => 'No booking reference provided.',
                'contact_support' => true,
            ]);
        }

        Log::info('Payment verification triggered', ['reference' => $reference]);

        // Find booking
        $booking = BookingModel::where('booking_reference', $reference)->first();
        
        if (!$booking) {
            return view('client.payment-callback-error', [
                'message' => 'Booking not found.',
                'reference' => $reference,
                'contact_support' => true,
            ]);
        }

        // Find pending payment
        $payment = $booking->payments()->where('status', 'pending')->first();
        
        if (!$payment) {
            // Check if already paid
            $paidPayment = $booking->payments()->where('status', 'succeeded')->first();
            if ($paidPayment) {
                return redirect()->route('client.payment.success', ['reference' => $reference]);
            }
            
            return view('client.payment-callback-error', [
                'message' => 'No pending payment found.',
                'reference' => $reference,
                'contact_support' => true,
            ]);
        }

        // Get session ID from request or payment details
        $sessionId = request()->query('session_id') ?? $payment->stripe_session_id;
        
        if (!$sessionId) {
            if ($payment->status === 'succeeded') {
                return redirect()->route('client.payment.success', ['reference' => $reference]);
            }
            
            return view('client.payment-callback-error', [
                'message' => 'Payment session not found.',
                'reference' => $reference,
                'contact_support' => true,
            ]);
        }

        try {
            // Check Stripe checkout session status
            $sessionData = $this->stripeService->retrieveCheckoutSession($sessionId);
            
            if (!$sessionData) {
                return view('client.payment-verifying', [
                    'booking' => $booking,
                    'payment' => $payment,
                    'status' => 'checking',
                    'message' => 'Checking payment status with Stripe...',
                ]);
            }

            $status = $sessionData['payment_status'] ?? 'unknown';
            
            Log::info('Stripe checkout session status', [
                'session_id' => $sessionId,
                'status' => $status,
                'reference' => $reference,
            ]);

            if ($status === 'paid') {
                // Payment successful!
                $paymentDetails = $payment->payment_details ?? [];
                $paymentDetails['verified_at'] = now()->toDateTimeString();
                $paymentDetails['stripe_status'] = $status;
                $paymentDetails['session_id'] = $sessionId;
                
                $payment->update([
                    'status' => 'succeeded',
                    'paid_at' => now(),
                    'payment_method' => 'card',
                    'payment_details' => $paymentDetails,
                ]);

                // ========== FIXED: Update booking payment status and remaining balance ==========
                $booking->updatePaymentStatus();
                
                // Only update booking status to confirmed if it's the first payment
                $isBalancePayment = $paymentDetails['is_balance_payment'] ?? false;
                if (!$isBalancePayment && $booking->status === 'pending') {
                    $booking->update([
                        'status' => 'confirmed',
                    ]);
                }
                
                // Log payment info
                Log::info('Stripe payment verified successfully', [
                    'payment_id' => $payment->id,
                    'booking_id' => $booking->id,
                    'amount' => $payment->amount,
                    'total_paid' => $booking->total_paid,
                    'payment_status' => $booking->payment_status,
                    'remaining_balance' => $booking->remaining_balance,
                    'is_balance_payment' => $isBalancePayment,
                ]);

                // Create revenue record for this payment
                SystemRevenueModel::createForPayment($booking, $payment);
                $this->updateClientBudgetSpending($booking, $payment);

                return redirect()->route('client.payment.success', ['reference' => $reference]);

            } elseif ($status === 'unpaid' || $status === 'open') {
                // Payment not yet completed
                return view('client.payment-verifying', [
                    'booking' => $booking,
                    'payment' => $payment,
                    'status' => 'pending',
                    'message' => 'Payment is still pending. Please wait or try again.',
                    'retry_url' => $payment->payment_details['checkout_url'] ?? route('client.payments.initialize'),
                ]);
                
            } else {
                return redirect()->route('client.payment.failed', ['reference' => $reference]);
            }

        } catch (\Exception $e) {
            Log::error('Payment verification error: ' . $e->getMessage());
            
            // Check if payment was already succeeded (webhook might have processed it)
            $updatedPayment = PaymentModel::find($payment->id);
            if ($updatedPayment && $updatedPayment->status === 'succeeded') {
                return redirect()->route('client.payment.success', ['reference' => $reference]);
            }
            
            return view('client.payment-verifying', [
                'booking' => $booking,
                'payment' => $payment,
                'status' => 'error',
                'message' => 'Error verifying payment. Please contact support.',
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Test Stripe API connection
     */
    public function testStripeConnection()
    {
        $result = $this->stripeService->testConnection();
        
        // Add additional info for test mode
        if ($this->stripeService->isTestMode) {
            $result['test_mode_info'] = [
                'test_card' => '4242424242424242',
                'test_expiry' => 'Any future date (e.g., 12/30)',
                'test_cvv' => 'Any 3 digits',
                'note' => 'Use test cards only in test mode',
            ];
        }
        
        return response()->json($result);
    }

    /**
     * Handle Stripe webhook for checkout session updates
     */
    public function handleStripeWebhook(Request $request)
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');
        $webhookSecret = config('services.stripe.webhook_secret');
        
        Log::info('Stripe Webhook Received', ['signature' => $signature]);
        
        try {
            $event = $this->stripeService->verifyWebhookSignature($payload, $signature, $webhookSecret);
            
            if (!$event) {
                Log::error('Stripe webhook verification failed');
                return response()->json(['error' => 'Invalid signature'], 400);
            }
            
            switch ($event->type) {
                case 'checkout.session.completed':
                    $session = $event->data->object;
                    
                    // Find payment by session ID
                    $payment = PaymentModel::where('stripe_session_id', $session->id)->first();
                    
                    if ($payment && $payment->status !== 'succeeded') {
                        $payment->update([
                            'status' => 'succeeded',
                            'paid_at' => now(),
                            'payment_method' => 'card',
                        ]);
                        
                        // Update booking
                        $booking = $payment->booking;
                        if ($booking) {
                            if ($booking->payment_type === 'full_payment') {
                                $paymentStatus = 'paid';
                            } else {
                                $paymentStatus = 'partially_paid';
                            }
                            
                            $booking->update([
                                'payment_status' => $paymentStatus,
                                'status' => 'confirmed',
                            ]);
                            
                            // ADD THIS LINE FOR REVENUE SPLIT IN WEBHOOK
                            $this->createRevenueRecord($booking, $payment);
                            $this->updateClientBudgetSpending($booking, $payment);
                        }

                        Log::info('Payment updated via Stripe webhook', [
                            'payment_id' => $payment->id,
                            'booking_id' => $booking->id ?? 'N/A',
                            'session_id' => $session->id,
                        ]);
                    }
                    break;
                    
                case 'payment_intent.succeeded':
                    $paymentIntent = $event->data->object;
                    Log::info('Payment intent succeeded', ['intent_id' => $paymentIntent->id]);
                    break;

                case 'payment_intent.payment_failed':
                    $paymentIntent = $event->data->object;

                    $payment = PaymentModel::where('stripe_payment_intent_id', $paymentIntent->id)->first();

                    if ($payment && $payment->status !== 'failed') {
                        $payment->update(['status' => 'failed']);

                        $booking = $payment->booking;
                        if ($booking && $booking->client) {
                            $this->notifyPaymentFailed($payment, $booking->client);
                        }

                        Log::info('Payment marked failed via Stripe webhook', [
                            'payment_id' => $payment->id,
                            'intent_id' => $paymentIntent->id,
                        ]);
                    }
                    break;
            }
            
            return response()->json(['success' => true]);
            
        } catch (\Exception $e) {
            Log::error('Stripe webhook error: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    /**
     * Test Paymongo API connection
     */
    public function testPaymongoConnection()
    {
        $result = $this->paymongoService->testConnection();
        
        // Add additional info for test mode
        if ($this->paymongoService->isTestMode) {
            $result['test_mode_info'] = [
                'limitation' => 'Test mode only supports card payments',
                'test_card' => '4111111111111111',
                'test_expiry' => 'Any future date (e.g., 12/30)',
                'test_cvv' => 'Any 3 digits',
                'note' => 'GCash, GrabPay, Maya require live merchant accounts',
            ];
        }
        
        return response()->json($result);
    }

    /**
     * Fallback: Create simple checkout session
     */
    private function createSimpleCheckoutSession($payment, $booking)
    {
        try {
            $successUrl = route('client.payment.success') . '?reference=' . $booking->booking_reference;
            $failedUrl = route('client.payment.failed');
            
            $response = Http::withBasicAuth(config('services.paymongo.secret_key'), '')
                ->post('https://api.paymongo.com/v1/checkout_sessions', [
                    'data' => [
                        'attributes' => [
                            'cancel_url' => $failedUrl,
                            'success_url' => $successUrl,
                            'line_items' => [[
                                'amount' => $payment->amount * 100,
                                'currency' => 'PHP',
                                'name' => 'Booking Deposit - ' . $booking->booking_reference,
                                'quantity' => 1,
                            ]],
                            // IMPORTANT: For test mode, don't specify payment_method_types
                            // Let Paymongo decide what's available
                            'description' => 'Booking deposit for photography services',
                        ],
                    ],
                ]);
            
            if ($response->successful()) {
                $data = $response->json()['data'];
                
                $payment->update([
                    'paymongo_source_id' => $data['id'],
                    'payment_details' => json_encode([
                        'checkout_session_created' => true,
                        'checkout_url' => $data['attributes']['checkout_url'],
                        'created_at' => now()->toDateTimeString(),
                    ]),
                ]);

                return response()->json([
                    'success' => true,
                    'redirect_url' => $data['attributes']['checkout_url'],
                    'session_id' => $data['id'],
                ]);
            }
            
            // If both methods fail, show manual payment instructions for testing
            return response()->json([
                'success' => false,
                'message' => 'Payment gateway not available. For testing purposes, please use the test credentials below.',
                'test_mode' => true,
                'booking_reference' => $booking->booking_reference,
                'amount' => $payment->amount,
                'manual_test_instructions' => 'Since Paymongo test mode is not showing payment methods, you can simulate payment by marking this booking as paid manually in the database.',
            ], 500);
            
        } catch (\Exception $e) {
            Log::error('Simple checkout error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Handle successful payment redirect from Stripe
     */
    public function paymentSuccess(Request $request)
    {
        try {
            \Log::info('Payment success callback', [
                'query_params' => $request->query(),
                'referer' => $request->header('referer'),
                'path' => $request->path()
            ]);
            
            // Get reference from route parameter
            $reference = $request->route('reference');
            
            if (!$reference) {
                \Log::error('No booking reference found in success callback');
                return view('client.payment-callback-error', [
                    'message' => 'No booking reference provided.',
                    'contact_support' => true,
                ]);
            }
            
            // Find booking by reference
            $booking = BookingModel::where('booking_reference', $reference)->first();
            
            if (!$booking) {
                \Log::error('Booking not found for reference', ['reference' => $reference]);
                return view('client.payment-callback-error', [
                    'message' => 'Payment received but booking not found.',
                    'reference' => $reference,
                    'contact_support' => true,
                ]);
            }
            
            // Check if we have a session_id in query (direct from Stripe)
            $sessionId = $request->query('session_id');
            
            if ($sessionId) {
                // If we came directly from Stripe with session_id, redirect to verifyPayment
                \Log::info('Redirecting to verifyPayment from paymentSuccess', [
                    'reference' => $reference,
                    'session_id' => $sessionId
                ]);
                
                return redirect()->route('client.payment.verify', [
                    'reference' => $reference,
                    'session_id' => $sessionId
                ]);
            }
            
            // Find the successful payment
            $payment = $booking->payments()->where('status', 'succeeded')->latest()->first();
            
            if (!$payment) {
                // Check for any payment
                $payment = $booking->payments()->latest()->first();
                
                \Log::warning('No successful payment found for booking, showing verifying page', [
                    'booking_id' => $booking->id,
                    'payment_status' => $payment ? $payment->status : 'no payment'
                ]);
                
                // Show verifying page since payment isn't verified yet
                return view('client.payment-verifying', [
                    'booking' => $booking,
                    'payment' => $payment,
                    'status' => 'checking',
                    'message' => 'Verifying your payment...',
                ]);
            }
            
            \Log::info('Showing payment success page', [
                'booking_id' => $booking->id,
                'payment_id' => $payment->id,
                'reference' => $reference,
            ]);
            
            return view('client.payment-success', [
                'booking' => $booking,
                'payment' => $payment,
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Payment success error: ' . $e->getMessage());
            return view('client.payment-callback-error', [
                'message' => 'An error occurred while processing your payment.',
                'reference' => 'Error: ' . $e->getMessage(),
                'contact_support' => true,
            ]);
        }
    }

    /**
     * Process successful payment and update records
     */
    private function processSuccessfulPayment($payment)
    {
        // Check if already processed
        if ($payment->status === 'succeeded') {
            \Log::info('Payment already marked as succeeded', ['payment_id' => $payment->id]);
            return view('client.payment-success', [
                'booking' => $payment->booking,
                'payment' => $payment,
            ]);
        }
        
        // Mark payment as succeeded
        $payment->update([
            'status' => 'succeeded',
            'paid_at' => now(),
            'payment_details' => array_merge(
                $payment->payment_details ?? [],
                [
                    'paid_at' => now()->toDateTimeString(),
                    'verified' => true,
                    'processed_via' => 'redirect_callback',
                    'callback_received' => now()->toDateTimeString(),
                ]
            ),
        ]);
        
        // Update booking status
        $payment->booking()->update([
            'payment_status' => 'partially_paid',
            'status' => 'confirmed',
        ]);

        $this->updateClientBudgetSpending($payment->booking()->first(), $payment);

        \Log::info('Payment marked as successful via callback', [
            'payment_id' => $payment->id,
            'booking_id' => $payment->booking_id,
            'amount' => $payment->amount,
        ]);
        
        return view('client.payment-success', [
            'booking' => $payment->booking,
            'payment' => $payment,
        ]);
    }

    /**
     * Handle failed payment
     */
    public function paymentFailed(Request $request)
    {
        $checkoutId = $request->query('checkout_session_id');
        
        if ($checkoutId) {
            $payment = PaymentModel::where('paymongo_source_id', $checkoutId)->first();
            
            if ($payment) {
                $payment->update([
                    'status' => 'failed',
                    'payment_details' => array_merge(
                        $payment->payment_details ?? [],
                        ['failed_at' => now()->toDateTimeString()]
                    ),
                ]);
            }
        }
        
        return view('client.payment-callback-error');
    }

    /**
     * Get booking summary
     */
    public function getSummary(Request $request)
    {
        $request->validate([
            'package_id' => 'required|integer',
            'type' => 'required|in:studio,freelancer',
            // payment_type no longer required for freelancer
        ]);

        if ($request->type === 'studio') {
            $package = StudioPackagesModel::findOrFail($request->package_id);
            // Get downpayment percentage
            $studio = StudiosModel::find($package->studio_id);
            $downpaymentPercentage = $studio->downpayment_percentage ?? 30;
            $paymentType = 'downpayment';
        } else {
            $package = FreelancerPackagesModel::findOrFail($request->package_id);
            
            // ========== FIX: Get freelancer deposit settings ==========
            $freelancer = ProfileModel::where('user_id', $package->user_id)->first();
            
            if ($freelancer && $freelancer->deposit_policy === 'required') {
                // Deposit is required
                $paymentType = 'downpayment';
                
                if ($freelancer->deposit_type === 'percentage') {
                    $downpaymentPercentage = $freelancer->deposit_amount ?? 30;
                } elseif ($freelancer->deposit_type === 'fixed') {
                    $downpaymentPercentage = 0; // Not applicable for fixed amount
                } else {
                    $downpaymentPercentage = 30; // Fallback
                }
            } else {
                // No deposit required - full payment
                $paymentType = 'full_payment';
                $downpaymentPercentage = 100;
            }
            // ========== End of freelancer deposit logic ==========
        }

        $totalAmount = $package->package_price;
        
        if ($paymentType === 'downpayment') {
            if ($request->type === 'freelancer' && isset($freelancer) && 
                $freelancer->deposit_policy === 'required' && $freelancer->deposit_type === 'fixed') {
                // Fixed amount deposit
                $downPayment = $freelancer->deposit_amount ?? 0;
                if ($downPayment > $totalAmount) {
                    $downPayment = $totalAmount;
                }
            } else {
                // Percentage-based deposit
                $downPayment = ($totalAmount * $downpaymentPercentage) / 100;
            }
            $remainingBalance = $totalAmount - $downPayment;
        } else {
            $downPayment = $totalAmount;
            $remainingBalance = 0;
        }

        $packageData = [
            'package_name' => $package->package_name,
            'package_price' => number_format($package->package_price, 2),
            'total_amount' => number_format($totalAmount, 2),
            'down_payment' => number_format($downPayment, 2),
            'downpayment_percentage' => $downpaymentPercentage,
            'remaining_balance' => number_format($remainingBalance, 2),
            'inclusions' => $package->package_inclusions,
            'duration' => $package->duration,
            'maximum_edited_photos' => $package->maximum_edited_photos,
            'payment_type' => $paymentType,
            'online_gallery' => $package->online_gallery ?? false,
            'gallery_status' => ($package->online_gallery ?? false) ? 'Included' : 'Not Included',
            'package_location' => $package->package_location ?? 'In-Studio',
        ];
        
        // Add fixed deposit info for freelancer if applicable
        if ($request->type === 'freelancer' && isset($freelancer) && 
            $freelancer->deposit_policy === 'required' && $freelancer->deposit_type === 'fixed') {
            $packageData['deposit_type'] = 'fixed';
            $packageData['deposit_amount'] = $freelancer->deposit_amount;
            $packageData['deposit_display'] = 'Fixed Deposit: ₱' . number_format($freelancer->deposit_amount, 2);
        }
        
        if ($request->type === 'studio') {
            $packageData['photographer_count'] = $package->photographer_count ?? 1;
            $packageData['photographer_text'] = $package->photographer_count . ' photographer' . ($package->photographer_count > 1 ? 's' : '');
        }

        return response()->json([
            'success' => true,
            'summary' => $packageData,
        ]);
    }

    private function handleCheckoutSessionCompleted($data)
    {
        $checkoutSessionId = $data['id'];
        $status = $data['attributes']['status'] ?? null;
        
        if ($status === 'paid') {
            $payment = PaymentModel::where('paymongo_source_id', $checkoutSessionId)->first();
            
            if ($payment && $payment->status !== 'succeeded') {
                $payment->markAsPaid();
                $payment->booking()->update([
                    'payment_status' => 'partially_paid',
                    'status' => 'confirmed',
                ]);
                
                Log::info('Payment marked as paid via webhook', [
                    'payment_id' => $payment->id,
                    'booking_id' => $payment->booking_id,
                ]);
            }
        }
    }

    private function handlePaymentPaid($data)
    {
        // Handle paid payment webhook
        $paymentId = $data['attributes']['data']['id'] ?? null;
        
        if ($paymentId) {
            $payment = PaymentModel::where('paymongo_payment_id', $paymentId)->first();
            
            if ($payment) {
                $payment->markAsPaid();
                $payment->booking()->update(['payment_status' => 'partially_paid']);
            }
        }
    }

    /**
     * Test method to check Paymongo API directly
     */
    public function testPaymongoApi()
    {
        try {
            // Test 1: Create a simple checkout session
            $response = Http::withBasicAuth(config('services.paymongo.secret_key'), '')
                ->post('https://api.paymongo.com/v1/checkout_sessions', [
                    'data' => [
                        'attributes' => [
                            'cancel_url' => route('client.payment.failed'),
                            'success_url' => route('client.payment.success') . '?test=true',
                            'line_items' => [[
                                'amount' => 10000, // 100 PHP
                                'currency' => 'PHP',
                                'name' => 'Test Payment',
                                'quantity' => 1,
                            ]],
                            'payment_method_types' => ['card', 'gcash'],
                            'description' => 'Test Payment',
                        ],
                    ],
                ]);
            
            if ($response->successful()) {
                $data = $response->json()['data'];
                return response()->json([
                    'success' => true,
                    'message' => 'Paymongo API is working',
                    'checkout_url' => $data['attributes']['checkout_url'],
                    'session_id' => $data['id'],
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Paymongo API test failed',
                'error' => $response->json(),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Exception: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Get calendar availability for a full month
     */
    public function getCalendarAvailability(Request $request)
    {
        $request->validate([
            'type'        => 'required|in:studio,freelancer',
            'provider_id' => 'required|integer',
            'year'        => 'required|integer',
            'month'       => 'required|integer|between:1,12',
        ]);

        try {
            $startDate = Carbon::create($request->year, $request->month, 1);
            $endDate   = $startDate->copy()->endOfMonth();

            // Get provider schedule
            if ($request->type === 'studio') {
                $provider = StudiosModel::with('schedules')->find($request->provider_id);

                if (!$provider || !$provider->schedules || $provider->schedules->isEmpty()) {
                    return response()->json([
                        'success'      => false,
                        'message'      => 'Studio has no schedule available.',
                        'availability' => [],
                    ]);
                }

                $schedule      = $provider->schedules->first();
                $operatingDays = $schedule->operating_days ?? [];

                if (is_string($operatingDays)) {
                    $operatingDays = json_decode($operatingDays, true) ?? [];
                }

                $maxBookings = $provider->max_clients_per_day ?? 3;

            } else {
                $provider = ProfileModel::with('schedule')->where('user_id', $request->provider_id)->first();

                if (!$provider || !$provider->schedule) {
                    return response()->json([
                        'success'      => false,
                        'message'      => 'Freelancer has no schedule available.',
                        'availability' => [],
                    ]);
                }

                $schedule      = $provider->schedule;
                $operatingDays = $schedule->operating_days ?? [];

                if (is_string($operatingDays)) {
                    $operatingDays = json_decode($operatingDays, true) ?? [];
                }

                $maxBookings = $schedule->booking_limit ?? 3;
            }

            // Get booking counts per date for the month
            $existingBookings = BookingModel::where('booking_type', $request->type)
                ->where('provider_id', $request->provider_id)
                ->whereBetween('event_date', [$startDate, $endDate])
                ->whereIn('status', ['pending', 'confirmed'])
                ->selectRaw('event_date, COUNT(*) as booking_count')
                ->groupBy('event_date')
                ->get()
                ->keyBy(function ($item) {
                    return Carbon::parse($item->event_date)->format('Y-m-d');
                });

            // Build availability data
            $availability = [];
            $currentDate  = $startDate->copy();
            $today        = Carbon::today();

            while ($currentDate->lte($endDate)) {
                $dateString    = $currentDate->format('Y-m-d');
                $dayOfWeek     = strtolower($currentDate->format('l'));
                $isPast        = $currentDate->lt($today);
                $isOperatingDay = in_array($dayOfWeek, $operatingDays);
                $bookingCount  = $existingBookings[$dateString]->booking_count ?? 0;

                // Fully booked if booking count has reached max limit
                $fullyBooked = $isOperatingDay && ($bookingCount >= $maxBookings);
                $available   = !$isPast && $isOperatingDay && !$fullyBooked;

                $availability[$dateString] = [
                    'date'            => $dateString,
                    'available'       => $available,
                    'fully_booked'    => $fullyBooked,
                    'not_operating'   => !$isOperatingDay,
                    'is_past'         => $isPast,
                    'is_operating_day' => $isOperatingDay,
                    'booking_count'   => $bookingCount,
                    'max_bookings'    => $maxBookings,
                ];

                $currentDate->addDay();
            }

            return response()->json([
                'success'        => true,
                'availability'   => $availability,
                'month'          => $startDate->format('F Y'),
                'max_bookings'   => $maxBookings,
                'operating_days' => $operatingDays,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get calendar availability: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify the Paymongo-Signature header against the raw payload.
     * Header format: t=<timestamp>,te=<test signature>,li=<live signature>
     */
    private function verifyPaymongoSignature(string $rawPayload, ?string $signatureHeader, string $secret): bool
    {
        if (!$signatureHeader || !$secret) {
            return false;
        }

        $parts = [];
        foreach (explode(',', $signatureHeader) as $pair) {
            [$key, $value] = array_pad(explode('=', $pair, 2), 2, null);
            if ($key !== null) {
                $parts[trim($key)] = trim((string) $value);
            }
        }

        $timestamp = $parts['t'] ?? null;
        $expectedSignature = $this->paymongoService->isTestMode ? ($parts['te'] ?? null) : ($parts['li'] ?? null);

        if (!$timestamp || !$expectedSignature) {
            return false;
        }

        $computedSignature = hash_hmac('sha256', $timestamp . '.' . $rawPayload, $secret);

        return hash_equals($expectedSignature, $computedSignature);
    }

    /**
     * Handle Paymongo webhook for checkout session updates
     */
    public function handleWebhook(Request $request)
    {
        $webhookSecret = config('services.paymongo.webhook_secret');

        if (!$this->verifyPaymongoSignature(
            $request->getContent(),
            $request->header('Paymongo-Signature'),
            (string) $webhookSecret
        )) {
            Log::error('Paymongo webhook verification failed');
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        $payload = $request->all();
        $eventType = $payload['data']['attributes']['type'] ?? null;

        Log::info('Paymongo Webhook Received', ['event_type' => $eventType]);

        if ($eventType === 'checkout_session.completed' || $eventType === 'checkout_session.paid') {
            $sessionData = $payload['data'];
            $sessionId = $sessionData['id'];
            $status = $sessionData['attributes']['status'] ?? null;

            if ($status === 'paid') {
                // Find payment by session ID
                $payment = PaymentModel::where('paymongo_source_id', $sessionId)->first();

                if ($payment && $payment->status !== 'succeeded') {
                    $payment->update([
                        'status' => 'succeeded',
                        'paid_at' => now(),
                        'payment_method' => $sessionData['attributes']['payment_method_used'] ?? 'card',
                    ]);

                    // Update booking
                    $booking = $payment->booking;
                    if ($booking) {
                        if ($booking->payment_type === 'full_payment') {
                            $paymentStatus = 'paid';
                        } else {
                            $paymentStatus = 'partially_paid';
                        }

                        $booking->update([
                            'payment_status' => $paymentStatus,
                            'status' => 'confirmed',
                        ]);

                        $this->createRevenueRecord($booking, $payment);
                        $this->updateClientBudgetSpending($booking, $payment);
                    }

                    Log::info('Payment updated via webhook', [
                        'payment_id' => $payment->id,
                        'booking_id' => $booking->id ?? 'N/A',
                        'status' => $status,
                    ]);
                }
            }
        } elseif ($eventType === 'payment.failed') {
            $sessionId = $payload['data']['attributes']['data']['attributes']['checkout_session_id']
                ?? $payload['data']['id']
                ?? null;

            $payment = $sessionId ? PaymentModel::where('paymongo_source_id', $sessionId)->first() : null;

            if ($payment && $payment->status !== 'failed') {
                $payment->update(['status' => 'failed']);

                $booking = $payment->booking;
                if ($booking && $booking->client) {
                    $this->notifyPaymentFailed($payment, $booking->client);
                }

                Log::info('Payment marked failed via Paymongo webhook', [
                    'payment_id' => $payment->id,
                ]);
            }
        }

        return response()->json(['success' => true]);
    }

    /**
     * Show card payment page (updated for Stripe)
     */
    public function showCardPayment($reference)
    {
        $booking = BookingModel::where('booking_reference', $reference)->firstOrFail();
        $payment = $booking->payments()->where('status', 'pending')->first();
        
        if (!$payment) {
            return redirect()->route('client.dashboard')->with('error', 'Payment session expired.');
        }
        
        // Create Stripe payment intent for direct card payment
        $paymentIntent = $this->stripeService->createPaymentIntent(
            $payment->amount,
            $booking->booking_reference,
            'PHP',
            'Booking ' . ($booking->payment_type === 'downpayment' ? 'Deposit' : 'Payment')
        );
        
        if (!$paymentIntent) {
            return redirect()->route('client.dashboard')->with('error', 'Failed to create payment session.');
        }
        
        // Update payment with intent ID
        $payment->update([
            'stripe_payment_intent_id' => $paymentIntent['id'],
        ]);
        
        return view('client.payment-card', [
            'reference' => $reference,
            'amount' => $payment->amount,
            'client_secret' => $paymentIntent['client_secret'],
            'stripe_key' => $this->stripeService->getPublicKey(),
        ]);
    }

    /**
     * Process Stripe payment (updated)
     */
    public function processStripePayment(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|string',
            'booking_reference' => 'required|string',
        ]);
        
        try {
            $booking = BookingModel::where('booking_reference', $request->booking_reference)->firstOrFail();
            $payment = $booking->payments()->where('status', 'pending')->first();
            
            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }
            
            // Confirm payment intent with Stripe
            $intent = $this->stripeService->stripe->paymentIntents->retrieve($payment->stripe_payment_intent_id);
            
            if ($intent->status === 'succeeded') {
                // Payment already succeeded (webhook may have processed it)
                $payment->update([
                    'status' => 'succeeded',
                    'paid_at' => now(),
                ]);
                
                // Update booking
                if ($booking->payment_type === 'full_payment') {
                    $paymentStatus = 'paid';
                } else {
                    $paymentStatus = 'partially_paid';
                }
                
                $booking->update([
                    'payment_status' => $paymentStatus,
                    'status' => 'confirmed',
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Payment successful',
                    'redirect_url' => route('client.payment.success', ['reference' => $booking->booking_reference]),
                ]);
            }
            
            // Try to confirm the payment
            $confirmedIntent = $this->stripeService->stripe->paymentIntents->confirm(
                $intent->id,
                ['payment_method' => $request->payment_method_id]
            );
            
            if ($confirmedIntent->status === 'succeeded') {
                // Payment successful
                $payment->update([
                    'status' => 'succeeded',
                    'paid_at' => now(),
                ]);
                
                // Update booking
                if ($booking->payment_type === 'full_payment') {
                    $paymentStatus = 'paid';
                } else {
                    $paymentStatus = 'partially_paid';
                }
                
                $booking->update([
                    'payment_status' => $paymentStatus,
                    'status' => 'confirmed',
                ]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Payment successful',
                    'redirect_url' => route('client.payment.success', ['reference' => $booking->booking_reference]),
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed. Status: ' . $confirmedIntent->status,
            ]);
            
        } catch (\Exception $e) {
            Log::error('Stripe payment processing error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Attach payment method to payment intent
     */
    public function attachPaymentMethod(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required|string',
            'booking_reference' => 'required|string',
        ]);
        
        try {
            $booking = BookingModel::where('booking_reference', $request->booking_reference)->firstOrFail();
            $payment = $booking->payments()->where('status', 'pending')->first();
            
            if (!$payment) {
                return response()->json([
                    'success' => false,
                    'message' => 'Payment not found',
                ], 404);
            }
            
            // Get payment details as array
            $paymentDetails = $payment->payment_details;
            
            // Attach payment method to payment intent
            $response = Http::withBasicAuth(config('services.paymongo.secret_key'), '')
                ->post('https://api.paymongo.com/v1/payment_intents/' . $payment->paymongo_source_id . '/attach', [
                    'data' => [
                        'attributes' => [
                            'payment_method' => $request->payment_method_id,
                            'return_url' => route('client.payment.verify', ['reference' => $booking->booking_reference]),
                        ],
                    ],
                ]);
            
            if ($response->successful()) {
                $responseData = $response->json()['data'];
                $status = $responseData['attributes']['status'];
                
                if ($status === 'succeeded') {
                    // Payment successful
                    $paymentDetails['attached_at'] = now()->toDateTimeString();
                    $paymentDetails['payment_method_id'] = $request->payment_method_id;
                    $paymentDetails['paymongo_status'] = $status;
                    
                    $payment->update([
                        'status' => 'succeeded',
                        'paid_at' => now(),
                        'payment_details' => $paymentDetails, // Will be cast to JSON
                    ]);
                    
                    // Update booking status
                    if ($booking->payment_type === 'full_payment') {
                        $paymentStatus = 'paid';
                    } else {
                        $paymentStatus = 'partially_paid';
                    }
                    
                    $booking->update([
                        'payment_status' => $paymentStatus,
                        'status' => 'confirmed',
                    ]);
                    
                    return response()->json([
                        'success' => true,
                        'message' => 'Payment successful',
                    ]);
                }
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Payment processing failed',
                'response' => $response->json(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Attach payment method error: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Payment processing error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Create system revenue record for successful payment.
     */
    private function createRevenueRecord($booking, $payment)
    {
        return SystemRevenueModel::createForPayment($booking, $payment);
    }

    /**
     * Increment the client's active budget for this booking's category by
     * the confirmed payment amount, and notify if the maximum is exceeded.
     */
    private function updateClientBudgetSpending($booking, $payment): void
    {
        if (!$booking->category_id) {
            return;
        }

        $budget = $this->getClientBudgetForCategory($booking->client_id, $booking->category_id);

        if (!$budget) {
            return;
        }

        $budget->increment('spent_amount', $payment->amount);

        if ($budget->maximum_budget !== null && $budget->spent_amount >= $budget->maximum_budget && $booking->client) {
            $this->notifyBudgetExceeded($budget, $booking->client);
        }
    }

    /**
     * Get barangays for selected municipality
     */
    public function getBarangays(Request $request)
    {
        $request->validate([
            'municipality' => 'required|string'
        ]);

        try {
            $location = \App\Models\Admin\LocationModel::where('municipality', $request->municipality)
                ->where('status', 'active')
                ->first();

            if (!$location) {
                return response()->json([
                    'success' => false,
                    'message' => 'Location not found',
                    'barangays' => []
                ]);
            }

            $barangays = $location->barangays;

            return response()->json([
                'success' => true,
                'barangays' => $barangays
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load barangays: ' . $e->getMessage(),
                'barangays' => []
            ], 500);
        }
    }

    /**
     * Get client budget for a specific category
     *
     * @param int $clientId
     * @param int $categoryId
     * @return \App\Models\ClientBudgetModel|null
     */
    private function getClientBudgetForCategory($clientId, $categoryId)
    {
        return ClientBudgetModel::where('client_id', $clientId)
            ->where('category_id', $categoryId)
            ->where('status', 'active')
            ->first();
    }

    /**
     * Calculate how well a package matches client budget
     *
     * @param float $packagePrice
     * @param \App\Models\ClientBudgetModel|null $clientBudget
     * @return array
     */
    private function calculateBudgetMatch($packagePrice, $clientBudget)
    {
        $match = [
            'has_budget' => false,
            'match_level' => 'no_budget',
            'match_percentage' => 0,
            'is_recommended' => false,
            'budget_range' => null
        ];
        
        if (!$clientBudget) {
            return $match;
        }
        
        $match['has_budget'] = true;
        $match['minimum_budget'] = $clientBudget->minimum_budget ? (float) $clientBudget->minimum_budget : null;
        $match['maximum_budget'] = $clientBudget->maximum_budget ? (float) $clientBudget->maximum_budget : null;
        $match['preferred_budget'] = $clientBudget->preferred_budget ? (float) $clientBudget->preferred_budget : null;
        
        // Format budget range for display
        $match['budget_range'] = $this->formatBudgetRange($clientBudget);
        
        // Determine if within range
        $min = $match['minimum_budget'] ?? 0;
        $max = $match['maximum_budget'] ?? PHP_FLOAT_MAX;
        $preferred = $match['preferred_budget'];
        
        if ($packagePrice >= $min && $packagePrice <= $max) {
            $match['match_level'] = 'within_range';
            
            // Calculate match percentage based on preferred budget if available
            if ($preferred && $preferred > 0) {
                $diff = abs($packagePrice - $preferred);
                $match['match_percentage'] = max(0, 100 - round(($diff / $preferred) * 100, 2));
                
                // Perfect match if within 10% of preferred budget
                if ($match['match_percentage'] >= 90) {
                    $match['match_level'] = 'perfect_match';
                    $match['is_recommended'] = true;
                }
            } else {
                // If no preferred budget, calculate based on position in range
                $rangeSize = $max - $min;
                if ($rangeSize > 0) {
                    $position = ($packagePrice - $min) / $rangeSize;
                    $match['match_percentage'] = round(100 - (abs(0.5 - $position) * 100), 2);
                    
                    if ($match['match_percentage'] >= 75) {
                        $match['is_recommended'] = true;
                    }
                }
            }
        } else {
            $match['match_level'] = 'above_budget';
            $match['match_percentage'] = 0;
        }
        
        return $match;
    }

    /**
     * Format budget range for display
     *
     * @param \App\Models\ClientBudgetModel $budget
     * @return string|null
     */
    private function formatBudgetRange($budget)
    {
        if (!$budget) {
            return null;
        }
        
        $parts = [];
        
        if ($budget->minimum_budget) {
            $parts[] = '₱' . number_format($budget->minimum_budget, 2);
        }
        
        if ($budget->maximum_budget) {
            $parts[] = '₱' . number_format($budget->maximum_budget, 2);
        }
        
        if (empty($parts)) {
            return null;
        }
        
        if (count($parts) === 1) {
            return $parts[0];
        }
        
        return implode(' - ', $parts);
    }

    /**
     * Get packages for selected category
     */
    public function getPackages(Request $request)
    {
        $request->validate([
            'type' => 'required|in:studio,freelancer',
            'provider_id' => 'required|integer',
            'category_id' => 'required|exists:tbl_categories,id',
        ]);

        try {
            // ========== START: DSS - Get client budget for this category ==========
            $clientBudget = null;
            if (Auth::check()) {
                $clientBudget = $this->getClientBudgetForCategory(Auth::id(), $request->category_id);
            }
            // ========== END: DSS - Get client budget ==========

            if ($request->type === 'studio') {
                // For studio, provider_id is studio_id
                $packages = StudioPackagesModel::where('studio_id', $request->provider_id)
                    ->where('category_id', $request->category_id)
                    ->where('status', 'active')
                    ->get()
                    ->map(function($package) use ($clientBudget) { // ADD clientBudget parameter
                        // ==== START: Enhanced location data handling with proper JSON parsing ====
                        // Get package location - handle JSON array format
                        $packageLocation = $package->package_location;
                        
                        // If it's a JSON string, decode it
                        if (is_string($packageLocation)) {
                            // Check if it looks like a JSON array
                            if (str_starts_with(trim($packageLocation), '[')) {
                                $decoded = json_decode($packageLocation, true);
                                $packageLocation = is_array($decoded) ? $decoded : [$packageLocation];
                            } else {
                                // If it's a single value without JSON brackets
                                $packageLocation = [$packageLocation];
                            }
                        }
                        
                        // Ensure it's always an array
                        if (!is_array($packageLocation)) {
                            $packageLocation = $packageLocation ? [$packageLocation] : ['In-Studio'];
                        }
                        
                        // Clean up values (remove any extra quotes or whitespace)
                        $packageLocation = array_map(function($location) {
                            return trim($location, '"\' ');
                        }, $packageLocation);
                        
                        // Remove any empty values
                        $packageLocation = array_filter($packageLocation);
                        
                        // Re-index array
                        $packageLocation = array_values($packageLocation);
                        
                        // Determine flexibility
                        $locationCount = count($packageLocation);
                        $locationFlexibility = [
                            'options' => $packageLocation,
                            'count' => $locationCount,
                            'is_flexible' => $locationCount > 1,
                            'single_option' => $locationCount === 1 ? $packageLocation[0] : null,
                            'raw' => $package->package_location // Keep raw for debugging if needed
                        ];
                        // ==== END: Enhanced location data handling with proper JSON parsing ====
                        
                        // ========== START: DSS - Calculate budget match ==========
                        $budgetMatch = $this->calculateBudgetMatch($package->package_price, $clientBudget);
                        // ========== END: DSS - Calculate budget match ==========
                        
                        return [
                            'id' => $package->id,
                            'package_name' => $package->package_name,
                            'package_description' => $package->package_description,
                            'package_price' => $package->package_price,
                            'duration' => $package->duration,
                            'maximum_edited_photos' => $package->maximum_edited_photos,
                            'package_inclusions' => $package->package_inclusions,
                            'coverage_scope' => $package->coverage_scope,
                            'online_gallery' => $package->online_gallery,
                            'photographer_count' => $package->photographer_count ?? 1,
                            // ==== START: Enhanced location data in response ====
                            'package_location' => $packageLocation,
                            'location_flexibility' => $locationFlexibility,
                            // ==== NEW: Multiple location fields ====
                            'allow_multiple_locations' => $package->allow_multiple_locations ?? false,
                            'max_locations' => $package->max_locations ?? 1,
                            // ==== END: Multiple location fields ====
                            'allow_time_customization' => $package->allow_time_customization,
                            'gallery_badge' => $package->online_gallery ? 'Yes' : 'No',
                            'gallery_icon' => $package->online_gallery ? 'ti ti-photo' : 'ti ti-photo-off',
                            'gallery_class' => $package->online_gallery ? 'success' : 'secondary',
                            'photographer_text' => $package->photographer_count . ' photographer' . ($package->photographer_count > 1 ? 's' : ''),
                            // ========== START: DSS - Add budget info to response ==========
                            'budget_info' => $budgetMatch
                            // ========== END: DSS - Add budget info ==========
                        ];
                    });
            } else {
                // For freelancer, provider_id is user_id
                $profile = ProfileModel::where('user_id', $request->provider_id)->first();
                
                if (!$profile) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Freelancer profile not found.',
                        'packages' => [],
                    ]);
                }
                
                $packages = FreelancerPackagesModel::where('user_id', $request->provider_id)
                    ->where('category_id', $request->category_id)
                    ->where('status', 'active')
                    ->get()
                    ->map(function($package) use ($profile, $clientBudget) { // ADD clientBudget parameter
                        // Parse package location - freelancers default to both options
                        $packageLocation = ['In-Studio', 'On-Location'];
                        
                        $locationCount = count($packageLocation);
                        $locationFlexibility = [
                            'options' => $packageLocation,
                            'count' => $locationCount,
                            'is_flexible' => $locationCount > 1,
                            'single_option' => $locationCount === 1 ? $packageLocation[0] : null
                        ];
                        
                        // ========== START: DSS - Calculate budget match ==========
                        $budgetMatch = $this->calculateBudgetMatch($package->package_price, $clientBudget);
                        // ========== END: DSS - Calculate budget match ==========
                        
                        return [
                            'id' => $package->id,
                            'package_name' => $package->package_name,
                            'package_description' => $package->package_description,
                            'package_price' => $package->package_price,
                            'duration' => $package->duration,
                            'maximum_edited_photos' => $package->maximum_edited_photos,
                            'package_inclusions' => $package->package_inclusions,
                            'coverage_scope' => $package->coverage_scope,
                            'online_gallery' => $package->online_gallery ?? false,
                            'package_location' => $packageLocation,
                            'location_flexibility' => $locationFlexibility,
                            'allow_multiple_locations' => $package->allow_multiple_locations ?? false,
                            'max_locations' => $package->max_locations ?? 1,
                            'freelancer_has_policy' => $profile->deposit_policy === 'required',
                            'freelancer_deposit_policy' => $profile->deposit_policy,
                            'freelancer_deposit_type' => $profile->deposit_type,
                            'freelancer_deposit_amount' => $profile->deposit_amount,
                            'allow_time_customization' => $package->allow_time_customization,
                            'gallery_badge' => ($package->online_gallery ?? false) ? 'Yes' : 'No',
                            'gallery_icon' => ($package->online_gallery ?? false) ? 'ti ti-photo' : 'ti ti-photo-off',
                            'gallery_class' => ($package->online_gallery ?? false) ? 'success' : 'secondary',
                            // ========== START: DSS - Add budget info to response ==========
                            'budget_info' => $budgetMatch
                            // ========== END: DSS - Add budget info ==========
                        ];
                    });
            }

            return response()->json([
                'success' => true,
                'packages' => $packages,
                'total' => $packages->count(),
                // ========== START: DSS - Include budget info in response metadata ==========
                'has_budget' => !is_null($clientBudget),
                'budget_info' => $clientBudget ? [
                    'minimum_budget' => $clientBudget->minimum_budget,
                    'maximum_budget' => $clientBudget->maximum_budget,
                    'preferred_budget' => $clientBudget->preferred_budget,
                    'budget_range' => $this->formatBudgetRange($clientBudget),
                    'budget_name' => $clientBudget->budget_name
                ] : null
                // ========== END: DSS - Include budget info ==========
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to load packages: ' . $e->getMessage(),
                'packages' => [],
            ], 500);
        }
    }
}
