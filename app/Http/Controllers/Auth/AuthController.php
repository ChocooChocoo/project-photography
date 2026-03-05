<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use App\Models\UserModel;
use App\Models\Admin\LocationModel;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;
use App\Mail\VerificationEmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    /**
     * Show the login form
     */
    public function index()
    {
        // If user is already logged in, redirect to their dashboard
        if (Auth::check()) {
            return $this->redirectToDashboard();
        }
        
        return view('auth.login');
    }

    /**
     * Show the registration form
     */
    public function register()
    {
        // If user is already logged in, redirect to their dashboard
        if (Auth::check()) {
            return $this->redirectToDashboard();
        }
        
        // Get all municipalities from Cavite - FIXED QUERY
        $municipalities = LocationModel::where('province', 'Cavite')
            ->where('status', 'active')
            ->select('municipality')
            ->distinct()
            ->orderBy('municipality')
            ->pluck('municipality');
        
        return view('auth.register', compact('municipalities'));
    }

    /**
     * Show the verification form
     */
    public function verify()
    {
        // If user is already logged in, redirect to their dashboard
        if (Auth::check()) {
            return $this->redirectToDashboard();
        }
        
        return view('auth.verify');
    }

    /**
     * Handle registration form submission
     */
    public function store(RegisterRequest $request)
    {
        try {
            // Map userType to role
            $roleMap = [
                'client' => 'client',
                'freelancer' => 'freelancer',
                'owner' => 'owner'
            ];

            $role = $roleMap[$request->userType] ?? 'client';
            
            // Determine user_type based on role
            $userType = ($role === 'client') ? 'customer' : 'photographer';
            
            // Get location ID based on selected municipality
            $location = LocationModel::where('province', 'Cavite')
                ->where('municipality', $request->municipality)
                ->where('status', 'active')
                ->first();
            
            // Generate verification token
            $verificationToken = Str::random(60);
            $tokenExpiry = now()->addHours(24);

            // Create user
            $user = UserModel::create([
                'uuid' => Str::uuid(),
                'role' => $role,
                'user_type' => $userType,
                'first_name' => $request->firstName,
                'middle_name' => $request->middleName,
                'last_name' => $request->lastName,
                'email' => $request->userEmail,
                'mobile_number' => $request->userMobile,
                'password' => Hash::make($request->userPassword),
                'location_id' => $location ? $location->id : null, // Add location_id
                'status' => 'active',
                'email_verified' => false,
                'verification_token' => $verificationToken,
                'token_expiry' => $tokenExpiry
            ]);

            // Send verification email
            Mail::to($user->email)->send(new VerificationEmail($user));

            return response()->json([
                'success' => true,
                'message' => 'Registration successful! Please check your email for verification.',
                'redirect' => route('verify')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Registration failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }

    /**
     * Handle login form submission
     */
    public function login(LoginRequest $request)
    {
        try {
            $credentials = $request->only('email', 'password');
            
            // Find user by email
            $user = UserModel::where('email', $request->email)->first();
            
            // Check if user exists
            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid email or password.'
                ], 401);
            }
            
            // Check if email is verified
            if (!$user->isEmailVerified()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Please verify your email address before logging in.',
                    'needs_verification' => true
                ], 401);
            }
            
            // Check if account is active
            if (!$user->isActive()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Your account is inactive. Please contact support.'
                ], 401);
            }
            
            // Attempt authentication
            if (Auth::attempt($credentials, $request->filled('remember'))) {
                $request->session()->regenerate();
                
                // Store user role in session for easy access
                session(['user_role' => $user->role]);
                
                return response()->json([
                    'success' => true,
                    'message' => 'Login successful!',
                    'redirect' => $this->getDashboardRoute($user->role)
                ]);
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Invalid email or password.'
            ], 401);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Login failed. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : null
            ], 500);
        }
    }
    
    /**
     * Handle logout
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        
        return redirect()->route('login');
    }

    /**
     * Verify email
     */
    public function verifyEmail(Request $request, $token)
    {
        try {
            $user = UserModel::where('verification_token', $token)
                ->where('token_expiry', '>', now())
                ->first();

            if (!$user) {
                return redirect()->route('login')->with('error', 'Invalid or expired verification link.');
            }

            $user->update([
                'email_verified' => true,
                'verification_token' => null,
                'token_expiry' => null
            ]);

            return redirect()->route('login')->with('success', 'Email verified successfully! You can now login.');

        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', 'Verification failed. Please try again.');
        }
    }
    
    /**
     * Get dashboard route based on user role
     */
    private function getDashboardRoute($role): string
    {
        $routes = [
            'admin' => 'admin.dashboard',
            'owner' => 'owner.dashboard',
            'freelancer' => 'freelancer.dashboard',
            'client' => 'client.dashboard',
            'studio-hr' => 'studio-hr.dashboard',
            'studio-photographer' => 'studio-photographer.dashboard'
        ];
        
        return route($routes[$role] ?? 'login');
    }
    
    /**
     * Redirect authenticated users to their dashboard
     */
    private function redirectToDashboard()
    {
        $user = Auth::user();
        
        if ($user) {
            return redirect($this->getDashboardRoute($user->role));
        }
        
        return redirect()->route('login');
    }
}