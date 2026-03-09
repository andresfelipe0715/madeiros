<?php

namespace App\Http\Controllers;

use App\Models\Material;
use App\Models\OrderMaterial;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class MaterialConsumptionController extends Controller
{
    /**
     * Display a calendar view of material consumption.
     */
    public function index(Request $request): View
    {
        Gate::authorize('view-materials');

        $month = $request->integer('month', now()->month);
        $year = $request->integer('year', now()->year);
        $materialId = $request->get('material_id');

        $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
        $endDate = Carbon::createFromDate($year, $month, 1)->endOfMonth();

        $query = OrderMaterial::with(['order', 'material'])
            ->active()
            ->consumed()
            ->whereBetween('consumed_at', [$startDate, $endDate]);

        if ($materialId) {
            $query->where('material_id', $materialId);
        }

        $consumptions = $query->get()->groupBy(function ($item) {
            return $item->consumed_at->format('Y-m-d');
        });

        // Calculate totals per day and material
        $dailyData = [];
        foreach ($consumptions as $date => $items) {
            $dailyData[$date] = $items->groupBy('material_id')->map(function ($group) {
                return [
                    'material_name' => $group->first()->material->name,
                    'total_actual_quantity' => $group->sum('actual_quantity'),
                    'orders' => $group->map(function ($item) {
                        return [
                            'id' => $item->order_id,
                            'invoice_number' => $item->order->invoice_number,
                        ];
                    })->unique('id'),
                ];
            });
        }

        $materials = Material::orderBy('name')->get();

        $currentDate = Carbon::createFromDate($year, $month, 1);
        $prevMonth = $currentDate->copy()->subMonth();
        $nextMonth = $currentDate->copy()->addMonth();

        return view('materials-consumption.index', compact(
            'dailyData',
            'materials',
            'month',
            'year',
            'materialId',
            'startDate',
            'endDate',
            'prevMonth',
            'nextMonth'
        ));
    }
}
