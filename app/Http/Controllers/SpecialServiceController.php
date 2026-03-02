<?php

namespace App\Http\Controllers;

use App\Models\SpecialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SpecialServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        Gate::authorize('view-special-services');

        $query = SpecialService::query();

        if ($search = request('search')) {
            $query->where('name', 'LIKE', "%{$search}%");
        }

        $services = $query->latest()->paginate(15)->withQueryString();

        return view('special-services.index', compact('services'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create-special-services');

        return view('special-services.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create-special-services');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:special_services,name'],
            'active' => ['boolean'],
        ]);

        $validated['active'] = $request->boolean('active', true);

        SpecialService::create($validated);

        return redirect()->route('special-services.index')->with('success', 'Servicio especial creado exitosamente.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SpecialService $specialService): View
    {
        Gate::authorize('edit-special-services');

        return view('special-services.edit', compact('specialService'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SpecialService $specialService): RedirectResponse
    {
        Gate::authorize('edit-special-services');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:special_services,name,'.$specialService->id],
            'active' => ['boolean'],
        ]);

        $validated['active'] = $request->has('active');

        $specialService->update($validated);

        return redirect()->route('special-services.index')->with('success', 'Servicio especial actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SpecialService $specialService): RedirectResponse
    {
        Gate::authorize('edit-special-services');

        if ($specialService->orderSpecialServices()->exists()) {
            return back()->with('error', 'No se puede eliminar el servicio porque está asociado a una o más órdenes.');
        }

        $specialService->delete();

        return redirect()->route('special-services.index')->with('success', 'Servicio especial eliminado exitosamente.');
    }
}
