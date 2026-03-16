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
                'question' => 'What curriculum does Kweneng International Secondary School offer?',
                'answer' => 'Kweneng International Secondary School offers the Cambridge IGCSE curriculum. We focus on delivering strong academic preparation at secondary school level through the Cambridge programme.'
            ],
            [
                'question' => 'Do you offer AS or A Level programmes?',
                'answer' => 'No. At the moment, Kweneng International Secondary School offers Cambridge IGCSE only.'
            ],
            [
                'question' => 'What are the admission requirements?',
                'answer' => 'Admission is based on the learner’s previous academic record and other requirements set by the school. Parents or guardians are encouraged to contact the school directly for the latest admission guidance and application process.'
            ],
            [
                'question' => 'How do I apply for admission?',
                'answer' => 'You can apply by contacting the school, obtaining the application form, and submitting the required documents. For assistance, please use the Contact page or speak to the school through the official phone numbers or WhatsApp.'
            ],
            [
                'question' => 'Do you provide school transport?',
                'answer' => 'No, the school does not provide its own transport service. However, there is reliable public transportation serving the school area.'
            ],
            [
                'question' => 'Is the school accessible from other areas?',
                'answer' => 'Yes. The school is accessible from Molepolole and surrounding areas, and many families make use of reliable public transport options.'
            ],
            [
                'question' => 'What are your school office hours?',
                'answer' => 'The school office operates from Monday to Friday, 7:30 AM to 4:00 PM.'
            ],
            [
                'question' => 'How can I contact the school?',
                'answer' => 'You can contact the school by phone, WhatsApp, email, or by using the contact form on the website. Please visit the Contact page for the latest official contact details.'
            ],
            [
                'question' => 'Does the school follow a disciplined academic environment?',
                'answer' => 'Yes. Kweneng International Secondary School promotes a disciplined academic culture focused on learning, character, responsibility, and strong examination preparation.'
            ],
            [
                'question' => 'Can I visit the school before applying?',
                'answer' => 'Yes. Parents and guardians may contact the school to arrange an enquiry or visit before completing the admission process.'
            ],
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
