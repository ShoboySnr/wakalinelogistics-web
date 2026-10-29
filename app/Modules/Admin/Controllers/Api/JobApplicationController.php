<?php

namespace App\Modules\Admin\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\ActivityLog;
use App\Modules\Admin\Models\JobApplication;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class JobApplicationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = JobApplication::query()->with('reviewer');
        $baseQuery = JobApplication::query();

        if ($request->filled('job_type')) {
            $query->where('job_type', $request->job_type);
            $baseQuery->where('job_type', $request->job_type);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $apply = function ($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            };
            $query->where($apply);
            $baseQuery->where($apply);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $applications = $query->latest()->paginate(min(100, max(5, (int) $request->get('per_page', 20))));

        return response()->json([
            'success' => true,
            'data' => collect($applications->items())->map(fn (JobApplication $a) => $this->summary($a)),
            'meta' => [
                'current_page' => $applications->currentPage(),
                'last_page' => $applications->lastPage(),
                'per_page' => $applications->perPage(),
                'total' => $applications->total(),
            ],
            'stats' => [
                'pending' => (clone $baseQuery)->where('status', 'pending')->count(),
                'reviewing' => (clone $baseQuery)->where('status', 'reviewing')->count(),
                'shortlisted' => (clone $baseQuery)->where('status', 'shortlisted')->count(),
                'rejected' => (clone $baseQuery)->where('status', 'rejected')->count(),
                'hired' => (clone $baseQuery)->where('status', 'hired')->count(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->detail(JobApplication::with('reviewer')->findOrFail($id))]);
    }

    public function updateStatus(Request $request, int $id): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:pending,reviewing,shortlisted,rejected,hired',
            'admin_notes' => 'nullable|string|max:2000',
        ]);

        $application = JobApplication::findOrFail($id);
        $application->update([
            'status' => $validated['status'],
            'admin_notes' => $validated['admin_notes'] ?? $application->admin_notes,
            'reviewed_at' => now(),
            'reviewed_by' => auth()->id(),
        ]);

        ActivityLog::log('job_application_updated', "Updated job application status for {$application->full_name} to {$validated['status']}", $application);

        return response()->json(['success' => true, 'message' => 'Application status updated', 'data' => $this->detail($application->fresh('reviewer'))]);
    }

    public function destroy(int $id): JsonResponse
    {
        $application = JobApplication::findOrFail($id);
        $name = $application->full_name;
        $application->delete();

        ActivityLog::log('job_application_deleted', "Deleted job application from {$name}");

        return response()->json(['success' => true, 'message' => 'Application deleted successfully']);
    }

    private function summary(JobApplication $a): array
    {
        return [
            'id' => $a->id,
            'job_type' => $a->job_type,
            'formatted_job_type' => $a->formatted_job_type,
            'full_name' => $a->full_name,
            'phone' => $a->phone,
            'email' => $a->email,
            'address' => $a->address,
            'age' => $a->age,
            'experience_years' => $a->experience_years,
            'status' => $a->status,
            'is_partner' => $a->isPartnerApplication(),
            'created_at' => $a->created_at,
        ];
    }

    private function detail(JobApplication $a): array
    {
        return $this->summary($a) + [
            'license_number' => $a->license_number,
            'previous_work' => $a->previous_work,
            'availability' => $a->availability,
            'why_join' => $a->why_join,
            'owns_vehicle' => $a->owns_vehicle,
            'vehicle_type' => $a->vehicle_type,
            'vehicle_registration' => $a->vehicle_registration,
            'coverage_areas' => $a->coverage_areas,
            'has_smartphone' => $a->has_smartphone,
            'guarantors' => $a->guarantors(),
            'admin_notes' => $a->admin_notes,
            'reviewed_at' => $a->reviewed_at,
            'reviewed_by' => $a->reviewer?->name,
            'updated_at' => $a->updated_at,
        ];
    }
}
