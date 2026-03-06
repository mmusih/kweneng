<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AlumniInterest;

class AlumniInterestController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'full_name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'graduation_year' => 'required|string|max:10',
            'phone' => 'nullable|string|max:20',
        ]);

        // Store in database for admin review
        AlumniInterest::create($validated);
        
        return redirect()->back()
                        ->with('success', 'Thank you for your interest! Our alumni team will contact you shortly to complete your registration.');
    }
}
