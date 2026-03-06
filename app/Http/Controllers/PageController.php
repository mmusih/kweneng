<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class PageController extends Controller
{
    public function about()
    {
        return view('pages.about');
    }

    public function academics()
    {
        return view('pages.academics');
    }

    public function admissions()
    {
        return view('pages.admissions');
    }

    public function faq()
    {
        $faqs = [
            [
                'question' => 'What are the admission requirements?',
                'answer' => 'Students must have completed primary education with satisfactory grades. We require previous academic records and may conduct entrance assessments.'
            ],
            [
                'question' => 'What curriculum do you offer?',
                'answer' => 'We follow the Cambridge International Curriculum including IGCSE and AS/A Level programs.'
            ],
            [
                'question' => 'Is transport available?',
                'answer' => 'Yes, we provide transportation services to major areas including Molepolole, Gaborone, Mogoditshane, Metsimotlhabe, and Thamaga.'
            ],
            [
                'question' => 'What are your school hours?',
                'answer' => 'School runs from 7:30 AM to 4:00 PM, Monday through Friday.'
            ],
            [
                'question' => 'How do I apply?',
                'answer' => 'Download the application form from our website, complete it, and submit along with required documents to our admissions office.'
            ]
        ];

        return view('pages.faq', compact('faqs'));
    }

    public function contact()
    {
        return view('pages.contact');
    }

    public function contactSubmit(Request $request)
    {
        // Validate the form data
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|max:2000',
        ]);

        try {
            // Prepare email content
            $emailContent = "
New Contact Form Submission from Kweneng International Website:

Name: {$validated['name']}
Email: {$validated['email']}
Subject: {$validated['subject']}

Message:
{$validated['message']}

---

Sent from contact form at: " . now()->format('Y-m-d H:i:s') . "
IP Address: " . $request->ip() . "
";

            // Send email to school
            Mail::raw($emailContent, function ($message) use ($validated) {
                $message->to('info@kwenenginternational.com')
                        ->subject("Website Contact Form: {$validated['subject']}")
                        ->from('noreply@kwenenginternational.com', 'Kweneng International Website');
            });

            return redirect()->back()->with('success', 'Thank you for your message! We will get back to you soon.');
        } catch (\Exception $e) {
            Log::error('Contact form error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Sorry, there was an error sending your message. Please try again or contact us directly.')->withInput();
        }
    }

    public function alumni()
    {
        return view('pages.alumni');
    }

    public function studentLife()
    {
        return view('pages.student-life');
    }

    public function newsEvents()
    {
        return view('pages.news-events');
    }

    public function facilities()
    {
        return view('pages.facilities');
    }

    public function parentResources()
    {
        return view('pages.parent-resources');
    }

    public function termDates()
    {
        return view('pages.term-dates');
    }

    public function policies()
    {
        return view('pages.policies');
    }

    public function successStories()
    {
        return view('pages.success-stories');
    }
}
