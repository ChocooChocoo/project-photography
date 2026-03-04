<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index()
    {
        return view('owner.view-employee');
    }

    public function create()
    {
        return view('owner.create-employee');
    }
}
