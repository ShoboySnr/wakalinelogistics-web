<?php

namespace App\Modules\Admin\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Bike;
use App\Modules\Admin\Models\Rider;
use App\Modules\Admin\Services\BikeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BikeController extends Controller
{
    public function __construct(private BikeService $bikes)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(5, (int) $request->get('per_page', 15)));
        $bikes = $this->bikes->listQuery($request)
            ->with('assignedRider:id,name,phone')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($bikes->items())->map(fn (Bike $bike) => $this->summary($bike)),
            'meta' => [
                'current_page' => $bikes->currentPage(),
                'last_page' => $bikes->lastPage(),
                'per_page' => $bikes->perPage(),
                'total' => $bikes->total(),
            ],
            'stats' => $this->bikes->stats(),
        ]);
    }

    public function formOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'statuses' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'maintenance', 'label' => 'Maintenance'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                ],
                'documents' => collect(BikeService::DOCUMENTS)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
                'riders' => Rider::where('status', 'active')->orderBy('name')->get(['id', 'name', 'phone']),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $bike = $this->bikes->create($request->validate($this->bikes->rules()), $request);

        return response()->json(['success' => true, 'message' => 'Bike created successfully', 'data' => $this->detail($bike)], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->detail(Bike::findOrFail($id))]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $bike = Bike::findOrFail($id);
        $this->bikes->update($bike, $request->validate($this->bikes->rules($bike)), $request);

        return response()->json(['success' => true, 'message' => 'Bike updated successfully', 'data' => $this->detail($bike->fresh())]);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->bikes->delete(Bike::findOrFail($id));

        return response()->json(['success' => true, 'message' => 'Bike deleted successfully']);
    }

    public function assignRider(Request $request, int $id): JsonResponse
    {
        $bike = Bike::findOrFail($id);
        $riderId = $request->validate(['assigned_rider_id' => 'nullable|exists:riders,id'])['assigned_rider_id'] ?? null;

        $this->bikes->assignRider($bike, $riderId);

        return response()->json(['success' => true, 'message' => $riderId ? 'Rider assigned' : 'Rider unassigned', 'data' => $this->detail($bike->fresh())]);
    }

    private function summary(Bike $bike): array
    {
        return [
            'id' => $bike->id,
            'bike_number' => $bike->bike_number,
            'plate_number' => $bike->plate_number,
            'brand' => $bike->brand,
            'model' => $bike->model,
            'color' => $bike->color,
            'year' => $bike->year,
            'status' => $bike->status,
            'assigned_rider' => $bike->assignedRider ? [
                'id' => $bike->assignedRider->id,
                'name' => $bike->assignedRider->name,
                'phone' => $bike->assignedRider->phone,
            ] : null,
            'has_expiring_documents' => $bike->hasExpiringDocuments(30),
            'has_expired_documents' => $bike->hasExpiredDocuments(),
            'next_maintenance_date' => $bike->next_maintenance_date?->toDateString(),
            'created_at' => $bike->created_at,
        ];
    }

    private function detail(Bike $bike): array
    {
        $documents = [];
        foreach (BikeService::DOCUMENTS as $key => $label) {
            $path = $bike->{"{$key}_document"};
            $expiry = $bike->{"{$key}_expiry_date"};
            $documents[] = [
                'key' => $key,
                'label' => $label,
                'url' => $path ? asset($path) : null,
                'expiry_date' => $expiry?->toDateString(),
                'status' => $this->documentStatus($expiry?->toDateString()),
            ];
        }

        $stickers = collect($bike->getStickersPermitsWithStatus())->map(fn ($sticker) => [
            'name' => $sticker['name'] ?? null,
            'serial_number' => $sticker['serial_number'] ?? null,
            'expiry_date' => $sticker['expiry_date'] ?? null,
            'url' => ($sticker['document'] ?? null) ? asset($sticker['document']) : null,
            'status' => $sticker['status'] ?? $this->documentStatus($sticker['expiry_date'] ?? null),
        ])->all();

        return $this->summary($bike) + [
            'engine_number' => $bike->engine_number,
            'chassis_number' => $bike->chassis_number,
            'last_maintenance_date' => $bike->last_maintenance_date?->toDateString(),
            'assignment_date' => $bike->assignment_date?->toDateString(),
            'notes' => $bike->notes,
            'documents' => $documents,
            'stickers' => $stickers,
        ];
    }

    private function documentStatus(?string $expiry): string
    {
        if (! $expiry) {
            return 'none';
        }

        $date = \Illuminate\Support\Carbon::parse($expiry);

        if ($date->isPast()) {
            return 'expired';
        }

        if ($date->lte(now()->addDays(30))) {
            return 'expiring';
        }

        return 'valid';
    }
}
