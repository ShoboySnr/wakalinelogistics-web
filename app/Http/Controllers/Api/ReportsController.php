<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Models\Client;
use App\Modules\Admin\Models\Order;
use App\Modules\Admin\Models\ReportExport;
use App\Modules\Admin\Models\InvoiceTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class ReportsController extends Controller
{
    /**
     * Get report branding settings
     */
    public function getBranding()
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'company_logo' => $user->company_logo,
                'invoice_prefix' => $user->invoice_prefix ?? 'INV',
                'invoice_counter' => $user->invoice_counter ?? 1000,
                'primary_color' => $user->primary_color ?? '#c1666b',
                'secondary_color' => $user->secondary_color ?? '#2c3e50',
                'company_address' => $user->company_address,
                'company_phone' => $user->company_phone,
                'company_email' => $user->company_email,
                'company_website' => $user->company_website,
                'tax_id' => $user->tax_id,
                'registration_number' => $user->registration_number,
                'report_settings' => $user->report_settings ?? [],
            ],
        ]);
    }

    /**
     * Update report branding settings
     */
    public function updateBranding(Request $request)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'invoice_prefix' => 'sometimes|string|max:10',
            'invoice_counter' => 'sometimes|integer|min:1',
            'primary_color' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'secondary_color' => 'sometimes|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'company_address' => 'sometimes|string|nullable',
            'company_phone' => 'sometimes|string|nullable',
            'company_email' => 'sometimes|email|nullable',
            'company_website' => 'sometimes|url|nullable',
            'tax_id' => 'sometimes|string|nullable',
            'registration_number' => 'sometimes|string|nullable',
            'report_settings' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $user->update($request->only([
            'invoice_prefix',
            'invoice_counter',
            'primary_color',
            'secondary_color',
            'company_address',
            'company_phone',
            'company_email',
            'company_website',
            'tax_id',
            'registration_number',
            'report_settings',
        ]));

        return response()->json([
            'success' => true,
            'message' => 'Branding settings updated successfully',
        ]);
    }

    /**
     * Get report statistics
     */
    public function getStats(Request $request)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $startDate = $request->query('start_date', now()->subDays(30)->format('Y-m-d'));
        $endDate = $request->query('end_date', now()->format('Y-m-d'));

        $orders = Order::where('client_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        $stats = [
            'total_orders' => $orders->count(),
            'total_revenue' => $orders->sum('price'),
            'completed_orders' => $orders->where('status', 'delivered')->count(),
            'pending_orders' => $orders->whereIn('status', ['pending', 'confirmed'])->count(),
            'cancelled_orders' => $orders->where('status', 'cancelled')->count(),
            'average_order_value' => $orders->count() > 0 ? $orders->avg('price') : 0,
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Generate and export report
     */
    public function generateReport(Request $request)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'report_type' => 'required|in:order_summary,customer_report,financial_summary,invoice',
            'format' => 'required|in:pdf,excel,csv',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'filters' => 'sometimes|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        // Generate filename
        $fileName = sprintf(
            '%s_%s_%s.%s',
            $request->report_type,
            now()->format('Y-m-d_His'),
            $user->id,
            $request->format === 'excel' ? 'xlsx' : $request->format
        );

        // Create export record
        $export = ReportExport::create([
            'client_id' => $user->id,
            'report_type' => $request->report_type,
            'file_name' => $fileName,
            'file_path' => 'reports/' . $fileName,
            'file_format' => $request->format,
            'filters' => $request->filters ?? [],
            'data_summary' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
            ],
            'expires_at' => now()->addDays(7),
        ]);

        // In production, you would generate the actual file here
        // For now, we'll return the export record

        return response()->json([
            'success' => true,
            'message' => 'Report generated successfully',
            'data' => [
                'export_id' => $export->id,
                'file_name' => $export->file_name,
                'download_url' => route('api.reports.download', $export->id),
                'expires_at' => $export->expires_at,
            ],
        ], 201);
    }

    /**
     * List export history
     */
    public function exportHistory()
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $exports = ReportExport::where('client_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get()
            ->map(function ($export) {
                return [
                    'id' => $export->id,
                    'report_type' => $export->report_type,
                    'file_name' => $export->file_name,
                    'file_format' => $export->file_format,
                    'file_size' => $export->getFormattedFileSize(),
                    'download_count' => $export->download_count,
                    'created_at' => $export->created_at,
                    'expires_at' => $export->expires_at,
                    'is_expired' => $export->isExpired(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $exports,
        ]);
    }

    /**
     * Get invoice templates
     */
    public function getTemplates()
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $templates = InvoiceTemplate::where('client_id', $user->id)
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $templates,
        ]);
    }

    /**
     * Create invoice template
     */
    public function createTemplate(Request $request)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'template_name' => 'required|string|max:255',
            'is_default' => 'sometimes|boolean',
            'layout_settings' => 'sometimes|array',
            'style_settings' => 'sometimes|array',
            'header_html' => 'sometimes|string|nullable',
            'footer_html' => 'sometimes|string|nullable',
            'terms_conditions' => 'sometimes|string|nullable',
            'payment_instructions' => 'sometimes|string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $template = InvoiceTemplate::create([
            'client_id' => $user->id,
            'template_name' => $request->template_name,
            'is_default' => $request->is_default ?? false,
            'layout_settings' => $request->layout_settings ?? [],
            'style_settings' => $request->style_settings ?? [],
            'header_html' => $request->header_html,
            'footer_html' => $request->footer_html,
            'terms_conditions' => $request->terms_conditions,
            'payment_instructions' => $request->payment_instructions,
        ]);

        if ($request->is_default) {
            $template->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'Template created successfully',
            'data' => $template,
        ], 201);
    }

    /**
     * Update invoice template
     */
    public function updateTemplate(Request $request, int $id)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $template = InvoiceTemplate::where('client_id', $user->id)->find($id);

        if (!$template) {
            return response()->json(['success' => false, 'message' => 'Template not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'template_name' => 'sometimes|string|max:255',
            'is_default' => 'sometimes|boolean',
            'layout_settings' => 'sometimes|array',
            'style_settings' => 'sometimes|array',
            'header_html' => 'sometimes|string|nullable',
            'footer_html' => 'sometimes|string|nullable',
            'terms_conditions' => 'sometimes|string|nullable',
            'payment_instructions' => 'sometimes|string|nullable',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $template->update($request->only([
            'template_name',
            'is_default',
            'layout_settings',
            'style_settings',
            'header_html',
            'footer_html',
            'terms_conditions',
            'payment_instructions',
        ]));

        if ($request->is_default) {
            $template->setAsDefault();
        }

        return response()->json([
            'success' => true,
            'message' => 'Template updated successfully',
            'data' => $template,
        ]);
    }

    /**
     * Delete invoice template
     */
    public function deleteTemplate(int $id)
    {
        $user = Auth::user();

        if (!$user instanceof Client) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $template = InvoiceTemplate::where('client_id', $user->id)->find($id);

        if (!$template) {
            return response()->json(['success' => false, 'message' => 'Template not found'], 404);
        }

        if ($template->is_default) {
            return response()->json(['success' => false, 'message' => 'Cannot delete default template'], 400);
        }

        $template->delete();

        return response()->json([
            'success' => true,
            'message' => 'Template deleted successfully',
        ]);
    }
}
