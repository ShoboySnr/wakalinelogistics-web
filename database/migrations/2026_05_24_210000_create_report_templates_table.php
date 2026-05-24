<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add report customization fields to clients table
        Schema::table('clients', function (Blueprint $table) {
            // Invoice/Report Branding
            if (!Schema::hasColumn('clients', 'company_logo')) {
                $table->string('company_logo')->nullable();
            }
            if (!Schema::hasColumn('clients', 'invoice_prefix')) {
                $table->string('invoice_prefix', 10)->default('INV');
            }
            if (!Schema::hasColumn('clients', 'invoice_counter')) {
                $table->integer('invoice_counter')->default(1000);
            }
            if (!Schema::hasColumn('clients', 'primary_color')) {
                $table->string('primary_color', 7)->default('#c1666b');
            }
            if (!Schema::hasColumn('clients', 'secondary_color')) {
                $table->string('secondary_color', 7)->default('#2c3e50');
            }
            
            // Company Details for Reports
            if (!Schema::hasColumn('clients', 'company_address')) {
                $table->text('company_address')->nullable();
            }
            if (!Schema::hasColumn('clients', 'company_phone')) {
                $table->string('company_phone')->nullable();
            }
            if (!Schema::hasColumn('clients', 'company_email')) {
                $table->string('company_email')->nullable();
            }
            if (!Schema::hasColumn('clients', 'company_website')) {
                $table->string('company_website')->nullable();
            }
            if (!Schema::hasColumn('clients', 'registration_number')) {
                $table->string('registration_number')->nullable();
            }
            
            // Report Preferences
            if (!Schema::hasColumn('clients', 'report_settings')) {
                $table->json('report_settings')->nullable();
            }
        });

        // Report templates and exports
        Schema::create('report_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('report_type'); // invoice, order_summary, customer_report, financial_summary, etc.
            $table->string('file_name');
            $table->string('file_path');
            $table->string('file_format'); // pdf, excel, csv
            $table->integer('file_size')->nullable();
            $table->json('filters')->nullable(); // Date range, status, etc.
            $table->json('data_summary')->nullable(); // Quick stats
            $table->timestamp('expires_at')->nullable();
            $table->integer('download_count')->default(0);
            $table->timestamps();
            
            $table->index(['client_id', 'report_type']);
            $table->index('created_at');
        });

        // Invoice customization templates
        Schema::create('invoice_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('clients')->onDelete('cascade');
            $table->string('template_name');
            $table->boolean('is_default')->default(false);
            $table->json('layout_settings'); // Header, footer, sections
            $table->json('style_settings'); // Colors, fonts, spacing
            $table->text('header_html')->nullable();
            $table->text('footer_html')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->text('payment_instructions')->nullable();
            $table->timestamps();
            
            $table->index('client_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_templates');
        Schema::dropIfExists('report_exports');
        
        Schema::table('clients', function (Blueprint $table) {
            $table->dropColumn([
                'company_logo',
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
            ]);
        });
    }
};
