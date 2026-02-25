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
                                    @if(auth()->user()->role->hasPermission('orders', 'edit') && $order->orderMaterials->whereNull('cancelled_at')->count() > 0)
                                        <br><small><strong>Nota:</strong> Tiene permiso para actualizar el consumo real de los materiales.</small>
                                    @endif
                                </div>
                            @endif

                            <form action="{{ route('orders.update', $order) }}" method="POST" novalidate>
                                @csrf
                                @method('PUT')

                                @php
                                    $isDisabled = $order->delivered_at ? 'disabled' : '';

                                    $hasEditPermission = auth()->user()->role->hasPermission('orders', 'edit');
                                    $hasActiveMaterials = $order->orderMaterials->whereNull('cancelled_at')->count() > 0;
                                    
                                    $canCorrectConsumption = $order->delivered_at && $hasActiveMaterials && $hasEditPermission;
                                    
                                    $btnDisabled = ($order->delivered_at && !$canCorrectConsumption) ? 'disabled' : '';
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
<div class="mb-3"
    x-data="{
        materials: {{ json_encode(old('materials', $order->orderMaterials->map(fn($om) => [
            'id' => $om->id,
            'material_id' => $om->material_id,
            'material_name' => $om->material->name,
            'estimated_quantity' => $om->estimated_quantity,
            'actual_quantity' => $om->actual_quantity,
            'notes' => $om->notes ?? '',
            'cancelled' => $om->cancelled_at !== null,
            'cancelled_at' => $om->cancelled_at ? $om->cancelled_at->format('Y-m-d H:i') : null,
        ])->toArray())) }}.map(m => ({
            ...m,
            cancelled: m.cancelled == 1 || m.cancelled === true || m.cancelled === '1'
        }))
    }">

    <div class="d-flex justify-content-between align-items-center mb-2">
        <label class="form-label text-muted small text-uppercase font-weight-bold mb-0">
            Reserva de Materiales
        </label>
        <span class="badge bg-secondary rounded-pill"
            x-text="materials.filter(m => !m.cancelled).length + ' Activos'"></span>
    </div>

    <!-- ACTIVE MATERIALS -->
    <div class="bg-light p-3 rounded border mb-3">
        <h6 class="small fw-bold text-primary mb-3">Materiales Activos</h6>

        <template x-for="(material, index) in materials" :key="index">
            <template x-if="!material.cancelled">
                <div class="mb-3 pb-3 border-bottom last-child-no-border">
                    <div class="row g-2 mb-2 align-items-center">
                        <input type="hidden" :name="`materials[${index}][id]`" x-model="material.id">
                        <input type="hidden" :name="`materials[${index}][cancelled]`" value="0">

                        <div class="col-md-6 col-12">
                            <select :name="`materials[${index}][material_id]`"
                                x-model="material.material_id"
                                class="form-select form-select-sm" {{ $isDisabled }}>
                                <option value="">Seleccionar Material...</option>
                                @foreach($materials as $m)
                                    <option value="{{ $m->id }}">
                                        {{ $m->name }} (Stock: {{ $m->stock_quantity }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 col-8">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0">Cant.</span>
                                <input type="number"
                                    :name="`materials[${index}][estimated_quantity]`"
                                    x-model="material.estimated_quantity"
                                    class="form-control border-start-0"
                                    placeholder="Est."
                                    min="0.01"
                                    step="0.01"
                                    {{ $isDisabled }}>
                            </div>
                        </div>

                        <div class="col-md-2 col-4 text-end">
                            @if(!$order->delivered_at)
                                <template x-if="material.id">
                                    <button type="button"
                                        @click="if(confirm('¿Cancelar este material? El stock reservado será liberado.')) { material.cancelled = true }"
                                        class="btn btn-sm btn-outline-danger border-0">
                                        <i class="bi bi-x-circle me-1"></i>
                                        <span class="small">Cancelar</span>
                                    </button>
                                </template>
                                <template x-if="!material.id">
                                    <button type="button"
                                        @click="materials.splice(index, 1)"
                                        class="btn btn-sm btn-outline-danger border-0">
                                        <i class="bi bi-trash me-1"></i>
                                        <span class="small">Quitar</span>
                                    </button>
                                </template>
                            @endif
                        </div>
                    </div>

                    @if($order->delivered_at)
                    <div class="row g-2 mb-2">
                        <div class="col-12">
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-primary text-white border-primary">Consumo Real</span>
                                <input type="number"
                                    :name="`materials[${index}][actual_quantity]`"
                                    x-model="material.actual_quantity"
                                    class="form-control border-primary"
                                    placeholder="Consumo Real"
                                    min="0"
                                    step="0.01"
                                    required>
                            </div>
                        </div>
                    </div>
                    @endif

                    <div class="row g-2">
                        <div class="col-12">
                            <div class="position-relative">
                                <input type="text"
                                    :name="`materials[${index}][notes]`"
                                    x-model="material.notes"
                                    class="form-control form-control-sm"
                                    placeholder="Notas (ej. Color, medidas...)"
                                    maxlength="50"
                                    {{ $isDisabled }}>
                                <div class="position-absolute end-0 top-50 translate-middle-y me-2 text-muted"
                                    style="font-size: .65rem;"
                                    x-text="material.notes.length + '/50'"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </template>

        @if(!$order->delivered_at)
        <button type="button"
            @click="materials.push({ id:null, material_id:'', estimated_quantity:'', notes:'', cancelled:false })"
            class="btn btn-sm btn-outline-primary mt-2 rounded-pill">
            <i class="bi bi-plus-lg me-1"></i>
            Añadir Material
        </button>
        @endif
    </div>

    <!-- CANCELLED MATERIALS -->
    <template x-if="materials.some(m => m.cancelled)">
        <div class="p-3 rounded border border-danger-subtle bg-danger-subtle bg-opacity-10 opacity-75">
            <h6 class="small fw-bold text-danger mb-3">Materiales Cancelados</h6>

            <template x-for="(material, index) in materials" :key="'cancelled-'+index">
                <template x-if="material.cancelled">
                    <div class="mb-2 pb-2 border-bottom border-danger-subtle last-child-no-border">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="text-danger small fw-bold"
                                    x-text="material.material_name || 'Nuevo Material'"></span>

                                <div class="text-muted extra-small">
                                    Cant: <span x-text="material.estimated_quantity"></span>
                                    <template x-if="material.notes">
                                        <span> | <span x-text="material.notes"></span></span>
                                    </template>
                                </div>

                                <div class="extra-small text-danger mt-1">
                                    <i class="bi bi-calendar-x me-1"></i>
                                    Cancelado <span x-text="material.cancelled_at"></span>
                                </div>
                            </div>

                            <input type="hidden" :name="`materials[${index}][id]`" x-model="material.id">
                            <input type="hidden" :name="`materials[${index}][material_id]`" x-model="material.material_id">
                            <input type="hidden" :name="`materials[${index}][estimated_quantity]`" x-model="material.estimated_quantity">
                            <input type="hidden" :name="`materials[${index}][notes]`" x-model="material.notes">
                            <input type="hidden" :name="`materials[${index}][cancelled]`" value="1">
                        </div>
                    </div>
                </template>
            </template>
        </div>
    </template>

    @error('materials')
        <div class="text-danger small mt-1">{{ $message }}</div>
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
                                    <button type="submit" class="btn btn-primary" {{ $btnDisabled }}>
                                        {{ __('Actualizar Información') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Production Workflow -->
                <div class="col-md-7 mb-4">
                    <div class="card shadow-sm">
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

        .last-child-no-border:last-child {
            border-bottom: none !important;
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }
    </style>
</x-app-layout>