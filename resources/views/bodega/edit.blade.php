<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">
                {{ __('Ajustar Bodega') }}: {{ \Illuminate\Support\Str::limit($material->name, 50) }}
            </h2>
            <a href="{{ route('bodega.index') }}" class="btn btn-secondary">
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
                            <h5 class="card-title mb-0">Ajuste de Inventario en Bodega</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('bodega.update', $material) }}" method="POST" novalidate>
                                @csrf
                                @method('PUT')

                                <div class="mb-3">
                                    <label for="name"
                                        class="form-label text-muted small text-uppercase font-weight-bold">Nombre del
                                        Material</label>
                                    <input type="text" class="form-control bg-light" id="name" name="name"
                                        value="{{ $material->name }}" readonly disabled>
                                    <p class="text-muted small mt-1">El nombre del material solo puede ser modificado
                                        desde la gestión de Materiales.</p>
                                </div>

                                <div class="mb-3">
                                    <label for="stock_quantity"
                                        class="form-label text-muted small text-uppercase font-weight-bold">Stock en
                                        Punto de Venta</label>
                                    <input type="number" step="0.01" class="form-control bg-light"
                                        value="{{ floor($material->stock_quantity) == $material->stock_quantity ? number_format($material->stock_quantity, 0) : number_format($material->stock_quantity, 2) }}"
                                        readonly disabled>
                                    <p class="text-muted small mt-1">Información de lectura únicamente. Para mover
                                        producto de bodega a ventas, use el botón de <b>Transferir</b>.</p>
                                </div>

                                <div class="mb-3 border-top pt-4">
                                    <label for="bodega_quantity"
                                        class="form-label text-primary font-weight-bold">Cantidad Activa en
                                        Bodega</label>
                                    <input type="number" step="0.01"
                                        class="form-control form-control-lg border-primary @error('bodega_quantity') is-invalid @enderror"
                                        id="bodega_quantity" name="bodega_quantity"
                                        value="{{ old('bodega_quantity', $material->bodega_quantity) }}" required
                                        autofocus>
                                    @error('bodega_quantity')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <p class="text-primary small mt-1">Éste es el inventario real almacenado en bodega.
                                        Ajuste este valor si ingresa nueva mercancía o hay una merma.</p>
                                </div>

                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        {{ __('Guardar Cambio en Bodega') }}
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