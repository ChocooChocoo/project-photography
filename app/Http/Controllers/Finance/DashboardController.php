<?php

namespace App\Http\Controllers\Finance;

use App\Http\Controllers\Controller;

/**
 * Handle finance dashboard requests.
 */
class DashboardController extends Controller
{
    /**
     * Display the finance dashboard page.
     *
     * @return \Illuminate\Contracts\View\View
     */
    public function index()
    {
        return view('studio-finance.dashboard');
    }
}
