<?php

// Class aliases for backward compatibility
// These allow old code to continue working while we transition to the new module structure

namespace App\Models;

// Admin module models
class_alias(\App\Modules\Admin\Models\Order::class, Order::class);
class_alias(\App\Modules\Admin\Models\Rider::class, Rider::class);
class_alias(\App\Modules\Admin\Models\Expense::class, Expense::class);
class_alias(\App\Modules\Admin\Models\ActivityLog::class, ActivityLog::class);
class_alias(\App\Modules\Admin\Models\Setting::class, Setting::class);

// Metter module models
class_alias(\App\Modules\Metter\Models\MetterConfiguration::class, MetterConfiguration::class);
class_alias(\App\Modules\Metter\Models\MetterFeature::class, MetterFeature::class);
