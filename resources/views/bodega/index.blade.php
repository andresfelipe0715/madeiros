<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">
                {{ __('Gestión de Bodega') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-fluid px-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <form action="{{ route('bodega.index') }}" method="GET" class="d-flex align-items-center">
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
                        <a href="{{ route('bodega.index') }}"
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
                                    <th class="px-4 py-3">Nombre del Material</th>
                                    <th class="px-4 py-3 text-primary">Bodega</th>
                                    <th class="px-4 py-3 text-muted">Stock PT (Info)</th>
                                    <th class="px-4 py-3 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="align-middle">
                                @forelse ($materials as $material)
                                    <tr>
                                        <td class="px-4 py-3 font-weight-bold">
                                            {{ $material->name }}
                                        </td>
                                        <td class="px-4 py-3 font-weight-bold text-primary">
                                            {{ floor($material->bodega_quantity) == $material->bodega_quantity ? number_format($material->bodega_quantity, 0) : number_format($material->bodega_quantity, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-muted">
                                            {{ floor($material->stock_quantity) == $material->stock_quantity ? number_format($material->stock_quantity, 0) : number_format($material->stock_quantity, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="d-flex justify-content-center gap-2">
                                                @can('edit-bodega')
                                                    <a href="{{ route('bodega.edit', $material) }}"
                                                        class="btn btn-sm btn-outline-primary" title="Ajustar Bodega">
                                                        <i class="fas fa-edit"></i> Ajustar
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-success"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#transferModal{{ $material->id }}"
                                                        title="Transferir al Punto de Venta">
                                                        <i class="fas fa-exchange-alt"></i> Transferir
                                                    </button>
                                                @endcan
                                            </div>

                                            @can('edit-bodega')
                                                <!-- Modal de Transferencia -->
                                                <div class="modal fade" id="transferModal{{ $material->id }}" tabindex="-1"
                                                    aria-hidden="true">
                                                    <div class="modal-dialog">
                                                        <form action="{{ route('bodega.transfer', $material) }}"
                                                            method="POST">
                                                            @csrf
                                                            <div class="modal-content text-start">
                                                                <div class="modal-header">
                                                                    <h5 class="modal-title">Transferir Material:
                                                                        {{ $material->name }}</h5>
                                                                    <button type="button" class="btn-close"
                                                                        data-bs-dismiss="modal" aria-label="Close"></button>
                                                                </div>
                                                                <div class="modal-body">
                                                                    <p class="text-muted small">Mueve material desde Bodega
                                                                        hacia el Punto de Venta.</p>
                                                                    <div class="mb-3">
                                                                        <label class="form-label">Cantidad a transferir (A la zona de ventas)</label>
                                                                        <div class="d-flex justify-content-between text-muted small mb-2">
                                                                            <span>Bodega actual: {{ floor($material->bodega_quantity) == $material->bodega_quantity ? number_format($material->bodega_quantity, 0) : number_format($material->bodega_quantity, 2) }}</span>
                                                                            <span>Ventas actual: {{ floor($material->stock_quantity) == $material->stock_quantity ? number_format($material->stock_quantity, 0) : number_format($material->stock_quantity, 2) }}</span>
                                                                        </div>
                                                                        <input type="number" name="quantity"
                                                                            class="form-control" step="0.01" min="0.01"
                                                                            max="{{ $material->bodega_quantity }}" required>
                                                                    </div>
                                                                </div>
                                                                <div class="modal-footer">
                                                                    <button type="button" class="btn btn-secondary"
                                                                        data-bs-dismiss="modal">Cancelar</button>
                                                                    <button type="submit" class="btn btn-success">Transferir al
                                                                        Punto de Venta</button>
                                                                </div>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-5 text-center text-muted">
                                            No se encontraron materiales listados en la base de datos.
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
