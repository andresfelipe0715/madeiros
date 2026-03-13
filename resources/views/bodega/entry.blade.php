<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">
                {{ __('Registrar Ingreso') }}: {{ \Illuminate\Support\Str::limit($material->name, 50) }}
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
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-success text-white py-3">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-box-seam me-2"></i>Registrar Entrada de Material
                            </h5>
                        </div>
                        <div class="card-body p-4">
                            <form action="{{ route('bodega.store-entry', $material) }}" method="POST" novalidate>
                                @csrf

                                <div class="mb-4">
                                    <label class="form-label text-muted small text-uppercase font-weight-bold">Material</label>
                                    <div class="p-3 bg-light rounded border">
                                        <div class="font-weight-bold text-dark">{{ $material->name }}</div>
                                        @if($material->reference_number)
                                            <div class="text-muted small mt-1">Ref: {{ $material->reference_number }}</div>
                                        @endif
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label text-muted small text-uppercase font-weight-bold">Stock Actual en Bodega</label>
                                        <div class="h4 mb-0 text-primary">
                                            {{ floor($material->bodega_quantity) == $material->bodega_quantity ? number_format($material->bodega_quantity, 0) : number_format($material->bodega_quantity, 2) }}
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label for="quantity" class="form-label font-weight-bold text-dark">Cantidad a Ingresar</label>
                                    <div class="input-group input-group-lg">
                                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-plus-lg text-success"></i></span>
                                        <input type="number" step="0.01" 
                                            class="form-control border-start-0 @error('quantity') is-invalid @enderror" 
                                            id="quantity" name="quantity" 
                                            value="{{ old('quantity') }}" placeholder="0.00" required autofocus>
                                    </div>
                                    @error('quantity')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                    <p class="text-muted small mt-2">Ingrese la cantidad exacta que está llegando físicamente.</p>
                                </div>

                                <div class="mb-4">
                                    <label for="notes" class="form-label font-weight-bold text-dark">Notas / Referencia de Ingreso</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" 
                                        id="notes" name="notes" rows="3" maxlength="255"
                                        placeholder="Ej: Factura #1234, Proveedor X, Ingreso mensual..." required>{{ old('notes') }}</textarea>
                                    <div class="d-flex justify-content-end">
                                        <small class="text-muted" id="notesCounter">0/255</small>
                                    </div>
                                    @error('notes')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                    <p class="text-muted small mt-1">Indique el motivo del ingreso o número de documento asociado.</p>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-lg shadow-sm">
                                        {{ __('Registrar Ingreso de Mercancía') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const notesArea = document.getElementById('notes');
            const counter = document.getElementById('notesCounter');
            
            if (notesArea && counter) {
                notesArea.addEventListener('input', function() {
                    counter.textContent = `${this.value.length}/255`;
                });
                // Initial count
                counter.textContent = `${notesArea.value.length}/255`;
            }
        });
    </script>
</x-app-layout>
