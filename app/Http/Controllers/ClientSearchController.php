<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ClientSearchController extends Controller
{
    /**
     * Search for clients based on a query string.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->get('q');

        $clients = Client::query()
            ->when($query, function ($queryBuilder) use ($query) {
                $queryBuilder->where(function ($q) use ($query) {
                    $q->where('name', 'LIKE', "%{$query}%")
                        ->orWhere('document', 'LIKE', "%{$query}%");
                });
            })
            ->orderBy('name')
            ->paginate(25, ['id', 'name', 'document']);

        return response()->json([
            'data' => $clients->items(),
            'next_page_url' => $clients->appends(['q' => $query])->nextPageUrl(),
        ]);
    }
}
