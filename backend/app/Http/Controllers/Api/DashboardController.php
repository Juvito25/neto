<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Message;
use App\Models\Sale;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function metrics(Request $request)
    {
        $tenantId = $request->user()->tenant->id;

        $messagesReceived = Message::where('tenant_id', $tenantId)
            ->where('direction', 'in')
            ->count();
        
        $messagesResponded = Message::where('tenant_id', $tenantId)
            ->where('direction', 'out')
            ->count();

        $responseRate = $messagesReceived > 0 
            ? round(($messagesResponded / $messagesReceived) * 100) 
            : 0;

        $totalRevenue = Sale::where('tenant_id', $tenantId)
            ->where('status', 'confirmed')
            ->sum('total_amount');
        
        $pendingRevenue = Sale::where('tenant_id', $tenantId)
            ->where('status', 'pending')
            ->sum('total_amount');
        
        $salesCount = Sale::where('tenant_id', $tenantId)
            ->where('status', 'confirmed')
            ->count();

        return response()->json([
            'messagesReceived' => $messagesReceived,
            'messagesResponded' => $messagesResponded,
            'responseRate' => $responseRate,
            'totalRevenue' => (float)$totalRevenue,
            'pendingRevenue' => (float)$pendingRevenue,
            'salesCount' => $salesCount,
        ]);
    }

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
