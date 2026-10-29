<?php

namespace App\Modules\Admin\Services;

use App\Modules\Admin\Models\ActivityLog;
use App\Modules\Admin\Models\Rider;
use App\Modules\Admin\Models\RouteShare;
use DomainException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class RiderService
{
    public const FILE_FIELDS = [
        'rider_photo', 'rider_id_document', 'driver_license_doc', 'vehicle_registration', 'vehicle_insurance',
        'guarantor1_id_document', 'guarantor1_proof_of_address', 'guarantor1_employment_letter', 'guarantor1_additional_doc',
        'guarantor2_id_document', 'guarantor2_proof_of_address', 'guarantor2_employment_letter', 'guarantor2_additional_doc',
        'witness_signature', 'additional_file_1', 'additional_file_2', 'additional_file_3',
    ];

    public function listQuery(Request $request): Builder
    {
        $query = Rider::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('vehicle_number', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        return $query;
    }

    public function rules(?Rider $rider = null): array
    {
        $document = 'nullable|file|mimes:pdf,jpeg,png,jpg|max:5120';
        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('riders', 'email')->ignore($rider?->id)],
            'phone' => 'required|string|max:20',
            'age' => 'nullable|integer|min:18|max:100',
            'vehicle_type' => 'nullable|string|in:bike,car,van',
            'vehicle_number' => 'nullable|string|max:50',
            'license_number' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'status' => 'required|in:active,inactive,suspended',
            'rider_photo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'rider_id_document' => $document,
            'driver_license_doc' => $document,
            'vehicle_registration' => $document,
            'vehicle_insurance' => $document,
            'witness_full_name' => 'nullable|string|max:255',
            'witness_phone' => 'nullable|string|max:20',
            'witness_address' => 'nullable|string',
            'witness_signature' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
            'witness_date' => 'nullable|date',
            'additional_file_1' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
            'additional_file_2' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
            'additional_file_3' => 'nullable|file|mimes:pdf,doc,docx,jpeg,png,jpg,gif|max:5120',
        ];

        foreach ([1, 2] as $n) {
            $rules += [
                "guarantor{$n}_full_name" => 'nullable|string|max:255',
                "guarantor{$n}_dob" => 'nullable|date',
                "guarantor{$n}_nationality" => 'nullable|string|max:100',
                "guarantor{$n}_occupation" => 'nullable|string|max:255',
                "guarantor{$n}_nin" => 'nullable|string|max:50',
                "guarantor{$n}_residential_address" => 'nullable|string',
                "guarantor{$n}_phone" => 'nullable|string|max:20',
                "guarantor{$n}_alt_phone1" => 'nullable|string|max:20',
                "guarantor{$n}_alt_phone2" => 'nullable|string|max:20',
                "guarantor{$n}_work_address" => 'nullable|string',
                "guarantor{$n}_relationship" => 'nullable|string|max:100',
                "guarantor{$n}_years_known" => 'nullable|integer',
                "guarantor{$n}_id_document" => $document,
                "guarantor{$n}_proof_of_address" => $document,
                "guarantor{$n}_employment_letter" => $document,
                "guarantor{$n}_additional_doc" => $document,
            ];
        }

        return $rules;
    }

    public function create(array $validated, Request $request): Rider
    {
        $rider = Rider::create($this->withUploads($validated, $request));

        ActivityLog::log('rider_created', "Created rider: {$rider->name}", $rider);

        return $rider;
    }

    public function update(Rider $rider, array $validated, Request $request): Rider
    {
        $rider->update($this->withUploads($validated, $request));

        ActivityLog::log('rider_updated', "Updated rider: {$rider->name}", $rider);

        return $rider;
    }

    public function delete(Rider $rider): void
    {
        if ($rider->activeOrders()->exists()) {
            throw new DomainException('Cannot delete rider with active orders');
        }

        $name = $rider->name;
        $rider->delete();

        ActivityLog::log('rider_deleted', "Deleted rider: {$name}");
    }

    public function pendingOrders(Rider $rider): Collection
    {
        return $rider->orders()
            ->where(function ($query) {
                $query->whereNull('pickup_date')
                    ->orWhere(fn ($q) => $q->whereNotNull('pickup_date')->whereNull('delivery_date'));
            })
            ->whereIn('status', ['pending', 'confirmed', 'in_transit'])
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'asc')
            ->get();
    }

    public function activeRouteShare(Rider $rider): ?RouteShare
    {
        return RouteShare::where('rider_id', $rider->id)->where('is_active', true)->first();
    }

    public function generateShareLink(Rider $rider): RouteShare
    {
        RouteShare::where('rider_id', $rider->id)->delete();

        $share = RouteShare::create([
            'rider_id' => $rider->id,
            'token' => RouteShare::generateToken(),
            'expires_at' => now()->addYears(10),
            'is_active' => true,
        ]);

        ActivityLog::log('route_share_created', "Enabled route sharing for rider: {$rider->name}", $share);

        return $share;
    }

    public function disableShareLink(Rider $rider): void
    {
        RouteShare::where('rider_id', $rider->id)->update(['is_active' => false]);

        ActivityLog::log('route_share_disabled', "Disabled route sharing for rider: {$rider->name}");
    }

    public function regenerateCode(Rider $rider): string
    {
        $code = $rider->generateDailyCode();

        ActivityLog::log('access_code_regenerated', "Regenerated access code for rider: {$rider->name}");

        return $code;
    }

    private function withUploads(array $validated, Request $request): array
    {
        foreach (self::FILE_FIELDS as $field) {
            unset($validated[$field]);
            if ($request->hasFile($field)) {
                $file = $request->file($field);
                $validated[$field] = $file->storeAs('riders/documents', time() . '_' . uniqid() . '_' . $field . '.' . $file->getClientOriginalExtension(), 'public');
            }
        }

        return $validated;
    }
}
