<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Inertia\Inertia;


class GoalController extends Controller
{
    public function index() {
        return Inertia::render('Player/Player');
    }
}
