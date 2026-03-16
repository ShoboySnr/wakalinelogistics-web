<?php

namespace App\Modules\LandingPage\Controllers;

use App\Http\Controllers\Controller;

class LandingPageController extends Controller
{
    public function index()
    {
        return view('landing-page::index');
    }

    public function landing()
    {
        return view('landing-page::landing');
    }
}
