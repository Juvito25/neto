<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function recentConversations(Request $request)
    {
        $tenantId = $request->user()->tenant->id;

        $contacts = Contact::where('tenant_id', $tenantId)
            ->whereHas('messages')
            ->with(['messages' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->orderByDesc(
                \App\Models\Message::select('created_at')
                    ->whereColumn('contact_id', 'contacts.id')
                    ->latest()
                    ->limit(1)
            )
            ->limit(5)
            ->get()
            ->map(function ($contact) {
                $lastMsg = $contact->messages->first();
                $time = $lastMsg ? $lastMsg->created_at->diffForHumans() : '';
                $body = $lastMsg ? $lastMsg->body : '';

                if (mb_strlen($body) > 60) {
                    $body = mb_substr($body, 0, 57) . '...';
                }

                return [
                    'id' => $contact->id,
                    'name' => $contact->name,
                    'phone' => $contact->phone,
                    'last_message' => $body,
                    'time' => $time,
                ];
            });

        return response()->json(['data' => $contacts]);
    }
}
