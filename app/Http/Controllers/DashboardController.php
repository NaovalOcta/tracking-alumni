<?php

namespace App\Http\Controllers;

use App\Models\Alumni;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total' => Alumni::count(),
            'auto_verified' => Alumni::status('auto_verified')->count(),
            'needs_audit' => Alumni::status('needs_audit')->count(),
            'belum_dilacak' => Alumni::status('belum_dilacak')->count(),
            'not_found' => Alumni::status('not_found')->count(),
            'insufficient_data' => Alumni::status('insufficient_data')->count(),
        ];

        $recentAlumni = Alumni::latest('updated_at')->take(10)->get();

        return view('dashboard.index', compact('stats', 'recentAlumni'));
    }
}
