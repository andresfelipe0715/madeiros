<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">
                {{ __('Editar Material') }}: {{ $material->name }}
            </h2>
            <a href="{{ route('materials.index') }}" class="btn btn-secondary">
                {{ __('Volver') }}
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container text-start">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0">Información del Material</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('materials.update', $material) }}" method="POST" novalidate>
                                @csrf
                                @method('PUT')

                                <div class="mb-3">
                                    <label for="name"
                                        class="form-label text-muted small text-uppercase font-weight-bold">Nombre del
                                        Material</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name', $material->name) }}" required
                                        autofocus>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="stock_quantity"
                                        class="form-label text-muted small text-uppercase font-weight-bold">Cantidad en
                                        Stock (Punto de Venta)</label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('stock_quantity') is-invalid @enderror"
                                        id="stock_quantity" name="stock_quantity"
                                        value="{{ old('stock_quantity', $material->stock_quantity) }}" required>
                                    @error('stock_quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mt-4 p-3 bg-light rounded border">
                                    <h6 class="text-muted small text-uppercase font-weight-bold mb-2">Información de
                                        Reserva</h6>
                                    <div class="d-flex justify-content-between mb-1">
                                        <span class="small text-muted">Cantidad Reservada:</span>
                                        <span class="small">{{ $material->reserved_quantity }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="small text-muted">Cantidad Disponible:</span>
                                        <span class="small font-weight-bold">{{ $material->availableQuantity() }}</span>
                                    </div>
                                </div>

                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Actualizar Material') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>