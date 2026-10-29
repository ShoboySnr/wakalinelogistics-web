<?php

namespace App\Modules\Admin\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Rider;
use App\Modules\Admin\Resources\OrderResource;
use App\Modules\Admin\Services\RiderService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RiderController extends Controller
{
    public function __construct(private RiderService $riders)
    {
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = min(100, max(5, (int) $request->get('per_page', 15)));
        $riders = $this->riders->listQuery($request)
            ->withCount('orders')
            ->latest()
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => collect($riders->items())->map(fn (Rider $rider) => $this->summary($rider)),
            'meta' => [
                'current_page' => $riders->currentPage(),
                'last_page' => $riders->lastPage(),
                'per_page' => $riders->perPage(),
                'total' => $riders->total(),
            ],
            'stats' => [
                'total' => Rider::count(),
                'active' => Rider::where('status', 'active')->count(),
                'inactive' => Rider::where('status', 'inactive')->count(),
                'suspended' => Rider::where('status', 'suspended')->count(),
            ],
        ]);
    }

    public function formOptions(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => [
                'vehicle_types' => [
                    ['value' => 'bike', 'label' => 'Bike'],
                    ['value' => 'car', 'label' => 'Car'],
                    ['value' => 'van', 'label' => 'Van'],
                ],
                'statuses' => [
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                    ['value' => 'suspended', 'label' => 'Suspended'],
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $rider = $this->riders->create($request->validate($this->riders->rules()), $request);

        return response()->json(['success' => true, 'message' => 'Rider created successfully', 'data' => $this->detail($rider)], 201);
    }

    public function show(int $id): JsonResponse
    {
        return response()->json(['success' => true, 'data' => $this->detail(Rider::findOrFail($id))]);
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $rider = Rider::findOrFail($id);
        $this->riders->update($rider, $request->validate($this->riders->rules($rider)), $request);

        return response()->json(['success' => true, 'message' => 'Rider updated successfully', 'data' => $this->detail($rider->fresh())]);
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->riders->delete(Rider::findOrFail($id));
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }

        return response()->json(['success' => true, 'message' => 'Rider deleted successfully']);
    }

    public function orders(Request $request, int $id): JsonResponse
    {
        $orders = Rider::findOrFail($id)->orders()
            ->with(['client:id,name,company_name,pod_remittance_enabled'])
            ->latest()
            ->paginate(min(100, max(5, (int) $request->get('per_page', 10))));

        return response()->json([
            'success' => true,
            'data' => OrderResource::collection($orders->items()),
            'meta' => [
                'current_page' => $orders->currentPage(),
                'last_page' => $orders->lastPage(),
                'per_page' => $orders->perPage(),
                'total' => $orders->total(),
            ],
        ]);
    }

    public function shareLink(Request $request, int $id): JsonResponse
    {
        $rider = Rider::findOrFail($id);
        $action = $request->validate(['action' => 'required|in:generate,disable'])['action'];

        if ($action === 'disable') {
            $this->riders->disableShareLink($rider);

            return response()->json(['success' => true, 'message' => 'Route sharing disabled', 'data' => ['share_url' => null]]);
        }

        $share = $this->riders->generateShareLink($rider);

        return response()->json(['success' => true, 'message' => 'Route sharing enabled', 'data' => ['share_url' => $share->getShareUrl()]]);
    }

    public function regenerateCode(int $id): JsonResponse
    {
        $rider = Rider::findOrFail($id);
        $code = $this->riders->regenerateCode($rider);

        return response()->json(['success' => true, 'message' => 'Access code regenerated', 'data' => ['daily_code' => $code]]);
    }

    public function location(int $id): JsonResponse
    {
        $rider = Rider::findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'current_latitude' => $rider->current_latitude !== null ? (float) $rider->current_latitude : null,
                'current_longitude' => $rider->current_longitude !== null ? (float) $rider->current_longitude : null,
                'last_location_update' => $rider->last_location_update,
            ],
        ]);
    }

    private function summary(Rider $rider): array
    {
        return [
            'id' => $rider->id,
            'name' => $rider->name,
            'email' => $rider->email,
            'phone' => $rider->phone,
            'vehicle_type' => $rider->vehicle_type,
            'vehicle_number' => $rider->vehicle_number,
            'status' => $rider->status,
            'rating' => $rider->rating !== null ? (float) $rider->rating : null,
            'total_deliveries' => (int) $rider->total_deliveries,
            'orders_count' => $rider->orders_count ?? $rider->orders()->count(),
            'is_online' => (bool) $rider->is_online,
            'photo' => $rider->rider_photo ? Storage::disk('public')->url($rider->rider_photo) : null,
            'created_at' => $rider->created_at,
        ];
    }

    private function detail(Rider $rider): array
    {
        $files = [];
        foreach (RiderService::FILE_FIELDS as $field) {
            $files[$field] = $rider->{$field} ? Storage::disk('public')->url($rider->{$field}) : null;
        }

        $share = $this->riders->activeRouteShare($rider);

        return $this->summary($rider) + [
            'age' => $rider->age,
            'license_number' => $rider->license_number,
            'address' => $rider->address,
            'daily_code' => $rider->getDailyCode(),
            'current_latitude' => $rider->current_latitude !== null ? (float) $rider->current_latitude : null,
            'current_longitude' => $rider->current_longitude !== null ? (float) $rider->current_longitude : null,
            'last_location_update' => $rider->last_location_update,
            'share_url' => $share?->getShareUrl(),
            'witness' => [
                'full_name' => $rider->witness_full_name,
                'phone' => $rider->witness_phone,
                'address' => $rider->witness_address,
                'date' => $rider->witness_date,
            ],
            'guarantors' => collect([1, 2])->map(fn (int $n) => [
                'index' => $n,
                'full_name' => $rider->{"guarantor{$n}_full_name"},
                'dob' => $rider->{"guarantor{$n}_dob"},
                'nationality' => $rider->{"guarantor{$n}_nationality"},
                'occupation' => $rider->{"guarantor{$n}_occupation"},
                'nin' => $rider->{"guarantor{$n}_nin"},
                'residential_address' => $rider->{"guarantor{$n}_residential_address"},
                'phone' => $rider->{"guarantor{$n}_phone"},
                'alt_phone1' => $rider->{"guarantor{$n}_alt_phone1"},
                'alt_phone2' => $rider->{"guarantor{$n}_alt_phone2"},
                'work_address' => $rider->{"guarantor{$n}_work_address"},
                'relationship' => $rider->{"guarantor{$n}_relationship"},
                'years_known' => $rider->{"guarantor{$n}_years_known"},
            ])->all(),
            'pending_orders' => OrderResource::collection($this->riders->pendingOrders($rider))->resolve(),
            'files' => $files,
            'stats' => [
                'total_orders' => $rider->orders()->count(),
                'delivered_orders' => $rider->orders()->where('status', 'delivered')->count(),
                'active_orders' => $rider->activeOrders()->count(),
            ],
        ];
    }
}
