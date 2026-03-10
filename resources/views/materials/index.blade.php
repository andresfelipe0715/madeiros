<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">
                {{ __('Materiales') }}
            </h2>
            @can('create-materials')
                <a href="{{ route('materials.create') }}" class="btn btn-primary">
                    {{ __('Nuevo Material') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-fluid px-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <form action="{{ route('materials.index') }}" method="GET" class="d-flex align-items-center">
                    <div class="input-group shadow-sm border rounded-pill overflow-hidden bg-light search-pill"
                        style="width: 350px; transition: border-color 0.2s ease-in-out;">
                        <span class="input-group-text bg-transparent border-0 ps-3">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control bg-transparent border-0 py-2 shadow-none"
                            placeholder="Buscar por nombre..." value="{{ request('search') }}"
                            onkeyup="debounceSubmit(this.form)"
                            onfocus="this.parentElement.style.borderColor = '#0d6efd'"
                            onblur="this.parentElement.style.borderColor = '#dee2e6'">
                    </div>
                    @if(request('search'))
                        <a href="{{ route('materials.index') }}"
                            class="btn btn-link btn-sm text-decoration-none text-muted ms-2">Limpiar</a>
                    @endif
                </form>
                <div class="text-muted small">
                    Mostrando {{ $materials->firstItem() ?? 0 }} - {{ $materials->lastItem() ?? 0 }} de
                    {{ $materials->total() }} materiales
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light text-muted text-uppercase small font-weight-bold">
                                <tr>
                                    <th class="px-4 py-3">Nombre</th>
                                    <th class="px-4 py-3">Punto de Venta</th>
                                    <th class="px-4 py-3">Reservada</th>
                                    <th class="px-4 py-3">Disponible</th>
                                    <th class="px-4 py-3 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="align-middle">
                                @forelse ($materials as $material)
                                    <tr>
                                        <td class="px-4 py-3 font-weight-bold">
                                            {{ $material->name }}
                                        </td>
                                        <td class="px-4 py-3 text-muted">
                                            {{ floor($material->stock_quantity) == $material->stock_quantity ? number_format($material->stock_quantity, 0) : number_format($material->stock_quantity, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-muted">
                                            {{ floor($material->reserved_quantity) == $material->reserved_quantity ? number_format($material->reserved_quantity, 0) : number_format($material->reserved_quantity, 2) }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @php $available = $material->availableQuantity(); @endphp
                                            <span
                                                class="font-weight-bold {{ $available <= 0 ? 'text-danger' : 'text-success' }}">
                                                {{ floor($available) == $available ? number_format($available, 0) : number_format($available, 2) }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                @can('edit-materials')
                                                    <button type="button" class="btn btn-sm btn-outline-warning"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#adjustStockModal{{ $material->id }}"
                                                        title="Ajustar Stock">
                                                        <i class="fas fa-balance-scale"></i> Ajustar Stock
                                                    </button>
                                                    <a href="{{ route('materials.edit', $material) }}"
                                                        class="btn btn-sm btn-outline-primary" title="Editar Material">
                                                        <i class="fas fa-edit"></i> Editar
                                                    </a>
                                                    <form action="{{ route('materials.destroy', $material) }}" method="POST"
                                                        onsubmit="return confirm('¿Está seguro de eliminar este material?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            title="Eliminar Material">
                                                            <i class="fas fa-trash"></i> Eliminar
                                                        </button>
                                                    </form>
                                                @endcan
                                            </div>
                                        </td>
                                    </tr>

                                    <!-- Adjust Stock Modal -->
                                    <div class="modal fade" id="adjustStockModal{{ $material->id }}" tabindex="-1"
                                        aria-labelledby="adjustStockModalLabel{{ $material->id }}" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content text-start">
                                                <div class="modal-header bg-warning text-dark">
                                                    <h5 class="modal-title" id="adjustStockModalLabel{{ $material->id }}">
                                                        Ajustar Stock: {{ $material->name }}</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"
                                                        aria-label="Close"></button>
                                                </div>
                                                <form action="{{ route('materials.adjust', $material) }}" method="POST">
                                                    @csrf
                                                    <div class="modal-body">
                                                        <div class="alert alert-warning mb-3">
                                                            <i class="fas fa-exclamation-triangle"></i> Solo modificar para
                                                            ajustes o correcciones de inventario. Los ingresos normales
                                                            deben hacerse desde <strong>Bodega</strong> mediante
                                                            Transferencias.
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="adjusted_stock{{ $material->id }}"
                                                                class="form-label font-weight-bold">Nueva Cantidad en Punto
                                                                de Venta</label>
                                                            <input type="number" step="0.01" class="form-control"
                                                                id="adjusted_stock{{ $material->id }}" name="adjusted_stock"
                                                                value="{{ floor($material->stock_quantity) == $material->stock_quantity ? number_format($material->stock_quantity, 0, '.', '') : number_format($material->stock_quantity, 2, '.', '') }}"
                                                                required min="0">
                                                            <div class="form-text">Cantidad actual:
                                                                {{ floor($material->stock_quantity) == $material->stock_quantity ? number_format($material->stock_quantity, 0) : number_format($material->stock_quantity, 2) }}
                                                            </div>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="reason{{ $material->id }}"
                                                                class="form-label font-weight-bold">Motivo del Ajuste <span
                                                                    class="text-danger">*</span></label>
                                                            <textarea class="form-control" id="reason{{ $material->id }}"
                                                                name="reason" rows="2" required maxlength="300"
                                                                placeholder="Ej: Mercancía dañada, conteo físico no coincide..."></textarea>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer pb-0 border-top-0">
                                                        <button type="button" class="btn btn-secondary"
                                                            data-bs-dismiss="modal">Cancelar</button>
                                                        <button type="submit" class="btn btn-warning">Confirmar
                                                            Ajuste</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-5 text-center text-muted">
                                            No se encontraron materiales.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($materials->hasPages())
                    <div class="card-footer bg-white border-top-0 py-3">
                        {{ $materials->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        .search-pill input {
            padding-left: 1rem;
            outline: none;
        }

        .search-pill .input-group-text {
            padding-left: 0rem;
            padding-right: 0rem;
        }
    </style>
</x-app-layout>