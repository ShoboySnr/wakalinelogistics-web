<?php

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Models\ActivityLog;
use App\Modules\Admin\Models\Bike;
use App\Modules\Admin\Models\Rider;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class BikeService
{
    public const DOCUMENTS = [
        'registration' => 'Registration',
        'insurance' => 'Insurance',
        'roadworthiness' => 'Roadworthiness',
        'hackney_permit' => 'Hackney Permit',
        'vehicle_license' => 'Vehicle License',
    ];

    public function listQuery(Request $request): Builder
    {
        $query = Bike::query();

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('bike_number', 'like', "%{$search}%")
                    ->orWhere('plate_number', 'like', "%{$search}%")
                    ->orWhere('brand', 'like', "%{$search}%")
                    ->orWhere('model', 'like', "%{$search}%");
            });
        }

        if ($request->get('expiring') == '1') {
            $query->withExpiringDocuments(30);
        }

        if ($request->get('expired') == '1') {
            $query->withExpiredDocuments();
        }

        return $query;
    }

    public function stats(): array
    {
        return [
            'total' => Bike::count(),
            'active' => Bike::where('status', 'active')->count(),
            'maintenance' => Bike::where('status', 'maintenance')->count(),
            'inactive' => Bike::where('status', 'inactive')->count(),
            'assigned' => Bike::whereNotNull('assigned_rider_id')->count(),
            'expiring_docs' => Bike::withExpiringDocuments(30)->count(),
            'expired_docs' => Bike::withExpiredDocuments()->count(),
        ];
    }

    public function rules(?Bike $bike = null): array
    {
        $rules = [
            'bike_number' => ['required', 'string', Rule::unique('bikes', 'bike_number')->ignore($bike?->id)],
            'brand' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'plate_number' => ['required', 'string', Rule::unique('bikes', 'plate_number')->ignore($bike?->id)],
            'color' => 'nullable|string|max:255',
            'year' => 'nullable|integer|min:1900|max:' . (date('Y') + 1),
            'engine_number' => 'nullable|string|max:255',
            'chassis_number' => 'nullable|string|max:255',
            'status' => 'required|in:active,maintenance,inactive',
            'assigned_rider_id' => 'nullable|exists:riders,id',
            'last_maintenance_date' => 'nullable|date',
            'next_maintenance_date' => 'nullable|date',
            'notes' => 'nullable|string',
            'stickers' => 'nullable|array',
            'stickers.*.name' => 'nullable|string|max:255',
            'stickers.*.serial_number' => 'nullable|string|max:255',
            'stickers.*.expiry_date' => 'nullable|date',
            'stickers.*.document' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'sticker_names.*' => 'nullable|string|max:255',
            'sticker_serial_numbers.*' => 'nullable|string|max:255',
            'sticker_expiry_dates.*' => 'nullable|date',
            'sticker_documents.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ];

        foreach (array_keys(self::DOCUMENTS) as $doc) {
            $rules["{$doc}_document"] = 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120';
            $rules["{$doc}_expiry_date"] = 'nullable|date';
        }

        return $rules;
    }

    public function create(array $validated, Request $request): Bike
    {
        $data = $this->prepare(new Bike(), $validated, $request);
        $data['created_by'] = Auth::id();
        $data['assignment_date'] = !empty($data['assigned_rider_id']) ? now() : null;

        $bike = Bike::create($data);

        ActivityLog::log('bike_created', "Created bike: {$bike->bike_number} - {$bike->plate_number}", $bike);

        return $bike;
    }

    public function update(Bike $bike, array $validated, Request $request): Bike
    {
        $data = $this->prepare($bike, $validated, $request);

        if (array_key_exists('assigned_rider_id', $data) && $data['assigned_rider_id'] != $bike->assigned_rider_id) {
            $data['assignment_date'] = $data['assigned_rider_id'] ? now() : null;
        }

        $bike->update($data);

        ActivityLog::log('bike_updated', "Updated bike: {$bike->bike_number} - {$bike->plate_number}", $bike);

        return $bike;
    }

    public function delete(Bike $bike): void
    {
        $number = $bike->bike_number;
        $bike->delete();

        ActivityLog::log('bike_deleted', "Deleted bike: {$number}");
    }

    public function assignRider(Bike $bike, ?int $riderId): void
    {
        $previous = $bike->assignedRider?->name ?? 'Unknown';

        $bike->forceFill([
            'assigned_rider_id' => $riderId,
            'assignment_date' => $riderId ? now() : null,
        ])->save();

        if ($riderId) {
            ActivityLog::log('bike_assigned', "Assigned bike {$bike->bike_number} to rider: " . Rider::find($riderId)?->name, $bike);
        } else {
            ActivityLog::log('bike_unassigned', "Unassigned bike {$bike->bike_number} from rider: {$previous}", $bike);
        }
    }

    private function prepare(Bike $bike, array $validated, Request $request): array
    {
        foreach (['stickers', 'sticker_names', 'sticker_serial_numbers', 'sticker_expiry_dates', 'sticker_documents'] as $key) {
            unset($validated[$key]);
        }

        foreach (array_keys(self::DOCUMENTS) as $doc) {
            $field = "{$doc}_document";
            unset($validated[$field]);

            if ($request->input("remove_{$field}") == '1') {
                $this->deleteFile($bike->{$field});
                $validated[$field] = null;
            } elseif ($file = $request->file($field)) {
                $this->deleteFile($bike->{$field});
                $validated[$field] = $this->storeFile($file, 'uploads/bikes/documents');
            }
        }

        if ($request->has('stickers') || $request->has('sticker_names')) {
            $validated['stickers_permits'] = $this->stickers($bike, $request);
        }

        return $validated;
    }

    private function stickers(Bike $bike, Request $request): array
    {
        $rows = $request->has('stickers')
            ? collect($request->input('stickers', []))->map(fn ($row, $i) => $row + ['_file' => $request->file("stickers.{$i}.document")])
            : collect($request->input('sticker_names', []))->map(fn ($name, $i) => [
                'name' => $name,
                'serial_number' => $request->input("sticker_serial_numbers.{$i}"),
                'expiry_date' => $request->input("sticker_expiry_dates.{$i}"),
                '_file' => $request->file("sticker_documents.{$i}"),
            ]);

        $existing = $bike->stickers_permits ?? [];

        return $rows->filter(fn ($row) => !empty($row['name']))
            ->map(function ($row, $index) use ($existing) {
                $current = $existing[$index]['document'] ?? $existing[$index]['document_path'] ?? null;

                if (($row['remove_document'] ?? null) == '1') {
                    $this->deleteFile($current);
                    $current = null;
                } elseif ($row['_file'] instanceof UploadedFile) {
                    if (!$row['_file']->isValid()) {
                        throw new DomainException("Sticker document upload failed for {$row['name']}.");
                    }
                    $this->deleteFile($current);
                    $current = $this->storeFile($row['_file'], 'uploads/bikes/stickers');
                }

                return [
                    'name' => $row['name'],
                    'serial_number' => $row['serial_number'] ?? '',
                    'expiry_date' => $row['expiry_date'] ?? null,
                    'document' => $current,
                ];
            })
            ->values()
            ->all();
    }

    private function storeFile(UploadedFile $file, string $directory): string
    {
        $filename = time() . '_' . uniqid() . '_' . preg_replace('/[^A-Za-z0-9._-]/', '_', $file->getClientOriginalName());
        $file->move(public_path($directory), $filename);

        return "{$directory}/{$filename}";
    }

    private function deleteFile(?string $path): void
    {
        if ($path && file_exists(public_path($path))) {
            unlink(public_path($path));
        }
    }
}
