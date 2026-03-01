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

                            <form action="{{ route('orders.update', $order) }}" method="POST" enctype="multipart/form-data" novalidate
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
                                    })),
                                    stockLookup: {{ json_encode($materials->mapWithKeys(fn($m) => [$m->id => (float)$m->availableQuantity()])) }},
                                    thisOrderReservations: {{ json_encode($order->orderMaterials->whereNull('cancelled_at')->groupBy('material_id')->map(fn($group) => $group->sum('estimated_quantity'))) }},
                                    get hasErrors() {
                                        let totals = {};
                                        this.materials.forEach(m => {
                                            if (!m.cancelled && m.material_id && m.estimated_quantity) {
                                                totals[m.material_id] = (totals[m.material_id] || 0) + parseFloat(m.estimated_quantity);
                                            }
                                        });
                                        for (let id in totals) {
                                            let limit = (this.stockLookup[id] || 0) + (this.thisOrderReservations[id] || 0);
                                            if (totals[id] > limit) return true;
                                        }
                                        return false;
                                    },
                                    getAvailableFor(mid) {
                                        return (this.stockLookup[mid] || 0) + (this.thisOrderReservations[mid] || 0);
                                    }
                                }">
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
                                <div class="mb-4 mt-3">
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
                            <div class="input-group input-group-sm" :class="material.material_id && material.estimated_quantity > getAvailableFor(material.material_id) ? 'border border-danger rounded' : ''">
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

                    <!-- Stock Feedback Row -->
                    <template x-if="material.material_id && !material.cancelled">
                        <div class="row g-2 mb-2">
                            <div class="col-md-6 col-12"></div>
                            <div class="col-md-4 col-8">
                                <div class="px-1">
                                    <small class="d-block" :class="material.estimated_quantity > getAvailableFor(material.material_id) ? 'text-danger fw-bold' : 'text-muted'" style="font-size: 0.7rem;">
                                        Disp: <span x-text="(stockLookup[material.material_id] || 0).toFixed(2)"></span>
                                    </small>
                                    <template x-if="material.estimated_quantity > getAvailableFor(material.material_id)">
                                        <small class="text-danger d-block lh-1 mt-1" style="font-size: 0.65rem;">No puedes exceder el stock disponible.</small>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </template>

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

                                <div class="mb-4" x-data="{ 
                                    selectedServices: {{ json_encode(collect(old('special_services', $order->orderSpecialServices->map(fn($oss) => [
                                        'id' => $oss->id,
                                        'service_id' => $oss->service_id,
                                        'notes' => $oss->notes ?? '',
                                        'cancelled' => $oss->cancelled_at !== null,
                                        'cancelled_at' => $oss->cancelled_at ? $oss->cancelled_at->format('Y-m-d H:i') : null,
                                    ])))->keyBy('service_id')->map(fn($s) => [
                                        'id' => $s['id'] ?? null,
                                        'notes' => $s['notes'] ?? '',
                                        'cancelled' => $s['cancelled'] ?? false
                                    ])) }}
                                }">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label text-muted small text-uppercase font-weight-bold mb-0">Servicios Especiales</label>
                                        <span class="badge bg-secondary rounded-pill" x-text="Object.values(selectedServices).filter(s => !s.cancelled).length + ' Activos'"></span>
                                    </div>

                                    <div class="bg-light p-3 rounded border mb-3">
                                        <div class="row g-3">
                                            @foreach($specialServices as $service)
                                                <div class="col-md-6">
                                                    <div class="form-check form-switch mb-1">
                                                        <input class="form-check-input" type="checkbox" 
                                                            id="service_{{ $service->id }}" 
                                                            :checked="selectedServices['{{ $service->id }}'] !== undefined && !selectedServices['{{ $service->id }}'].cancelled"
                                                            {{ $isDisabled }}
                                                            @change="
                                                                if($el.checked) { 
                                                                    if(selectedServices['{{ $service->id }}']) {
                                                                        selectedServices['{{ $service->id }}'].cancelled = false;
                                                                    } else {
                                                                        selectedServices['{{ $service->id }}'] = {id: null, notes: '', cancelled: false};
                                                                    }
                                                                } else { 
                                                                    if(selectedServices['{{ $service->id }}'] && selectedServices['{{ $service->id }}'].id) {
                                                                        selectedServices['{{ $service->id }}'].cancelled = true;
                                                                    } else {
                                                                        delete selectedServices['{{ $service->id }}'];
                                                                    }
                                                                }
                                                            ">
                                                        <label class="form-check-label fw-medium" :class="selectedServices['{{ $service->id }}']?.cancelled ? 'text-muted text-decoration-line-through' : ''" for="service_{{ $service->id }}">
                                                            {{ $service->name }}
                                                        </label>
                                                    </div>
                                                    
                                                    <div x-show="selectedServices['{{ $service->id }}'] !== undefined" x-transition class="ms-4 mt-2">
                                                        <input type="hidden" 
                                                            :name="'special_services['+{{ $loop->index }}+'][id]'" 
                                                            x-model="selectedServices['{{ $service->id }}'].id"
                                                            :disabled="selectedServices['{{ $service->id }}'] === undefined">
                                                        <input type="hidden" 
                                                            :name="'special_services['+{{ $loop->index }}+'][service_id]'" 
                                                            value="{{ $service->id }}"
                                                            :disabled="selectedServices['{{ $service->id }}'] === undefined">
                                                        <input type="hidden" 
                                                            :name="'special_services['+{{ $loop->index }}+'][cancelled]'" 
                                                            :value="selectedServices['{{ $service->id }}'].cancelled ? 1 : 0"
                                                            :disabled="selectedServices['{{ $service->id }}'] === undefined">
                                                        
                                                        <div class="position-relative" x-show="!selectedServices['{{ $service->id }}'].cancelled">
                                                            <input type="text" 
                                                                :name="'special_services['+{{ $loop->index }}+'][notes]'" 
                                                                x-model="selectedServices['{{ $service->id }}'].notes"
                                                                :disabled="selectedServices['{{ $service->id }}'] === undefined"
                                                                class="form-control form-control-sm border-0 shadow-sm" 
                                                                placeholder="Notas para {{ $service->name }}..."
                                                                maxlength="50"
                                                                {{ $isDisabled }}>
                                                            <div class="position-absolute end-0 top-50 translate-middle-y me-2 text-muted x-small"
                                                                style="font-size: .65rem;"
                                                                x-text="(selectedServices['{{ $service->id }}']?.notes?.length || 0) + '/50'">
                                                            </div>
                                                        </div>
                                                        <template x-if="selectedServices['{{ $service->id }}'].cancelled">
                                                            <div class="extra-small text-danger">
                                                                <i class="bi bi-calendar-x me-1"></i> Cancelado (Al guardar)
                                                            </div>
                                                        </template>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                    @error('special_services')
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

                                <div class="mb-5">
                                    <label class="form-label fw-bold d-block">Configuración Adicional</label>
                                    <div class="bg-light p-4 rounded-3 border mb-4">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="lleva_herrajeria"
                                                id="lleva_herrajeria" value="1" {{ old('lleva_herrajeria', $order->lleva_herrajeria) ? 'checked' : '' }} {{ $isDisabled }}>
                                            <label class="form-check-label"
                                                for="lleva_herrajeria">Incluye Herrajería</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="lleva_manual_armado"
                                                id="lleva_manual_armado" value="1" {{ old('lleva_manual_armado', $order->lleva_manual_armado) ? 'checked' : '' }} {{ $isDisabled }}>
                                            <label class="form-check-label"
                                                for="lleva_manual_armado">Incluye Manual de Armado</label>
                                        </div>
                                    </div>
                                    
                                    <label class="form-label fw-bold d-block">Gestión de Archivos y Evidencia</label>
                                    <div class="bg-light p-4 rounded-3 border mb-4">
                                        <!-- PDF Section -->
                                        @php
                                            $orderPdf = $order->orderFiles->first(fn($f) => str_contains(strtolower($f->fileType->name ?? ''), 'orden'));
                                        @endphp

                                        @if($orderPdf)
                                                <div class="d-flex align-items-center justify-content-between mb-3 bg-white p-2 px-3 rounded border shadow-sm">
                                                    <div class="d-flex align-items-center">
                                                        <i class="bi bi-file-earmark-pdf-fill fs-4 text-danger me-3"></i>
                                                        <div>
                                                            <div class="fw-bold small">{{ $orderPdf->fileType->name ?? 'Orden' }}</div>
                                                            <a href="{{ $orderPdf->fileUrl }}" 
                                                               target="_blank" class="text-primary small text-decoration-none hover-underline">
                                                                <i class="bi bi-eye me-1"></i> Ver archivo actual
                                                            </a>
                                                        </div>
                                                    </div>
                                                    <span class="badge bg-soft-success text-success rounded-pill small border border-success border-opacity-25 pb-1">Existente</span>
                                                </div>
                                        @endif

                                        <div x-data="{ 
                                            fileName: '',
                                            clearFile() {
                                                this.fileName = '';
                                                $refs.fileInput.value = '';
                                            }
                                        }">
                                            <label for="order_file" class="form-label fw-bold">
                                                {{ $orderPdf ? 'Reemplazar Archivo' : 'Añadir Archivo' }} 
                                                <span class="text-muted fw-normal small">(PDF, Opcional)</span>
                                            </label>
                                            <div class="d-flex align-items-center gap-2">
                                                <div class="input-group custom-input-group flex-grow-1">
                                                    <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-upload"></i></span>
                                                    <input type="file" name="order_file" id="order_file"
                                                        class="form-control border-start-0 @error('order_file') is-invalid @enderror"
                                                        accept="application/pdf"
                                                        x-ref="fileInput"
                                                        @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''">
                                                </div>
                                                
                                                <template x-if="fileName">
                                                    <button type="button" class="btn btn-outline-danger shadow-sm rounded-pill px-3" @click="clearFile()" title="Quitar selección">
                                                        <i class="bi bi-trash-fill me-1"></i> Quitar
                                                    </button>
                                                </template>
                                            </div>
                                            <template x-if="fileName">
                                                <div class="mt-2 small text-primary fw-medium">
                                                    <i class="bi bi-paperclip me-1"></i> Seleccionado: <span x-text="fileName"></span>
                                                </div>
                                            </template>
                                            @if($orderPdf)
                                                <div class="mt-1 x-small text-muted">
                                                    <i class="bi bi-exclamation-triangle me-1 text-warning"></i> Al subir uno nuevo, el anterior será eliminado permanentemente.
                                                </div>
                                            @endif
                                            @error('order_file')
                                                <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                            @enderror
                                        </div>

                                        <hr class="my-4 text-muted opacity-25">

                                        <!-- Evidence Section -->
                                        <div x-data="{ 
                                            newPhotos: null, 
                                            photosCount: 0,
                                            existingPhotos: {{ $order->orderFiles->filter(fn($f) => str_contains(strtolower($f->fileType->name ?? ''), 'evidencia'))->count() }},
                                            deleting: []
                                        }">
                                            <label class="form-label fw-bold d-block">Fotos de Evidencia</label>
                                            
                                            @php
                                                $evPhotos = $order->orderFiles->filter(fn($f) => str_contains(strtolower($f->fileType->name ?? ''), 'evidencia'))->values();
                                            @endphp

                                            @if($evPhotos->count() > 0)
                                                @php
                                                    $imageUrls = $evPhotos->map(fn($p) => $p->fileUrl)->values()->toArray();
                                                @endphp
                                                <div class="row g-2 mb-3">
                                                    @foreach($evPhotos as $index => $photo)
                                                        <div class="col-6 col-md-4">
                                                            <div class="position-relative border rounded overflow-hidden shadow-sm shadow-hover" 
                                                                style="height: 120px;">
                                                                <img src="{{ $photo->fileUrl }}" 
                                                                    class="w-100 h-100 object-fit-cover cursor-zoom-in" 
                                                                    alt="Foto de evidencia"
                                                                    data-images="{{ json_encode($imageUrls) }}"
                                                                    onclick="openLightbox(JSON.parse(this.dataset.images), {{ $index }})">
                                                                
                                                                <div class="position-absolute top-0 end-0 p-1">
                                                                    <button type="button" 
                                                                        class="btn btn-sm btn-danger rounded-circle p-0 shadow d-flex align-items-center justify-content-center" 
                                                                        style="width: 36px; height: 36px;" 
                                                                        title="Eliminar foto"
                                                                        onclick="confirmDeletion('{{ route('order-files.destroy', $photo->id) }}')">
                                                                        <i class="bi bi-trash3-fill" style="font-size: 1.2rem;"></i>
                                                                    </button>
                                                                </div>

                                                                <div class="position-absolute bottom-0 start-0 w-100 bg-dark bg-opacity-50 py-1 px-2 text-white x-small d-flex justify-content-between align-items-center">
                                                                    <span>#{{ $loop->iteration }}</span>
                                                                    <span class="text-white"><i class="bi bi-image"></i></span>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <div x-show="existingPhotos < 2" class="mt-2" 
                                                x-data="{ 
                                                    clearInput() { 
                                                        $refs.evidenceInput.value = ''; 
                                                        $data.newPhotos = null; 
                                                        $data.photosCount = 0; 
                                                    } 
                                                }">
                                                <label for="evidence_photos" class="form-label small fw-bold">
                                                    Añadir Evidencia <span class="text-muted fw-normal">(Imágenes, Máx <span x-text="2 - existingPhotos"></span>)</span>
                                                </label>
                                                <div class="d-flex gap-2">
                                                    <div class="input-group custom-input-group flex-grow-1">
                                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-image"></i></span>
                                                        <input type="file" name="evidence_photos[]" id="evidence_photos"
                                                            class="form-control border-start-0"
                                                            accept="image/*"
                                                            multiple
                                                            x-ref="evidenceInput"
                                                            @change="newPhotos = $event.target.files; photosCount = newPhotos.length">
                                                    </div>
                                                    <template x-if="photosCount > 0">
                                                        <button type="button" class="btn btn-outline-danger shadow-sm rounded-pill px-3" @click="clearInput()" title="Quitar selección">
                                                            <i class="bi bi-trash-fill me-1"></i> Quitar
                                                        </button>
                                                    </template>
                                                </div>
                                                
                                                <template x-if="photosCount > 0">
                                                    <div class="mt-2 small" :class="existingPhotos + photosCount > 2 ? 'text-danger fw-bold' : 'text-primary'">
                                                        <i class="bi" :class="existingPhotos + photosCount > 2 ? 'bi-exclamation-triangle-fill' : 'bi-check-circle-fill'"></i>
                                                        Seleccionado: <span x-text="photosCount"></span> archivo(s)
                                                        <template x-if="existingPhotos + photosCount > 2">
                                                            <span> (Excede el límite de 2 total)</span>
                                                        </template>
                                                    </div>
                                                </template>
                                                
                                                <div class="mt-1 extra-small text-muted">
                                                    <i class="bi bi-info-circle me-1"></i> Las imágenes se comprimirán automáticamente para ahorrar espacio.
                                                </div>
                                            </div>

                                            <div x-show="existingPhotos >= 2 & deleting.length == 0" class="alert bg-success bg-opacity-10 text-success py-2 mt-2 extra-small mb-0 border border-success border-opacity-25 pb-1">
                                                <i class="bi bi-check-circle-fill me-1"></i> Límite de 2 fotos alcanzado.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <template x-if="hasErrors">
                                        <div class="alert alert-danger py-2 extra-small mb-0 text-center border-0 shadow-sm">
                                            <i class="bi bi-exclamation-triangle-fill me-1"></i>
                                            No se puede actualizar: Hay materiales que exceden el stock disponible.
                                        </div>
                                    </template>
                                    <button type="submit" class="btn btn-primary" {{ $btnDisabled }} :disabled="hasErrors">
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
                                            @if($os->stage_id !== $finalStageId && $os->stage_id !== $firstStageId)
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