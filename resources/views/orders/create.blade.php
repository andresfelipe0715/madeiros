<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center w-100">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Crear Nueva Orden') }}
            </h2>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm rounded-pill">
                <i class="bi bi-arrow-left"></i> Volver al Dashboard
            </a>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-9 col-lg-7">
                    <div class="card shadow-sm border-0">
                        <div class="bg-primary bg-gradient py-1"></div>
                        <div class="card-body p-4 p-md-5">
                            <form action="{{ route('orders.store') }}" method="POST" enctype="multipart/form-data"
                                novalidate x-data="{ 
                                    materials: {{ json_encode(old('materials', [['material_id' => '', 'estimated_quantity' => '', 'notes' => '']])) }},
                                    stockLookup: {{ json_encode($materials->mapWithKeys(fn($m) => [$m->id => (float) $m->availableQuantity()])) }},
                                    get hasErrors() {
                                        if (this.materials.length === 0) return true;
                                        return this.materials.some(m => {
                                            if (!m.material_id || !m.estimated_quantity) return false;
                                            return parseFloat(m.estimated_quantity) > (this.stockLookup[m.material_id] || 0);
                                        });
                                    }
                                }">
                                @csrf

                                <div class="mb-4">
                                    <label for="client_id" class="form-label fw-bold">Cliente</label>
                                    <div class="input-group custom-input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i
                                                class="bi bi-person-fill"></i></span>
                                        <select name="client_id" id="client_id"
                                            class="form-select border-start-0 @error('client_id') is-invalid @enderror"
                                            required placeholder="Escriba nombre o documento del cliente">

                                            @if($selectedClient)
                                                <option value="{{ $selectedClient->id }}" selected>
                                                    {{ $selectedClient->name }}{{ $selectedClient->document ? ' - ' . $selectedClient->document : '' }}
                                                </option>
                                            @endif
                                        </select>
                                    </div>
                                    @error('client_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="invoice_number" class="form-label fw-bold">Número de Factura /
                                        Pedido</label>
                                    <div class="input-group custom-input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i
                                                class="bi bi-hash"></i></span>
                                        <input type="text" name="invoice_number" id="invoice_number"
                                            class="form-control border-start-0 @error('invoice_number') is-invalid @enderror"
                                            value="{{ old('invoice_number') }}" required placeholder="e.g. FAC-1234"
                                            maxlength="50">
                                    </div>
                                    @error('invoice_number')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-bold">Reserva de Inventario</label>
                                    <p class="text-muted small mb-2">Seleccione los materiales y añada notas opcionales
                                        (ej: color, espesor, corte).</p>

                                    <div class="bg-light p-3 rounded-3 border">
                                        <template x-if="materials.length === 0">
                                            <div class="text-center py-3 text-muted">
                                                <i class="bi bi-info-circle me-1"></i> No se han aÃ±adido materiales
                                                aÃºn.
                                            </div>
                                        </template>

                                        <template x-for="(material, index) in materials" :key="index">
                                            <div class="mb-3 pb-3 border-bottom last-child-no-border">
                                                <div class="row g-2 mb-2 align-items-center">
                                                    <div class="col-md-8 col-7">
                                                        <select :name="`materials[${index}][material_id]`"
                                                            x-model="material.material_id"
                                                            class="form-select border-0 shadow-sm" required>
                                                            <option value="">Seleccione material...</option>
                                                            @foreach($materials as $m)
                                                                <option value="{{ $m->id }}">{{ $m->name }} (Disp:
                                                                    {{ $m->availableQuantity() }})
                                                                </option>
                                                            @endforeach
                                                        </select>
                                                    </div>
                                                    <div class="col-md-3 col-4">
                                                        <div class="input-group input-group-sm shadow-sm"
                                                            :class="material.material_id && material.estimated_quantity > (stockLookup[material.material_id] || 0) ? 'border border-danger rounded' : ''">
                                                            <input type="number"
                                                                :name="`materials[${index}][estimated_quantity]`"
                                                                x-model="material.estimated_quantity"
                                                                class="form-control border-0" placeholder="Cant."
                                                                min="0.01" step="0.01" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-1 text-end">
                                                        <button type="button" @click="materials.splice(index, 1)"
                                                            class="btn btn-link text-danger p-0"
                                                            title="Eliminar material">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="18"
                                                                height="18" fill="currentColor" class="bi bi-trash3"
                                                                viewBox="0 0 16 16">
                                                                <path
                                                                    d="M6.5 1h3a.5.5 0 0 1 .5.5v1H6v-1a.5.5 0 0 1 .5-.5M11 2.5v-1A1.5 1.5 0 0 0 9.5 0h-3A1.5 1.5 0 0 0 5 1.5v1H1.5a.5.5 0 0 0 0 1h.538l.853 10.66A2 2 0 0 0 4.885 16h6.23a2 2 0 0 0 1.994-1.84l.853-10.66h.538a.5.5 0 0 0 0-1zm1.958 1-.846 10.58a1 1 0 0 1-.997.92h-6.23a1 1 0 0 1-.997-.92L3.042 3.5zm-7.487 1a.5.5 0 0 1 .528.47l.5 8.5a.5.5 0 0 1-.998.06L5 5.03a.5.5 0 0 1 .47-.53Zm5.058 0a.5.5 0 0 1 .47.53l-.5 8.5a.5.5 0 1 1-.998-.06l.5-8.5a.5.5 0 0 1 .528-.47M8 4.5a.5.5 0 0 1 .5.5v8.5a.5.5 0 0 1-1 0V5a.5.5 0 0 1 .5-.5" />
                                                            </svg>
                                                        </button>
                                                    </div>
                                                </div>

                                                <!-- Stock Feedback Row -->
                                                <template x-if="material.material_id">
                                                    <div class="row g-2 mb-2">
                                                        <div class="col-md-8 col-7"></div>
                                                        <div class="col-md-3 col-4">
                                                            <div class="px-1">
                                                                <small class="d-block"
                                                                    :class="material.estimated_quantity > (stockLookup[material.material_id] || 0) ? 'text-danger fw-bold' : 'text-muted'"
                                                                    style="font-size: 0.7rem;">
                                                                    Stock: <span
                                                                        x-text="stockLookup[material.material_id]"></span>
                                                                </small>
                                                                <template
                                                                    x-if="material.estimated_quantity > (stockLookup[material.material_id] || 0)">
                                                                    <small class="text-danger d-block lh-1 mt-1"
                                                                        style="font-size: 0.65rem;">No puedes exceder el
                                                                        stock disponible.</small>
                                                                </template>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                                <div class="row g-2">
                                                    <div class="col-12">
                                                        <div class="position-relative">
                                                            <input type="text" :name="`materials[${index}][notes]`"
                                                                x-model="material.notes"
                                                                class="form-control form-control-sm border-0 shadow-sm"
                                                                placeholder="Notas: ej. Canto grueso, proveedor alterno..."
                                                                maxlength="50">
                                                            <div class="position-absolute end-0 top-50 translate-middle-y me-2 text-muted x-small"
                                                                :class="material.notes.length >= 50 ? 'text-danger fw-bold' : ''"
                                                                x-text="material.notes.length + '/50'">
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>

                                        <button type="button"
                                            @click="materials.push({ material_id: '', estimated_quantity: '', notes: '' })"
                                            class="btn btn-sm btn-outline-primary mt-2 rounded-pill">
                                            <i class="bi bi-plus-lg me-1"></i> Agregar Material
                                        </button>
                                    </div>
                                    @error('materials')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                    @error('materials.*')
                                        <div class="invalid-feedback d-block">Error en los materiales seleccionados.</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="notes" class="form-label fw-bold">Notas Especiales <span
                                            class="text-muted fw-normal small">(Opcional)</span></label>
                                    <div class="input-group custom-input-group">
                                        <span
                                            class="input-group-text bg-white border-end-0 text-muted align-items-start pt-2"><i
                                                class="bi bi-sticky-fill"></i></span>
                                        <textarea name="notes" id="notes"
                                            class="form-control border-start-0 @error('notes') is-invalid @enderror"
                                            rows="3"
                                            placeholder="Detalles sobre cortes, acabados o servicios específicos..."
                                            maxlength="300">{{ old('notes') }}</textarea>
                                    </div>
                                    @error('notes')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-5">
                                    <label class="form-label fw-bold d-block">Configuración Adicional</label>
                                    <div class="bg-light p-4 rounded-3 border mb-4">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="lleva_herrajeria"
                                                id="lleva_herrajeria" value="1" {{ old('lleva_herrajeria') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="lleva_herrajeria">Incluye
                                                Herrajería</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="lleva_manual_armado"
                                                id="lleva_manual_armado" value="1" {{ old('lleva_manual_armado') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="lleva_manual_armado">Incluye Manual de
                                                Armado</label>
                                        </div>
                                    </div>

                                    <label class="form-label fw-bold d-block">Ruta de Producción</label>
                                    <p class="text-muted small mb-3">Seleccione las etapas que requiere este pedido. Por
                                        defecto se seleccionan todas.</p>

                                    <!-- Alpine.js Stages Component -->
                                    <div class="workflow-container bg-light p-4 rounded-3 border" x-data="{
                                             groups: {{ json_encode($stageGroups->map(function ($group) {
    $oldStageIds = collect(old('stages'))->pluck('stage_id')->all();
    return [
        'id' => $group->id,
        'name' => $group->name,
        'stages' => $group->stages->map(function ($stage) use ($oldStageIds) {
            return [
                'id' => $stage->id,
                'name' => $stage->name,
                'is_delivery' => $stage->is_delivery_stage,
                'selected' => (is_array(old('stages')) && in_array($stage->id, $oldStageIds)) || !old('stages')
            ];
        })->values()->toArray()
    ];
})->values()->toArray()) }},
                                             
                                             moveUp(groupIndex, stageIndex) {
                                                if (stageIndex > 0) {
                                                    let temp = this.groups[groupIndex].stages[stageIndex];
                                                    this.groups[groupIndex].stages[stageIndex] = this.groups[groupIndex].stages[stageIndex - 1];
                                                    this.groups[groupIndex].stages[stageIndex - 1] = temp;
                                                }
                                             },
                                             moveDown(groupIndex, stageIndex) {
                                                if (stageIndex < this.groups[groupIndex].stages.length - 1) {
                                                    let temp = this.groups[groupIndex].stages[stageIndex];
                                                    this.groups[groupIndex].stages[stageIndex] = this.groups[groupIndex].stages[stageIndex + 1];
                                                    this.groups[groupIndex].stages[stageIndex + 1] = temp;
                                                }
                                             }
                                         }">

                                        <!-- Hidden Inputs for Form Submission -->
                                        <template x-for="(group, gIndex) in groups" :key="group.id">
                                            <template x-for="(stage, sIndex) in group.stages" :key="stage.id">
                                                <template x-if="stage.selected">
                                                    <div>
                                                        <!-- Global sequential index is calculated assuming all previous groups' selected stages are counted -->
                                                        <!-- To keep it simple, we just use a flat index counter using a hidden computed property or just generate an array -->
                                                    </div>
                                                </template>
                                            </template>
                                        </template>
                                        <!-- Better approach for hidden inputs: use a computed flat array of selected stages -->
                                        <div x-data="{
                                            get flatSelectedStages() {
                                                let flat = [];
                                                this.groups.forEach(g => {
                                                    g.stages.forEach(s => {
                                                        if(s.selected) flat.push(s);
                                                    });
                                                });
                                                return flat;
                                            }
                                        }">
                                            <template x-for="(stage, index) in flatSelectedStages" :key="stage.id">
                                                <div>
                                                    <input type="hidden" :name="`stages[${index}][stage_id]`"
                                                        :value="stage.id">
                                                    <input type="hidden" :name="`stages[${index}][sequence]`"
                                                        :value="index + 1">
                                                </div>
                                            </template>
                                        </div>

                                        <!-- UI Rendering -->
                                        <template x-for="(group, groupIndex) in groups" :key="group.id">
                                            <div class="mb-4">
                                                <h6 class="text-muted fw-bold mb-3 border-bottom pb-2"
                                                    x-text="group.name"></h6>

                                                <div
                                                    class="list-group list-group-flush border rounded-3 bg-white shadow-sm overflow-hidden">
                                                    <template x-for="(stage, stageIndex) in group.stages"
                                                        :key="stage.id">
                                                        <div class="list-group-item d-flex align-items-center justify-content-between p-3"
                                                            :class="{'bg-light opacity-75': !stage.selected}">

                                                            <!-- Checkbox & Label -->
                                                            <div class="d-flex align-items-center flex-grow-1">
                                                                <div class="form-check form-switch mb-0">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        :id="'stage_'+stage.id" x-model="stage.selected"
                                                                        :disabled="groupIndex === 0 && stageIndex === 0 || stage.is_delivery">
                                                                    <label class="form-check-label ms-2 py-1"
                                                                        :for="'stage_'+stage.id"
                                                                        style="cursor: pointer;">
                                                                        <span x-text="stage.name"
                                                                            :class="stage.selected ? 'fw-medium text-dark' : 'text-muted text-decoration-line-through'"></span>

                                                                        <!-- Badges -->
                                                                        <template
                                                                            x-if="groupIndex === 0 && stageIndex === 0">
                                                                            <span
                                                                                class="badge bg-soft-info text-info small rounded-pill ms-2">Inicio</span>
                                                                        </template>
                                                                        <template x-if="stage.is_delivery">
                                                                            <span
                                                                                class="badge bg-soft-success text-success small rounded-pill ms-2">Fin</span>
                                                                        </template>
                                                                    </label>
                                                                </div>
                                                            </div>

                                                            <!-- Dynamic Single Reorder Button -->
                                                            <template x-if="group.stages.length > 1">
                                                                <button type="button" class="btn-reorder-dynamic"
                                                                    @click="stageIndex === group.stages.length - 1 ? moveUp(groupIndex, stageIndex) : moveDown(groupIndex, stageIndex)"
                                                                    :title="stageIndex === group.stages.length - 1 ? 'Subir etapa' : 'Bajar etapa'">
                                                                    <svg viewBox="0 0 24 24" fill="none"
                                                                        stroke="currentColor" stroke-width="2.5"
                                                                        stroke-linecap="round" stroke-linejoin="round"
                                                                        class="chevron-icon"
                                                                        :class="stageIndex === group.stages.length - 1 ? 'rotate-up' : 'rotate-down'">
                                                                        <polyline points="6 9 12 15 18 9"></polyline>
                                                                    </svg>
                                                                </button>
                                                            </template>
                                                        </div>
                                                    </template>
                                                </div>

                                                <!-- Arrow pointing to next group -->
                                                <template x-if="groupIndex < groups.length - 1">
                                                    <div class="text-center my-3 text-muted opacity-25">
                                                        <i class="bi bi-arrow-down fs-4"></i>
                                                    </div>
                                                </template>
                                            </div>
                                        </template>
                                    </div>
                                    @error('stages')
                                        <div class="invalid-feedback d-block mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4" x-data="{ 
                                    fileName: '',
                                    clearFile() {
                                        this.fileName = '';
                                        $refs.fileInput.value = '';
                                    }
                                }">
                                    <label for="order_file" class="form-label fw-bold">Archivo de la Orden <span
                                            class="text-muted fw-normal small">(PDF, Opcional)</span></label>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="input-group custom-input-group flex-grow-1">
                                            <span class="input-group-text bg-white border-end-0 text-muted"><i
                                                    class="bi bi-file-earmark-pdf-fill"></i></span>
                                            <input type="file" name="order_file" id="order_file"
                                                class="form-control border-start-0 @error('order_file') is-invalid @enderror"
                                                accept="application/pdf" x-ref="fileInput"
                                                @change="fileName = $event.target.files[0] ? $event.target.files[0].name : ''">
                                        </div>

                                        <template x-if="fileName">
                                            <button type="button"
                                                class="btn btn-outline-danger shadow-sm rounded-pill px-3"
                                                @click="clearFile()" title="Quitar archivo">
                                                <i class="bi bi-trash-fill me-1"></i> Quitar
                                            </button>
                                        </template>
                                    </div>
                                </div>
                                <template x-if="fileName">
                                    <div class="mt-2 small text-primary fw-medium">
                                        <i class="bi bi-paperclip me-1"></i> Seleccionado: <span
                                            x-text="fileName"></span>
                                    </div>
                                </template>
                                <div class=" mt-1 x-small text-muted\>
 <i class=" bi bi-info-circle me-1\></i> Recomendación: Para archivos muy grandes, use un compresor de PDF (ej.
                                    iLovePDF) antes de subir.
                                </div>
                                @error('order_file')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                        </div>

                        <div class="d-grid mt-5">
                            <button type="submit"
                                class="btn btn-primary btn-lg rounded-pill shadow-sm transition-all hover-elevate py-3"
                                :disabled="hasErrors">
                                <i class="bi bi-check2-circle me-2"></i> Confirmar y Crear Orden
                            </button>
                        </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <style>
        input:-webkit-autofill,
        textarea:-webkit-autofill,
        select:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 1000px #fff inset !important;
            -webkit-text-fill-color: #212529 !important;
        }

        .bg-soft-info {
            background-color: rgba(13, 202, 240, 0.1);
        }

        .bg-soft-success {
            background-color: rgba(25, 135, 84, 0.1);
        }

        .hover-elevate:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1) !important;
        }

        .transition-all {
            transition: all 0.2s ease;
        }

        .workflow-container {
            max-width: 100%;
        }

        /* Improved Input Alignment and Focus */
        .custom-input-group {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            transition: all 0.2s ease;
            background-color: #fff;
            position: relative;
            /* Ensure dropdown displays correctly */
        }

        .custom-input-group:focus-within {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.15);
        }

        .custom-input-group .input-group-text,
        .custom-input-group .form-control,
        .custom-input-group .form-select {
            border: none;
            box-shadow: none;
            background-color: transparent;
        }

        .custom-input-group .form-control:focus,
        .custom-input-group .form-select:focus {
            box-shadow: none;
        }

        .custom-input-group .input-group-text {
            padding-right: 0.25rem;
            color: #6c757d;
        }

        .custom-input-group .form-control,
        .custom-input-group .form-select {
            padding-left: 0.5rem;
        }

        /* Border radius fixes since we removed overflow:hidden */
        .custom-input-group> :first-child {
            border-top-left-radius: 0.4rem;
            border-bottom-left-radius: 0.4rem;
        }

        .custom-input-group> :last-child {
            border-top-right-radius: 0.4rem;
            border-bottom-right-radius: 0.4rem;
        }

        /* Tom Select Integration */
        .ts-wrapper.form-select {
            padding: 0 !important;
            border: none !important;
            box-shadow: none !important;
        }

        .ts-control {
            border: none !important;
            padding: 0.375rem 0.5rem !important;
            background: transparent !important;
            box-shadow: none !important;
        }

        .ts-wrapper.single .ts-control {
            background-image: none !important;
        }

        .ts-wrapper.single.input-active .ts-control {
            background: #fff !important;
        }

        .ts-dropdown {
            border-radius: 0.5rem !important;
            margin-top: 5px !important;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
            border: 1px solid #dee2e6 !important;
            background-color: #fff !important;
            z-index: 1050 !important;
        }

        .ts-wrapper .ts-control input::placeholder {
            color: #6c757d !important;
            opacity: 1;
        }

        .ts-wrapper.loading .ts-control::after {
            content: " ";
            display: block;
            width: 14px;
            height: 14px;
            margin: 0;
            border-radius: 50%;
            border: 2px solid #0d6efd;
            border-color: #0d6efd transparent #0d6efd transparent;
            animation: ts-spinner 1.2s linear infinite;
            position: absolute;
            right: 10px;
            top: 50%;
            margin-top: -7px;
        }

        @keyframes ts-spinner {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }


        .form-switch .form-check-input {
            background-size: 1rem 1rem;
            background-position: left center;
        }

        .form-switch .form-check-input:checked {
            background-position: right center;
        }

        .workflow-container .form-check {
            display: flex;
            align-items: center;
            padding-left: 0;
            /* Remove Bootstrap default padding for flex layout */
            margin-bottom: 0;
        }

        .workflow-container .form-check-input {
            margin-top: 0;
            margin-left: 0;
            float: none;
            /* Reset Bootstrap float */
            cursor: pointer;
            flex-shrink: 0;
        }

        .workflow-container .form-check-label {
            margin-left: 0.75rem;
            cursor: pointer;
            line-height: 1.2;
            padding: 0;
        }

        .last-child-no-border:last-child {
            border-bottom: none !important;
            margin-bottom: 0 !important;
            padding-bottom: 0 !important;
        }

        .btn-reorder-dynamic {
            width: 36px;
            height: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
            border: 1px solid #e2e8f0;
            background: #fff;
            color: #64748b;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 0;
            cursor: pointer;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .btn-reorder-dynamic:hover {
            color: #0d6efd;
            border-color: #0d6efd;
            background: #f0f7ff;
            box-shadow: 0 4px 12px rgba(13, 110, 253, 0.1);
        }

        .btn-reorder-dynamic:active {
            transform: scale(0.92);
        }

        .btn-reorder-dynamic svg {
            width: 18px;
            height: 18px;
            transition: transform 0.4s cubic-bezier(0.68, -0.6, 0.32, 1.6);
        }

        .rotate-up {
            transform: rotate(180deg);
        }

        .rotate-down {
            transform: rotate(0deg);
        }

        .chevron-icon {
            will-change: transform;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var settings = {
                valueField: 'id',
                labelField: 'name',
                searchField: ['name', 'document'],
                create: false,
                placeholder: 'Escriba nombre o documento del cliente',
                allowEmptyOption: false,
                loadThrottle: 300, // Debounce 300ms
                load: function (query, callback) {
                    if (query.length < 2) return callback();

                    var url = '{{ route("clients.search") }}?q=' + encodeURIComponent(query);
                    fetch(url)
                        .then(response => response.json())
                        .then(json => {
                            callback(json);
                        }).catch(() => {
                            callback();
                        });
                },
                render: {
                    option: function (item, escape) {
                        var name = item.name;
                        var document = item.document;

                        if (!name && !document) {
                            return null;
                        }

                        var label = escape(name);
                        if (document) {
                            label += '-' + escape(document);
                        }
                        return '<div class="py-2 px-3 border-bottom">' +
                            '<span class="d-block">' + label + '</span>' +
                            '</div>';
                    },
                    item: function (item, escape) {
                        var name = item.name;
                        var document = item.document;

                        if (!name && !document) {
                            return null;
                        }

                        var label = escape(name);
                        if (document) {
                            label += '-' + escape(document);
                        }
                        return '<div class="py-0">' + label + '</div>';
                    }
                }
            };
            new TomSelect('#client_id', settings);
        });
    </script>
</x-app-layout>