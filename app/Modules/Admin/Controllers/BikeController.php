<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Bike;
use App\Modules\Admin\Models\Rider;
use App\Modules\Admin\Models\ActivityLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BikeController extends Controller
{
    public function index(Request $request)
    {
        $query = Bike::with(['assignedRider', 'creator']);

        if ($request->has('status') && $request->status != '') {
            $query->where('status', $request->status);
        }

        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('bike_number', 'like', "%{$search}%")
                  ->orWhere('plate_number', 'like', "%{$search}%")
                  ->orWhere('brand', 'like', "%{$search}%")
                  ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if ($request->has('expiring') && $request->expiring == '1') {
            $query->withExpiringDocuments(30);
        }

        if ($request->has('expired') && $request->expired == '1') {
            $query->withExpiredDocuments();
        }

        $bikes = $query->latest()->paginate(20);

        $stats = [
            'total' => Bike::count(),
            'active' => Bike::where('status', 'active')->count(),
            'maintenance' => Bike::where('status', 'maintenance')->count(),
            'inactive' => Bike::where('status', 'inactive')->count(),
            'assigned' => Bike::whereNotNull('assigned_rider_id')->count(),
            'expiring_docs' => Bike::withExpiringDocuments(30)->count(),
            'expired_docs' => Bike::withExpiredDocuments()->count(),
        ];

        return view('Admin::bikes.index', compact('bikes', 'stats'));
    }

    public function create()
    {
        $bikeNumber = Bike::generateBikeNumber();
        $riders = Rider::all();
        return view('Admin::bikes.create', compact('bikeNumber', 'riders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'bike_number' => 'required|string|unique:bikes,bike_number',
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'plate_number' => 'required|string|unique:bikes,plate_number',
            'color' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'engine_number' => 'nullable|string|max:255',
            'chassis_number' => 'nullable|string|max:255',
            'status' => 'required|in:active,maintenance,inactive',
            'registration_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'registration_expiry_date' => 'nullable|date',
            'insurance_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'insurance_expiry_date' => 'nullable|date',
            'roadworthiness_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'roadworthiness_expiry_date' => 'nullable|date',
            'hackney_permit_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'hackney_permit_expiry_date' => 'nullable|date',
            'vehicle_license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'vehicle_license_expiry_date' => 'nullable|date',
            'assigned_rider_id' => 'nullable|exists:users,id',
            'last_maintenance_date' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'sticker_names.*' => 'nullable|string|max:255',
            'sticker_serial_numbers.*' => 'nullable|string|max:255',
            'sticker_expiry_dates.*' => 'nullable|date',
            'sticker_documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        $documentFields = [
            'registration_document',
            'insurance_document',
            'roadworthiness_document',
            'hackney_permit_document',
            'vehicle_license_document',
        ];

        foreach ($documentFields as $field) {
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $filename = time() . '_' . $field . '_' . $file->getClientOriginalName();
                $file->move(public_path('uploads/bikes/documents'), $filename);
                $path = 'uploads/bikes/documents/' . $filename;
                $validated[$field] = $path;
            }
        }

        $stickersPermits = [];
        if ($request->has('sticker_names')) {
            $stickerNames = $request->input('sticker_names', []);
            $stickerSerials = $request->input('sticker_serial_numbers', []);
            $stickerExpiries = $request->input('sticker_expiry_dates', []);
            $stickerDocs = $request->file('sticker_documents', []);

            foreach ($stickerNames as $index => $name) {
                if (!empty($name)) {
                    $documentPath = null;
                    if (isset($stickerDocs[$index]) && $stickerDocs[$index]) {
                        $file = $stickerDocs[$index];
                        $filename = time() . '_sticker_' . $index . '_' . $file->getClientOriginalName();
                        $file->move(public_path('uploads/bikes/stickers'), $filename);
                        $documentPath = 'uploads/bikes/stickers/' . $filename;
                    }

                    $stickersPermits[] = [
                        'name' => $name,
                        'serial_number' => $stickerSerials[$index] ?? '',
                        'expiry_date' => $stickerExpiries[$index] ?? null,
                        'document_path' => $documentPath,
                    ];
                }
            }
        }

        $validated['stickers_permits'] = $stickersPermits;
        $validated['created_by'] = Auth::id();
        if ($validated['assigned_rider_id']) {
            $validated['assignment_date'] = now();
        }

        $bike = Bike::create($validated);

        ActivityLog::log('bike_created', "Created bike: {$bike->bike_number} - {$bike->plate_number}", $bike);

        return redirect()->route('admin.bikes')->with('success', 'Bike registered successfully');
    }

    public function show($id)
    {
        $bike = Bike::with(['assignedRider', 'creator'])->findOrFail($id);
        return view('Admin::bikes.show', compact('bike'));
    }

    public function edit($id)
    {
        $bike = Bike::findOrFail($id);
        $riders = Rider::all();
        return view('Admin::bikes.edit', compact('bike', 'riders'));
    }

    public function update(Request $request, $id)
    {
        $bike = Bike::findOrFail($id);

        try {
            $validated = $request->validate([
            'bike_number' => 'required|string|unique:bikes,bike_number,' . $id,
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'plate_number' => 'required|string|unique:bikes,plate_number,' . $id,
            'color' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'engine_number' => 'nullable|string|max:255',
            'chassis_number' => 'nullable|string|max:255',
            'status' => 'required|in:active,maintenance,inactive',
            'registration_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'registration_expiry_date' => 'nullable|date',
            'insurance_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'insurance_expiry_date' => 'nullable|date',
            'roadworthiness_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'roadworthiness_expiry_date' => 'nullable|date',
            'hackney_permit_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'hackney_permit_expiry_date' => 'nullable|date',
            'vehicle_license_document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'vehicle_license_expiry_date' => 'nullable|date',
            'assigned_rider_id' => 'nullable|exists:riders,id',
            'last_maintenance_date' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'stickers.*.name' => 'nullable|string|max:255',
            'stickers.*.serial_number' => 'nullable|string|max:255',
            'stickers.*.expiry_date' => 'nullable|date',
            'stickers.*.document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            throw $e;
        }

        $documentFields = [
            'registration_document',
            'insurance_document',
            'roadworthiness_document',
            'hackney_permit_document',
            'vehicle_license_document',
        ];

        $updateData = $validated;

        foreach ($documentFields as $field) {
            $removeField = 'remove_' . $field;
            if ($request->has($removeField) && $request->input($removeField) == '1') {
                if ($bike->$field && file_exists(public_path($bike->$field))) {
                    unlink(public_path($bike->$field));
                }
                $updateData[$field] = null;
                continue;
            }
            
            $file = $request->file($field);
            
            if ($file) {
                if ($file->isValid()) {
                    try {
                        if ($bike->$field && file_exists(public_path($bike->$field))) {
                            unlink(public_path($bike->$field));
                        }
                        
                        $filename = time() . '_' . uniqid() . '_' . $file->getClientOriginalName();
                        $file->move(public_path('uploads/bikes/documents'), $filename);
                        $path = 'uploads/bikes/documents/' . $filename;
                        $updateData[$field] = $path;
                    } catch (\Exception $e) {
                        return redirect()->back()->withErrors([$field => "Failed to upload {$field}: " . $e->getMessage()])->withInput();
                    }
                } else {
                    $errorMessage = $this->getUploadErrorMessage($file->getError());
                    return redirect()->back()->withErrors([$field => $errorMessage])->withInput();
                }
            }
        }

        $stickersPermits = [];
        if ($request->has('stickers')) {
            $stickers = $request->input('stickers', []);
            
            foreach ($stickers as $index => $stickerData) {
                if (!empty($stickerData['name'])) {
                    $documentPath = null;
                    if (isset($bike->stickers_permits[$index]['document'])) {
                        $documentPath = $bike->stickers_permits[$index]['document'];
                    }
                    
                    if (isset($stickerData['remove_document']) && $stickerData['remove_document'] == '1') {
                        if ($documentPath && file_exists(public_path($documentPath))) {
                            unlink(public_path($documentPath));
                        }
                        $documentPath = null;
                    } else {
                        $file = $request->file("stickers.{$index}.document");
                        if ($file) {
                            if ($file->isValid()) {
                                try {
                                    if ($documentPath && file_exists(public_path($documentPath))) {
                                        unlink(public_path($documentPath));
                                    }
                                    
                                    $filename = time() . '_' . uniqid() . '_sticker_' . $index . '_' . $file->getClientOriginalName();
                                    $file->move(public_path('uploads/bikes/stickers'), $filename);
                                    $documentPath = 'uploads/bikes/stickers/' . $filename;
                                } catch (\Exception $e) {
                                    return redirect()->back()->withErrors(["stickers.{$index}.document" => "Failed to upload document: " . $e->getMessage()])->withInput();
                                }
                            } else {
                                $errorMessage = $this->getUploadErrorMessage($file->getError());
                                return redirect()->back()->withErrors(["stickers.{$index}.document" => $errorMessage])->withInput();
                            }
                        }
                    }

                    $stickersPermits[] = [
                        'name' => $stickerData['name'],
                        'serial_number' => $stickerData['serial_number'] ?? '',
                        'expiry_date' => $stickerData['expiry_date'] ?? null,
                        'document' => $documentPath,
                    ];
                }
            }
        }

        $updateData['stickers_permits'] = $stickersPermits;

        if (isset($updateData['assigned_rider_id']) && $updateData['assigned_rider_id'] != $bike->assigned_rider_id) {
            $updateData['assignment_date'] = $updateData['assigned_rider_id'] ? now() : null;
        }

        $bike->update($updateData);

        ActivityLog::log('bike_updated', "Updated bike: {$bike->bike_number} - {$bike->plate_number}", $bike);

        return redirect()->route('admin.bikes')->with('success', 'Bike updated successfully');
    }

    public function destroy($id)
    {
        $bike = Bike::findOrFail($id);
        $bikeNumber = $bike->bike_number;
        
        $bike->delete();

        ActivityLog::log('bike_deleted', "Deleted bike: {$bikeNumber}");

        return redirect()->route('admin.bikes')->with('success', 'Bike deleted successfully');
    }

    public function assignRider(Request $request, $id)
    {
        $validated = $request->validate([
            'rider_id' => 'required|exists:riders,id',
        ]);

        $bike = Bike::findOrFail($id);
        $bike->assigned_rider_id = $validated['rider_id'];
        $bike->assignment_date = now();
        $bike->save();

        $rider = Rider::find($validated['rider_id']);
        ActivityLog::log('bike_assigned', "Assigned bike {$bike->bike_number} to rider: {$rider->name}", $bike);

        return redirect()->back()->with('success', 'Bike assigned to rider successfully');
    }

    public function unassignRider($id)
    {
        $bike = Bike::findOrFail($id);
        $riderName = $bike->assignedRider ? $bike->assignedRider->name : 'Unknown';
        
        $bike->assigned_rider_id = null;
        $bike->assignment_date = null;
        $bike->save();

        ActivityLog::log('bike_unassigned', "Unassigned bike {$bike->bike_number} from rider: {$riderName}", $bike);

        return redirect()->back()->with('success', 'Bike unassigned from rider successfully');
    }

    private function getUploadErrorMessage($errorCode)
    {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
            UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form',
            UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
        ];
        
        return $errors[$errorCode] ?? 'Unknown upload error';
    }
}
