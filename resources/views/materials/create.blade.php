<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">
                {{ __('Nuevo Material') }}
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
                            <form action="{{ route('materials.store') }}" method="POST" novalidate>
                                @csrf

                                <div class="mb-3">
                                    <label for="name"
                                        class="form-label text-muted small text-uppercase font-weight-bold">Nombre del
                                        Material</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name') }}" required autofocus>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="stock_quantity"
                                        class="form-label text-muted small text-uppercase font-weight-bold">Cantidad en
                                        Stock</label>
                                    <input type="number" step="0.01"
                                        class="form-control @error('stock_quantity') is-invalid @enderror"
                                        id="stock_quantity" name="stock_quantity"
                                        value="{{ old('stock_quantity', '0') }}" required>
                                    @error('stock_quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <p class="text-muted small mt-1">La cantidad reservada se calculará automáticamente
                                        en función de las órdenes activas.</p>
                                </div>

                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Guardar Material') }}
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