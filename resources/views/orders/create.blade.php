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
                    <div class="card shadow-sm border-0 overflow-hidden">
                        <div class="bg-primary bg-gradient py-1"></div>
                        <div class="card-body p-4 p-md-5">
                            <form action="{{ route('orders.store') }}" method="POST">
                                @csrf

                                <div class="mb-4">
                                    <label for="client_id" class="form-label fw-bold">Cliente</label>
                                    <div class="input-group custom-input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-person-fill"></i></span>
                                        <select name="client_id" id="client_id" class="form-select border-start-0 @error('client_id') is-invalid @enderror" required>
                                            <option value="">Seleccione un cliente</option>
                                            @foreach($clients as $client)
                                                <option value="{{ $client->id }}" {{ old('client_id') == $client->id ? 'selected' : '' }}>
                                                    {{ $client->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @error('client_id')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="invoice_number" class="form-label fw-bold">Número de Factura / Pedido</label>
                                    <div class="input-group custom-input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-hash"></i></span>
                                        <input type="text" name="invoice_number" id="invoice_number" class="form-control border-start-0 @error('invoice_number') is-invalid @enderror" value="{{ old('invoice_number') }}" required placeholder="e.g. FAC-1234">
                                    </div>
                                    @error('invoice_number')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="material" class="form-label fw-bold">Material</label>
                                    <div class="input-group custom-input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-box-seam-fill"></i></span>
                                        <input type="text" name="material" id="material" class="form-control border-start-0 @error('material') is-invalid @enderror" value="{{ old('material') }}" required placeholder="e.g. Melamina Roble 18mm">
                                    </div>
                                    @error('material')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-4">
                                    <label for="notes" class="form-label fw-bold">Notas Especiales <span class="text-muted fw-normal small">(Opcional)</span></label>
                                    <div class="input-group custom-input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted align-items-start pt-2"><i class="bi bi-sticky-fill"></i></span>
                                        <textarea name="notes" id="notes" class="form-control border-start-0 @error('notes') is-invalid @enderror" rows="3" placeholder="Detalles sobre cortes, acabados o servicios específicos...">{{ old('notes') }}</textarea>
                                    </div>
                                    @error('notes')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-5">
                                    <label class="form-label fw-bold d-block">Ruta de Producción</label>
                                    <p class="text-muted small mb-3">Seleccione las etapas que requiere este pedido. Por defecto se seleccionan todas.</p>
                                    
                                    <div class="workflow-container bg-light p-4 rounded-3 border">
                                        @foreach($stages as $stage)
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="stages[]" value="{{ $stage->id }}" id="stage_{{ $stage->id }}" 
                                                    {{ (is_array(old('stages')) && in_array($stage->id, old('stages'))) || (!old('stages') && true) ? 'checked' : '' }}>
                                                <label class="form-check-label d-flex justify-content-between align-items-center w-100" for="stage_{{ $stage->id }}">
                                                    <span>{{ $stage->name }}</span>
                                                    @if($loop->first)
                                                        <span class="badge bg-soft-info text-info small rounded-pill">Inicio</span>
                                                    @elseif($loop->last)
                                                        <span class="badge bg-soft-success text-success small rounded-pill">Fin</span>
                                                    @endif
                                                </label>
                                            </div>
                                            @if(!$loop->last)
                                                <div class="workflow-arrow text-center my-1 text-muted opacity-25">
                                                    <i class="bi bi-chevron-down"></i>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                    @error('stages')
                                        <div class="text-danger small mt-2">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-grid mt-5">
                                    <button type="submit" class="btn btn-primary btn-lg rounded-pill shadow-sm transition-all hover-elevate py-3">
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
        .bg-soft-info { background-color: rgba(13, 202, 240, 0.1); }
        .bg-soft-success { background-color: rgba(25, 135, 84, 0.1); }
        .hover-elevate:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.1) !important;
        }
        .transition-all { transition: all 0.2s ease; }
        .workflow-container {
            max-width: 100%;
        }

        /* Improved Input Alignment and Focus */
        .custom-input-group {
            border: 1px solid #dee2e6;
            border-radius: 0.5rem;
            overflow: hidden;
            transition: all 0.2s ease;
            background-color: #fff;
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
    </style>
</x-app-layout>
