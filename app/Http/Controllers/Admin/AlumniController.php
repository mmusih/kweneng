<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Alumni;
use App\Models\AlumniInterest;
use App\Mail\AlumniWelcome;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class AlumniController extends Controller
{
    public function index()
    {
        try {
            $alumni = Alumni::orderBy('graduation_year', 'desc')->get();
            return view('admin.alumni.index', compact('alumni'));
        } catch (\Exception $e) {
            Log::error('Error fetching alumni: ' . $e->getMessage());
            return view('admin.alumni.index', ['alumni' => collect()])->with('error', 'Failed to load alumni.');
        }
    }

    public function create()
    {
        return view('admin.alumni.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:alumni,email',
            'phone' => 'nullable|string|max:20',
            'graduation_year' => 'required|integer|min:1950|max:' . (date('Y') + 5),
            'current_occupation' => 'nullable|string|max:255',
            'company' => 'nullable|string|max:255',
            'location' => 'nullable|string|max:255',
            'bio' => 'nullable|string|max:1000',
            'linkedin_url' => 'nullable|url|max:500',
            'is_published' => 'boolean',
        ]);

        try {
            $alumni = Alumni::create($validated);

            // Send welcome email
            $emailSent = $this->sendWelcomeEmail($alumni);

            $message = 'Alumni created successfully.';
            if ($emailSent) {
                $message .= ' Welcome email sent.';
            } else {
                $message .= ' Welcome email could not be sent.';
            }

            return redirect()->route('admin.alumni.index')->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Error creating alumni: ' . $e->getMessage());
            return redirect()->back()
                            ->with('error', 'Failed to create alumni.')
                            ->withInput();
        }
    }

    public function show($alumnus)
    {
        try {
            $alumni = Alumni::findOrFail($alumnus);
            return view('admin.alumni.show', compact('alumni'));
        } catch (\Exception $e) {
            Log::error('Error showing alumni: ' . $e->getMessage());
            return redirect()->route('admin.alumni.index')
                            ->with('error', 'Alumni not found.');
        }
    }

    public function edit($alumnus)
    {
        try {
            $alumni = Alumni::findOrFail($alumnus);
            return view('admin.alumni.edit', compact('alumni'));
        } catch (\Exception $e) {
            Log::error('Error editing alumni: ' . $e->getMessage());
            return redirect()->route('admin.alumni.index')
                            ->with('error', 'Alumni not found.');
        }
    }

    public function update(Request $request, $alumnus)
    {
        try {
            $alumni = Alumni::findOrFail($alumnus);
            
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'email' => ['required', 'email', Rule::unique('alumni', 'email')->ignore($alumni->id)],
                'phone' => 'nullable|string|max:20',
                'graduation_year' => 'required|integer|min:1950|max:' . (date('Y') + 5),
                'current_occupation' => 'nullable|string|max:255',
                'company' => 'nullable|string|max:255',
                'location' => 'nullable|string|max:255',
                'bio' => 'nullable|string|max:1000',
                'linkedin_url' => 'nullable|url|max:500',
                'is_published' => 'boolean',
            ]);

            $alumni->update($validated);

            return redirect()->route('admin.alumni.index')
                            ->with('success', 'Alumni updated successfully.');
        } catch (\Exception $e) {
            Log::error('Error updating alumni: ' . $e->getMessage());
            return redirect()->back()
                            ->with('error', 'Failed to update alumni.')
                            ->withInput();
        }
    }

    public function destroy($alumnus)
    {
        try {
            $alumni = Alumni::findOrFail($alumnus);
            $alumni->delete();

            return redirect()->route('admin.alumni.index')
                            ->with('success', 'Alumni deleted successfully.');
        } catch (\Exception $e) {
            Log::error('Error deleting alumni: ' . $e->getMessage());
            return redirect()->route('admin.alumni.index')
                            ->with('error', 'Failed to delete alumni.');
        }
    }

    public function togglePublish($alumnus)
    {
        try {
            $alumni = Alumni::findOrFail($alumnus);
            $alumni->update(['is_published' => !$alumni->is_published]);

            $status = $alumni->is_published ? 'published' : 'unpublished';
            return redirect()->route('admin.alumni.index')
                            ->with('success', "Alumni {$status} successfully.");
        } catch (\Exception $e) {
            Log::error('Error toggling alumni publish status: ' . $e->getMessage());
            return redirect()->route('admin.alumni.index')
                            ->with('error', 'Failed to update alumni status.');
        }
    }

    // ALUMNI INTEREST METHODS
    
    public function interests()
    {
        try {
            $interests = AlumniInterest::where('processed', false)->orderBy('created_at', 'desc')->get();
            return view('admin.alumni.interests', compact('interests'));
        } catch (\Exception $e) {
            Log::error('Error fetching alumni interests: ' . $e->getMessage());
            return view('admin.alumni.interests', ['interests' => collect()])->with('error', 'Failed to load interests.');
        }
    }

    public function processInterest($id)
    {
        try {
            $interest = AlumniInterest::findOrFail($id);
            $interest->update(['processed' => true]);
            
            return redirect()->route('admin.alumni.interests')
                            ->with('success', 'Interest marked as processed successfully.');
        } catch (\Exception $e) {
            Log::error('Error processing alumni interest: ' . $e->getMessage());
            return redirect()->route('admin.alumni.interests')
                            ->with('error', 'Failed to process interest.');
        }
    }

    public function convertInterestToAlumni($id)
    {
        try {
            $interest = AlumniInterest::findOrFail($id);
            
            // Check if alumni with this email already exists
            $existingAlumni = Alumni::where('email', $interest->email)->first();
            if ($existingAlumni) {
                // Mark interest as processed and redirect
                $interest->update(['processed' => true]);
                
                return redirect()->route('admin.alumni.index')
                                ->with('warning', "Alumni profile for {$interest->full_name} already exists. Interest marked as processed.");
            }
            
            // Create alumni profile from interest data
            $alumni = Alumni::create([
                'name' => $interest->full_name,
                'email' => $interest->email,
                'graduation_year' => is_numeric($interest->graduation_year) ? (int)$interest->graduation_year : date('Y'),
                'phone' => $interest->phone,
                'is_published' => false, // Start as unpublished until reviewed
            ]);
            
            // Mark interest as processed
            $interest->update(['processed' => true]);
            
            // Send welcome email
            $emailSent = $this->sendWelcomeEmail($alumni);
            
            $message = "Alumni profile for {$alumni->name} created successfully!";
            if ($emailSent) {
                $message .= ' Welcome email sent.';
            } else {
                $message .= ' Welcome email could not be sent.';
            }
            
            return redirect()->route('admin.alumni.index')->with('success', $message);
        } catch (\Exception $e) {
            Log::error('Error converting alumni interest: ' . $e->getMessage());
            return redirect()->route('admin.alumni.interests')
                            ->with('error', 'Failed to create alumni profile: ' . $e->getMessage());
        }
    }

    // Helper method for sending welcome emails with feedback
    private function sendWelcomeEmail($alumni)
    {
        try {
            Mail::to($alumni->email)->send(new AlumniWelcome($alumni));
            return true;
        } catch (\Exception $e) {
            Log::error('Alumni welcome email failed: ' . $e->getMessage(), [
                'alumni_id' => $alumni->id,
                'email' => $alumni->email
            ]);
            return false;
        }
    }
}
