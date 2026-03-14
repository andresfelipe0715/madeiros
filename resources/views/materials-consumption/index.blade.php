<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Consumo de Materiales') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <!-- Filters -->
                <form action="{{ route('materials.consumption') }}" method="GET" class="mb-8">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label for="month" class="form-label small fw-bold text-muted">{{ __('Mes') }}</label>
                            <select name="month" id="month" class="form-select border-gray-300 rounded-md shadow-sm">
                                @foreach(range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="year" class="form-label small fw-bold text-muted">{{ __('Año') }}</label>
                            <select name="year" id="year" class="form-select border-gray-300 rounded-md shadow-sm">
                                @foreach(range(now()->year - 2, now()->year + 1) as $y)
                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                        {{ $y }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="material_id"
                                class="form-label small fw-bold text-muted">{{ __('Material (Opcional)') }}</label>
                            <select name="material_id" id="material_id"
                                class="form-select border-gray-300 rounded-md shadow-sm"
                                placeholder="{{ __('Buscar material...') }}">
                                <option value="">{{ __('Todos los materiales') }}</option>
                                @if($selectedMaterial)
                                    <option value="{{ $selectedMaterial->id }}" selected>
                                        {{ $selectedMaterial->name }}
                                    </option>
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100 shadow-sm border-0"
                                style="background-color: #0d6efd;">
                                <i class="bi bi-filter me-1"></i> {{ __('Filtrar') }}
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Month Navigation -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h3 class="h4 mb-0 fw-bold text-capitalize">
                        {{ $startDate->translatedFormat('F Y') }}
                    </h3>
                    <div class="btn-group">
                        <a href="{{ route('materials.consumption', ['month' => $prevMonth->month, 'year' => $prevMonth->year, 'material_id' => $materialId]) }}"
                            class="btn btn-outline-secondary btn-sm shadow-sm">
                            <i class="bi bi-chevron-left"></i> {{ __('Anterior') }}
                        </a>
                        <a href="{{ route('materials.consumption', ['month' => now()->month, 'year' => now()->year, 'material_id' => $materialId]) }}"
                            class="btn btn-outline-secondary btn-sm shadow-sm">
                            {{ __('Hoy') }}
                        </a>
                        <a href="{{ route('materials.consumption', ['month' => $nextMonth->month, 'year' => $nextMonth->year, 'material_id' => $materialId]) }}"
                            class="btn btn-outline-secondary btn-sm shadow-sm">
                            {{ __('Siguiente') }} <i class="bi bi-chevron-right"></i>
                        </a>
                    </div>
                </div>

                <!-- Calendar View -->
                <div class="calendar-container border rounded overflow-hidden shadow-sm">
                    <div class="row g-0 fw-bold text-center bg-gray-50 border-bottom">
                        @foreach(['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'] as $dayName)
                            <div class="col py-2 border-end small text-muted">{{ $dayName }}</div>
                        @endforeach
                    </div>

                    @php
                        $daysInMonth = $startDate->daysInMonth;
                        $firstDayOfWeek = $startDate->dayOfWeek; // 0 (Sun) to 6 (Sat)
                        $currentDay = 1;
                        $totalCells = ceil(($daysInMonth + $firstDayOfWeek) / 7) * 7;
                    @endphp

                    @for ($i = 0; $i < $totalCells; $i++)
                        @if ($i % 7 == 0)
                            <div class="row g-0 border-bottom" style="min-height: 120px;">
                        @endif

                            <div class="col border-end p-2 day-cell {{ ($i < $firstDayOfWeek || $currentDay > $daysInMonth) ? 'bg-light' : 'cursor-pointer hover-bg-light' }}"
                                style="width: 14.28%; transition: background-color 0.2s;"
                                @if (!($i < $firstDayOfWeek || $currentDay > $daysInMonth))
                                    onclick="showConsumptionModal('{{ $startDate->copy()->day($currentDay)->translatedFormat('d F Y') }}', '{{ $startDate->copy()->day($currentDay)->format('Y-m-d') }}')"
                                @endif>
                                @if ($i >= $firstDayOfWeek && $currentDay <= $daysInMonth)
                                    @php
                                        $dateString = $startDate->copy()->day($currentDay)->format('Y-m-d');
                                        $hasConsumption = isset($dailyData[$dateString]);
                                        $isToday = $dateString === now()->format('Y-m-d');
                                    @endphp

                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <span
                                            class="badge {{ $isToday ? 'bg-primary shadow-sm' : 'text-muted' }} rounded-circle p-1"
                                            style="width: 24px; height: 24px;">
                                            {{ $currentDay }}
                                        </span>
                                    </div>

                                    @if ($hasConsumption)
                                        <div class="consumption-list d-flex flex-column gap-1 overflow-auto"
                                            style="max-height: 90px;">
                                            @foreach($dailyData[$dateString] as $materialId => $data)
                                                <div class="bg-blue-50 border-start border-4 border-primary p-1 rounded-end mb-1"
                                                    style="font-size: 0.75rem;">
                                                    <div class="fw-bold text-primary text-truncate"
                                                        title="{{ $data['material_name'] }}">
                                                        {{ $data['material_name'] }}
                                                    </div>
                                                    <div class="text-muted d-flex justify-content-between">
                                                        <span>{{ (float) $data['total_actual_quantity'] }}</span>
                                                        <span class="fw-bold">{{ count($data['orders']) }}
                                                            {{ count($data['orders']) === 1 ? 'ped.' : 'peds.' }}</span>
                                                    </div>
                                                    <div class="mt-1 d-flex flex-wrap gap-1">
                                                        @foreach($data['orders'] as $order)
                                                            <a href="{{ route('orders.edit', $order['id']) }}"
                                                                class="badge bg-white text-primary border border-primary-subtle text-decoration-none"
                                                                style="font-size: 0.65rem;">
                                                                #{{ $order['invoice_number'] }}
                                                            </a>
                                                        @endforeach
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif

                                    @php $currentDay++; @endphp
                                @endif
                            </div>

                            @if ($i % 7 == 6)
                                </div>
                            @endif
                    @endfor
                </div>

                <div class="mt-4 p-3 bg-light rounded shadow-sm small text-muted border">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-info-circle me-2 text-primary"></i>
                        <span>{{ __('Este calendario muestra el consumo real de materiales registrado al finalizar la etapa de Corte.') }}</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Consumption Details Modal -->
    <div class="modal fade" id="consumptionModal" tabindex="-1" aria-labelledby="consumptionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-primary text-white border-0 py-3">
                    <h5 class="modal-title fw-bold" id="consumptionModalLabel">
                        <i class="bi bi-calendar-check me-2"></i>
                        {{ __('Consumo Detallado') }}: <span id="modalDateDisplay"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 consumption-list" id="modalConsumptionBody" style="max-height: 60vh; overflow-y: auto;">
                    <div class="text-center py-5 text-muted">
                        {{ __('No hay consumos registrados para este día.') }}
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-secondary shadow-sm" data-bs-dismiss="modal">{{ __('Cerrar') }}</button>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .calendar-container {
                border-left: 1px solid #dee2e6;
            }

            .col.border-end {
                border-right: 1px solid #dee2e6 !important;
            }

            .row.border-bottom {
                border-bottom: 2px solid #f8f9fa !important;
            }

            .bg-blue-50 {
                background-color: #f0f7ff;
            }

            .border-primary {
                border-color: #0d6efd !important;
            }

            .cursor-pointer {
                cursor: pointer;
            }

            .hover-bg-light:hover {
                background-color: #f8f9fa !important;
            }

            .consumption-list::-webkit-scrollbar {
                width: 4px;
            }

            .consumption-list::-webkit-scrollbar-track {
                background: #f1f1f1;
            }

            .consumption-list::-webkit-scrollbar-thumb {
                background: #cbd5e0;
                border-radius: 4px;
            }

            .consumption-list::-webkit-scrollbar-thumb:hover {
                background: #a0aec0;
            }

            /* TomSelect Customizations to match standard inputs */
            .ts-wrapper .ts-control {
                border-color: #dee2e6;
                border-radius: 0.375rem;
                padding: 0.375rem 0.75rem;
                min-height: 38px;
            }

            .ts-wrapper.focus .ts-control {
                border-color: #86b7fe;
                box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
            }

            .ts-dropdown {
                border-radius: 0.375rem;
                margin-top: 5px;
                box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            }
        </style>
    @endpush

    <script>
        // Data for the modal
        const dailyConsumptionData = @json($dailyData);

        function showConsumptionModal(formattedDate, dateString) {
            document.getElementById('modalDateDisplay').innerText = formattedDate;
            const container = document.getElementById('modalConsumptionBody');
            
            if (!dailyConsumptionData[dateString]) {
                container.innerHTML = '<div class="text-center py-5 text-muted"><i class="bi bi-info-circle h2 d-block mb-3"></i>{{ __("No hay consumos registrados para este día.") }}</div>';
            } else {
                let html = '<div class="list-group list-group-flush border rounded shadow-sm">';
                
                Object.entries(dailyConsumptionData[dateString]).forEach(([materialId, data]) => {
                    html += `
                        <div class="list-group-item p-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h4 class="h5 mb-0 fw-bold text-primary">${data.material_name}</h4>
                                <span class="badge bg-primary rounded-pill px-3 py-2 fs-6">
                                    ${parseFloat(data.total_actual_quantity).toLocaleString()} unidades
                                </span>
                            </div>
                            
                            <div class="mt-3">
                                <p class="small fw-bold text-muted mb-2 text-uppercase letter-spacing-1">
                                    <i class="bi bi-journal-text me-1"></i> {{ __('Órdenes Asociadas') }}
                                </p>
                                <div class="d-flex flex-wrap gap-2">
                    `;
                    
                    data.orders.forEach(order => {
                        const url = "{{ route('orders.edit', ':id') }}".replace(':id', order.id);
                        html += `
                            <a href="${url}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                <i class="bi bi-file-earmark-text me-1"></i> #${order.invoice_number}
                            </a>
                        `;
                    });
                    
                    html += `
                                </div>
                            </div>
                        </div>
                    `;
                });
                
                html += '</div>';
                container.innerHTML = html;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('consumptionModal'));
            modal.show();
        }

        document.addEventListener('DOMContentLoaded', function() {
            const materialSelect = document.getElementById('material_id');
            if (materialSelect) {
                new TomSelect(materialSelect, {
                    valueField: 'id',
                    labelField: 'name',
                    searchField: ['name'],
                    placeholder: '{{ __("Buscar material...") }}',
                    loadThrottle: 300,
                    plugins: ['virtual_scroll'],
                    shouldLoad: function(query) {
                        return query.length >= 2;
                    },
                    firstUrl: function(query) {
                        return '{{ route("materials.search") }}?q=' + encodeURIComponent(query);
                    },
                    load: function(query, callback) {
                        const url = this.getUrl(query);
                        if (!url) return callback();

                        fetch(url)
                            .then(response => response.json())
                            .then(json => {
                                this.setNextUrl(query, json.next_page_url);
                                callback(json.data);
                            }).catch(() => callback());
                    },
                    render: {
                        option: (item, escape) => `<div class="py-2 px-3 border-bottom">
                            <span class="d-block">${escape(item.name)}</span>
                            <small class="text-muted">Disponible: ${item.available_quantity}</small>
                        </div>`,
                        item: (item, escape) => `<div class="py-0">${escape(item.name)}</div>`
                    }
                });
            }
        });
    </script>
</x-app-layout>