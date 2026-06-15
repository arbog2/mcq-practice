<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    public function __invoke()
    {
        auth()->user()->load('organizationUnit');

        return view('student.dashboard');
    }
}
