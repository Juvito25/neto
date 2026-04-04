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
        $contacts = Contact::with(['messages' => function ($q) {
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

    public function show(Contact $contact)
    {
        return response()->json($contact);
    }

    public function messages(Request $request, Contact $contact)
    {
        $messages = Message::where('contact_id', $contact->id)
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        return response()->json($messages);
    }
}
