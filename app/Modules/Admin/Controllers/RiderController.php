<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Rider;
use App\Modules\Admin\Models\RouteShare;
use App\Modules\Admin\Models\ActivityLog;
use Illuminate\Http\Request;

class RiderController extends Controller
{
    public function index()
    {
        $riders = Rider::withCount('orders')->latest()->paginate(15);
        return view('Admin::riders.index', compact('riders'));
    }

    public function create()
    {
        return view('Admin::riders.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:riders,email',
            'phone' => 'required|string|max:20',
            'age' => 'nullable|integer|min:18|max:100',
            'vehicle_type' => 'nullable|string|in:bike,car,van',
            'vehicle_number' => 'nullable|string|max:50',
            'license_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'status' => 'required|in:active,inactive,suspended',
            // Rider documents
            'rider_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'rider_id_document' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'driver_license_doc' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'vehicle_registration' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'vehicle_insurance' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            // Guarantor 1
            'guarantor1_full_name' => 'nullable|string|max:255',
            'guarantor1_dob' => 'nullable|date',
            'guarantor1_nationality' => 'nullable|string|max:100',
            'guarantor1_occupation' => 'nullable|string|max:255',
            'guarantor1_nin' => 'nullable|string|max:50',
            'guarantor1_residential_address' => 'nullable|string',
            'guarantor1_phone' => 'nullable|string|max:20',
            'guarantor1_alt_phone1' => 'nullable|string|max:20',
            'guarantor1_alt_phone2' => 'nullable|string|max:20',
            'guarantor1_work_address' => 'nullable|string',
            'guarantor1_relationship' => 'nullable|string|max:100',
            'guarantor1_years_known' => 'nullable|integer',
            'guarantor1_id_document' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'guarantor1_proof_of_address' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'guarantor1_employment_letter' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'guarantor1_additional_doc' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            // Guarantor 2
            'guarantor2_full_name' => 'nullable|string|max:255',
            'guarantor2_dob' => 'nullable|date',
            'guarantor2_nationality' => 'nullable|string|max:100',
            'guarantor2_occupation' => 'nullable|string|max:255',
            'guarantor2_nin' => 'nullable|string|max:50',
            'guarantor2_residential_address' => 'nullable|string',
            'guarantor2_phone' => 'nullable|string|max:20',
            'guarantor2_alt_phone1' => 'nullable|string|max:20',
            'guarantor2_alt_phone2' => 'nullable|string|max:20',
            'guarantor2_work_address' => 'nullable|string',
            'guarantor2_relationship' => 'nullable|string|max:100',
            'guarantor2_years_known' => 'nullable|integer',
            'guarantor2_id_document' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'guarantor2_proof_of_address' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'guarantor2_employment_letter' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            'guarantor2_additional_doc' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120',
            // Witness
            'witness_full_name' => 'nullable|string|max:255',
            'witness_phone' => 'nullable|string|max:20',
            'witness_address' => 'nullable|string',
            'witness_signature' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'witness_date' => 'nullable|date',
            // Additional Files
            'additional_file_1' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
            'additional_file_2' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
            'additional_file_3' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
        ]);

        // Handle file uploads
        $fileFields = [
            'rider_photo', 'rider_id_document', 'driver_license_doc', 'vehicle_registration', 'vehicle_insurance',
            'guarantor1_id_document', 'guarantor1_proof_of_address', 'guarantor1_employment_letter', 'guarantor1_additional_doc',
            'guarantor2_id_document', 'guarantor2_proof_of_address', 'guarantor2_employment_letter', 'guarantor2_additional_doc',
            'witness_signature', 'additional_file_1', 'additional_file_2', 'additional_file_3'
        ];

