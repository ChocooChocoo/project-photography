<?php

namespace App\Http\Controllers\StudioOwner;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PermissionController extends Controller
{
    public function index()
    {
        return view('owner.view-permissions');
    }

    public function create()
    {
        return view('owner.create-permissions');
    }
}
