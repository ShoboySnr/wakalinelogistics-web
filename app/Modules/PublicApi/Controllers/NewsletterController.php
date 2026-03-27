<?php

namespace App\Modules\PublicApi\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\PublicApi\Services\NewsletterService;

class NewsletterController extends Controller
{
    public function subscribe(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email'
        ]);

        $service = new NewsletterService();
        $service->subscribe($data['email']);

        return response()->json(['success' => true, 'message' => 'Subscribed successfully']);
    }
}