        foreach ($fileFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . $field . '.' . $file->getClientOriginalExtension();
                $path = $file->storeAs('riders/documents', $filename, 'public');
                $validated[$field] = $path;
            }
        }

        $rider = Rider::create($validated);

        ActivityLog::log('rider_created', "Created rider: {$rider->name}", $rider);

        return redirect()->route('admin.riders')->with('success', 'Rider created successfully');
    }

    public function show($id)
    {
        $rider = Rider::with(['orders' => function($query) {
            $query->latest()->limit(10);
        }])->findOrFail($id);
        
        // Get orders for route planning: assigned to rider but not yet delivered
        // Includes: 1) Orders not yet picked up (no pickup_date), 2) Orders in transit (no delivery_date)
        $pendingOrders = $rider->orders()
            ->where(function($query) {
                $query->whereNull('pickup_date') // Not yet picked up
                      ->orWhere(function($q) {
                          $q->whereNotNull('pickup_date') // Already picked up
                            ->whereNull('delivery_date'); // But not yet delivered
                      });
            })
            ->whereIn('status', ['pending', 'confirmed', 'in_transit'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'asc')
            ->get();
        
        // Get route share link if exists
        $routeShare = RouteShare::where('rider_id', $rider->id)
            ->where('is_active', true)
            ->first();
        
        $waypoints = [];
        $startingPoint = 'Rider Location';
        $googleMapsUrl = null;
        $whatsappText = '';
        
        return view('Admin::riders.show', compact('rider', 'pendingOrders', 'routeShare', 'waypoints', 'startingPoint', 'googleMapsUrl', 'whatsappText'));
    }

    public function edit($id)
    {
        $rider = Rider::findOrFail($id);
        return view('Admin::riders.edit', compact('rider'));
    }

    public function update(Request $request, $id)
    {
        $rider = Rider::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:riders,email,' . $id,
            'phone' => 'required|string|max:20',
            'vehicle_type' => 'nullable|string|in:bike,car,van',
            'vehicle_number' => 'nullable|string|max:50',
            'license_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'status' => 'required|in:active,inactive,suspended',
        ]);

        $rider->update($validated);

        ActivityLog::log('rider_updated', "Updated rider: {$rider->name}", $rider);

        return redirect()->route('admin.riders.show', $id)->with('success', 'Rider updated successfully');
    }

    public function destroy($id)
    {
        $rider = Rider::findOrFail($id);
        $riderName = $rider->name;
        
        // Check if rider has active orders
        if ($rider->activeOrders()->count() > 0) {
            return back()->withErrors(['error' => 'Cannot delete rider with active orders']);
        }

        $rider->delete();

        ActivityLog::log('rider_deleted', "Deleted rider: {$riderName}");

        return redirect()->route('admin.riders')->with('success', 'Rider deleted successfully');
    }

    public function generateShareLink($id)
    {
        $rider = Rider::findOrFail($id);
        
        RouteShare::where('rider_id', $rider->id)->delete();
        
        $routeShare = RouteShare::create([
            'rider_id' => $rider->id,
            'token' => RouteShare::generateToken(),
            'expires_at' => now()->addYears(10),
            'is_active' => true,
        ]);
        
        ActivityLog::log('route_share_created', "Enabled route sharing for rider: {$rider->name}", $routeShare);
        
        return response()->json([
            'success' => true,
            'url' => $routeShare->getShareUrl(),
        ]);
    }

    public function disableShareLink($id)
    {
        $rider = Rider::findOrFail($id);
        
        RouteShare::where('rider_id', $rider->id)
            ->update(['is_active' => false]);
        
        ActivityLog::log('route_share_disabled', "Disabled route sharing for rider: {$rider->name}");
        
        return response()->json([
            'success' => true,
            'message' => 'Route sharing disabled successfully',
        ]);
    }

    public function regenerateCode($id)
    {
        $rider = Rider::findOrFail($id);
        
        $newCode = $rider->generateDailyCode();
        
        ActivityLog::log('access_code_regenerated', "Regenerated access code for rider: {$rider->name}");
        
        return response()->json([
            'success' => true,
            'code' => $newCode,
            'message' => 'Access code regenerated successfully',
        ]);
    }

    public function getLocation($id)
    {
        $rider = Rider::findOrFail($id);
        
        return response()->json([
            'success' => true,
            'current_latitude' => $rider->current_latitude,
            'current_longitude' => $rider->current_longitude,
            'last_location_update' => $rider->last_location_update,
        ]);
    }
}
