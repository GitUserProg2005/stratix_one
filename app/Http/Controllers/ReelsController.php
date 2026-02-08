<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Inertia\Inertia;

class ReelsController extends Controller
{
    public function index()
    {
        return Inertia::render('Reels/Reels');
    }
}
