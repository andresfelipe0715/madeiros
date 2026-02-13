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
                            <form action="{{ route('orders.store') }}" method="POST" enctype="multipart/form-data">
                                @csrf

                                <div class="mb-4">
                                    <label for="client_id" class="form-label fw-bold">Cliente</label>
                                    <div class="input-group custom-input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-person-fill"></i></span>
                                        <select name="client_id" id="client_id" class="form-select border-start-0 @error('client_id') is-invalid @enderror" required placeholder="Escriba nombre o documento del cliente">
                                            
                                            @if($selectedClient)
                                                <option value="{{ $selectedClient->id }}" selected>{{ $selectedClient->name }}</option>
                                            @endif
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
                                    <label class="form-label fw-bold d-block">Configuración Adicional</label>
                                    <div class="bg-light p-4 rounded-3 border mb-4">
                                        <div class="form-check form-switch mb-2">
                                            <input class="form-check-input" type="checkbox" name="lleva_herrajeria" id="lleva_herrajeria" value="1" {{ old('lleva_herrajeria') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="lleva_herrajeria">Incluye Herrajería</label>
                                        </div>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="lleva_manual_armado" id="lleva_manual_armado" value="1" {{ old('lleva_manual_armado') ? 'checked' : '' }}>
                                            <label class="form-check-label" for="lleva_manual_armado">Incluye Manual de Armado</label>
                                        </div>
                                    </div>

                                    <label class="form-label fw-bold d-block">Ruta de Producción</label>
                                    <p class="text-muted small mb-3">Seleccione las etapas que requiere este pedido. Por defecto se seleccionan todas.</p>
                                    
                                    <div class="workflow-container bg-light p-4 rounded-3 border">
                                        @foreach($stages as $stage)
                                            <div class="form-check form-switch mb-3">
                                                <input class="form-check-input" type="checkbox" name="stages[]" value="{{ $stage->id }}" id="stage_{{ $stage->id }}" 
                                                    {{ (is_array(old('stages')) && in_array($stage->id, old('stages'))) || (!old('stages') && true) ? 'checked' : '' }}
                                                    @if($loop->last) onclick="return false;" @endif>
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

                                <div class="mb-4">
                                    <label for="order_file" class="form-label fw-bold">Archivo de la Orden <span class="text-muted fw-normal small">(PDF, Opcional)</span></label>
                                    <div class="input-group custom-input-group">
                                        <span class="input-group-text bg-white border-end-0 text-muted"><i class="bi bi-file-earmark-pdf-fill"></i></span>
                                        <input type="file" name="order_file" id="order_file" class="form-control border-start-0 @error('order_file') is-invalid @enderror" accept="application/pdf">
                                    </div>
                                    @error('order_file')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
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
        input:-webkit-autofill,
textarea:-webkit-autofill,
select:-webkit-autofill {
    -webkit-box-shadow: 0 0 0 1000px #fff inset !important;
    -webkit-text-fill-color: #212529 !important;
}
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
            transition: all 0.2s ease;
            background-color: #fff;
            position: relative; /* Ensure dropdown displays correctly */
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
        .custom-input-group > :first-child {
            border-top-left-radius: 0.4rem;
            border-bottom-left-radius: 0.4rem;
        }
        .custom-input-group > :last-child {
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
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }


        .form-switch .form-check-input {
        background-size: 1rem 1rem;
        background-position: left center;
    }

    .form-switch .form-check-input:checked {
        background-position: right center;
    }
     
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var settings = {
                valueField: 'id',
                labelField: 'name',
                searchField: ['name', 'document'],
                create: false,
                placeholder: 'Escriba nombre o documento del cliente',
                allowEmptyOption: false,
                loadThrottle: 300, // Debounce 300ms
                load: function(query, callback) {
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
                    option: function(item, escape) {
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
                    item: function(item, escape) {
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
