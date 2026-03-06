<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\PromotionService;
use App\Models\Student;
use App\Models\ClassModel;
use Illuminate\Http\Request;

class PromotionController extends Controller
{
    protected $promotionService;
    
    public function __construct(PromotionService $promotionService)
    {
        $this->promotionService = $promotionService;
    }
    
    public function index()
    {
        $classes = ClassModel::with('academicYear')->get();
        $students = Student::with('user', 'currentClass')->get();
        
        return view('admin.promotions.index', compact('classes', 'students'));
    }
    
    public function promoteStudent(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'new_class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'promotion_type' => 'string|in:promoted,repeated,transferred,graduated',
            'remarks' => 'nullable|string|max:500'
        ]);
        
        $result = $this->promotionService->promoteStudent(
            $validated['student_id'],
            $validated['new_class_id'],
            $validated['academic_year_id'],
            $validated['promotion_type'] ?? 'promoted',
            $validated['remarks'] ?? null
        );
        
        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }
        
        return redirect()->back()->withErrors(['promotion' => $result['message']]);
    }
    
    public function bulkPromote(Request $request)
    {
        $validated = $request->validate([
            'from_class_id' => 'required|exists:classes,id',
            'to_class_id' => 'required|exists:classes,id',
            'academic_year_id' => 'required|exists:academic_years,id',
            'promotion_type' => 'string|in:promoted,repeated,transferred,graduated'
        ]);
        
        $result = $this->promotionService->bulkPromoteClass(
            $validated['from_class_id'],
            $validated['to_class_id'],
            $validated['academic_year_id'],
            $validated['promotion_type'] ?? 'promoted'
        );
        
        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }
        
        return redirect()->back()->withErrors(['bulk_promotion' => $result['message']]);
    }
    
    public function reversePromotion(Request $request)
    {
        $validated = $request->validate([
            'student_id' => 'required|exists:students,id',
            'remarks' => 'nullable|string|max:500'
        ]);
        
        $result = $this->promotionService->reversePromotion(
            $validated['student_id'],
            $validated['remarks'] ?? null
        );
        
        if ($result['success']) {
            return redirect()->back()->with('success', $result['message']);
        }
        
        return redirect()->back()->withErrors(['reverse_promotion' => $result['message']]);
    }
}
