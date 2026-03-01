<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\SpecialService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Search for materials with pagination.
     */
    public function materials(Request $request): JsonResponse
    {
        $query = $request->get('q');

        $materials = Material::query()
            ->when($query, function ($qb) use ($query) {
                $qb->where('name', 'LIKE', "%{$query}%");
            })
            ->orderBy('name')
            ->paginate(25);

        $data = collect($materials->items())->map(function ($material) {
            return [
                'id' => $material->id,
                'name' => $material->name,
                'available_quantity' => $material->availableQuantity(),
            ];
        });

        return response()->json([
            'data' => $data,
            'next_page_url' => $materials->appends(['q' => $query])->nextPageUrl(),
        ]);
    }

    /**
     * Search for special services with pagination.
     */
    public function specialServices(Request $request): JsonResponse
    {
        $query = $request->get('q');

        $services = SpecialService::query()
            ->where('active', true)
            ->when($query, function ($qb) use ($query) {
                $qb->where('name', 'LIKE', "%{$query}%");
            })
            ->orderBy('name')
            ->paginate(25, ['id', 'name']);

        return response()->json([
            'data' => $services->items(),
            'next_page_url' => $services->appends(['q' => $query])->nextPageUrl(),
        ]);
    }
}
