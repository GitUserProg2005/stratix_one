<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Inertia\Inertia;


class GamesController extends Controller
{
    public function index() {
        return Inertia::render('Games/Puzzle');
    }
}
