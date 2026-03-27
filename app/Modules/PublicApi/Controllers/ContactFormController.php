<?php

namespace App\Modules\PublicApi\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Modules\PublicApi\Services\ContactFormService;

class ContactFormController extends Controller
{
    public function send(Request $request)
    {
        $data = $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'nullable|string',
            'email' => 'required|email',
            'phone' => 'required|string',
            'message' => 'required|string',
        ]);

        $service = new ContactFormService();
        $service->send($data);

        return response()->json(['success' => true, 'message' => 'Message sent']);
    }
}
