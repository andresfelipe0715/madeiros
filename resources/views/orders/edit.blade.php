<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">
                {{ __('Gestionar Orden') }} #{{ $order->id }}
            </h2>
            <a href="{{ route('orders.index') }}" class="btn btn-secondary">
                {{ __('Volver a la Lista') }}
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container text-start">
            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif


            <div class="row">
                <!-- Order Details -->
                <div class="col-md-5 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0">Detalles de la Orden</h5>

                        </div>
                        <div class="card-body">
                            @if($order->delivered_at)
                                <div class="alert alert-warning border-0 shadow-sm mb-4">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Esta orden no se puede editar porque ya tiene una fecha de entrega.
                                </div>
                            @endif

                            <form action="{{ route('orders.update', $order) }}" method="POST" novalidate>
                                @csrf
                                @method('PUT')

                                @php
                                    $isDisabled = $order->delivered_at ? 'disabled' : '';
                                @endphp

                                <div class="mb-3">
                                    <label
                                        class="form-label text-muted small text-uppercase font-weight-bold">Cliente</label>
                                    <input type="text" class="form-control bg-light" value="{{ $order->client->name }}"
                                        readonly disabled>
                                </div>

                                <div class="mb-3">
                                    <label for="invoice_number"
                                        class="form-label text-muted small text-uppercase font-weight-bold">Número de
                                        Factura/Pedido</label>
                                    <input type="text"
                                        class="form-control @error('invoice_number') is-invalid @enderror"
                                        id="invoice_number" name="invoice_number"
                                        value="{{ old('invoice_number', $order->invoice_number) }}" required {{ $isDisabled }} maxlength="50">
                                    @error('invoice_number')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="material"
                                        class="form-label text-muted small text-uppercase font-weight-bold">Material</label>
                                    <input type="text" class="form-control @error('material') is-invalid @enderror"
                                        id="material" name="material" value="{{ old('material', $order->material) }}"
                                        required {{ $isDisabled }} maxlength="255">
                                    @error('material')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="notes"
                                        class="form-label text-muted small text-uppercase font-weight-bold">Notas
                                        Especiales</label>
                                    <textarea class="form-control @error('notes') is-invalid @enderror" id="notes"
                                        name="notes" rows="3" {{ $isDisabled }}
                                        maxlength="300">{{ old('notes', $order->notes) }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" name="lleva_herrajeria"
                                            id="lleva_herrajeria" value="1" {{ old('lleva_herrajeria', $order->lleva_herrajeria) ? 'checked' : '' }} {{ $isDisabled }}>
                                        <label class="form-check-label text-muted small text-uppercase font-weight-bold"
                                            for="lleva_herrajeria">Incluye Herrajería</label>
                                    </div>
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" name="lleva_manual_armado"
                                            id="lleva_manual_armado" value="1" {{ old('lleva_manual_armado', $order->lleva_manual_armado) ? 'checked' : '' }} {{ $isDisabled }}>
                                        <label class="form-check-label text-muted small text-uppercase font-weight-bold"
                                            for="lleva_manual_armado">Incluye Manual de Armado</label>
                                    </div>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary" {{ $isDisabled }}>
                                        {{ __('Actualizar Información') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Production Workflow -->
                <div class="col-md-7 mb-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0">Ruta de Producción (Flujo de Trabajo)</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group mb-4">
                                @foreach($order->orderStages->sortBy('sequence') as $os)
                                    <div class="list-group-item d-flex justify-content-between align-items-center">
                                        <div>
                                            <span class="badge bg-secondary me-2">{{ $os->sequence }}</span>
                                            <strong>{{ $os->stage->name }}</strong>
                                            @if($os->started_at)
                                                <small class="text-muted ms-2">(Iniciado:
                                                    {{ $os->started_at->format('d/m H:i') }})</small>
                                            @else
                                                <small class="text-warning ms-2">(Pendiente)</small>
                                            @endif
                                        </div>

                                        @if(!$os->started_at)
                                            @if($os->stage_id !== $finalStageId)
                                                <form action="{{ route('orders.remove-stage', [$order, $os->stage]) }}"
                                                    method="POST">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-outline-danger"
                                                        onclick="return confirm('¿Está seguro de eliminar esta etapa?')">
                                                        Eliminar
                                                    </button>
                                                </form>
                                            @else
                                                <span class="badge bg-light text-dark border">Obligatorio</span>
                                            @endif
                                        @else
                                            <span class="text-muted small">En progreso/Completada</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <hr>

                            <h6 class="font-weight-bold mb-3">Añadir Nueva Etapa</h6>
                            <form action="{{ route('orders.add-stage', $order) }}" method="POST" class="row g-3">
                                @csrf
                                <div class="col-md-8">
                                    <select name="stage_id" class="form-select @error('stage_id') is-invalid @enderror"
                                        required>
                                        <option value="">Seleccione etapa...</option>
                                        @foreach($allStages as $stage)
                                            @unless($order->orderStages->contains('stage_id', $stage->id))
                                                <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                            @endunless
                                        @endforeach
                                    </select>
                                    @error('stage_id')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-outline-primary w-100">
                                        Añadir Etapa
                                    </button>
                                </div>
                            </form>
                            <div class="mt-2">
                                <small class="text-muted">Nota: La etapa se insertará automáticamente en la posición
                                    lógica
                                    según el flujo de producción.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .form-switch .form-check-input {
            background-size: 1rem 1rem;
            background-position: left center;
        }

        .form-switch .form-check-input:checked {
            background-position: right center;
        }
    </style>
</x-app-layout>