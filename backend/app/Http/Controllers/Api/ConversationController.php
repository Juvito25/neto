<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Message;
use Illuminate\Http\Request;

class ConversationController extends Controller
{
    public function index(Request $request)
    {
        $tenant = $request->user()->tenant;
        
        $contacts = Contact::where('tenant_id', $tenant->id)
        ->with(['messages' => function ($q) {
            $q->latest()->limit(1);
        }])
        ->withCount('messages')
        ->when($request->search, fn($q, $search) => 
            $q->where('name', 'ilike', "%{$search}%")
              ->orWhere('phone', 'ilike', "%{$search}%")
        )
        ->orderByDesc('messages_count')
        ->paginate(20);

        return response()->json($contacts);
    }

    public function show(Request $request, Contact $contact)
    {
        $tenant = $request->user()->tenant;
        
        if ($contact->tenant_id !== $tenant->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        
        return response()->json($contact);
    }

    public function messages(Request $request, Contact $contact)
    {
        $tenant = $request->user()->tenant;
        
        if ($contact->tenant_id !== $tenant->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }
        
        $messages = Message::where('contact_id', $contact->id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($messages);
    }
}
