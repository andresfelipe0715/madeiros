<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">
                {{ __('Rendimiento de Empleados') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-fluid px-5">
            
            {{-- Filter Bar --}}
            <div class="bg-white p-4 rounded shadow-sm mb-4 border-0">
                <form action="{{ route('performance.index') }}" method="GET" id="filterForm">
                    <div class="row g-3 align-items-end">
                        <div class="col-lg-4 col-md-6">
                            <label class="small text-muted mb-2 text-uppercase font-weight-bold">Búsqueda</label>
                            <div class="input-group shadow-sm border rounded-pill overflow-hidden bg-light search-pill">
                                <span class="input-group-text bg-transparent border-0 ps-3">
                                    <i class="bi bi-search text-muted"></i>
                                </span>
                                <input type="text" name="search" class="form-control border-0 py-2 bg-transparent shadow-none" 
                                    placeholder="Nombre o documento..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-lg-5 col-md-6">
                            <label class="small text-muted mb-2 text-uppercase font-weight-bold">Rango de Fecha</label>
                            <div class="d-flex gap-2">
                                <select name="date_range" class="form-select border shadow-sm rounded-pill px-3" onchange="toggleCustomDates(this.value)">
                                    <option value="7" {{ $dateRange == '7' ? 'selected' : '' }}>Últimos 7 días</option>
                                    <option value="30" {{ $dateRange == '30' ? 'selected' : '' }}>Últimos 30 días</option>
                                    <option value="60" {{ $dateRange == '60' ? 'selected' : '' }}>Últimos 60 días</option>
                                    <option value="90" {{ $dateRange == '90' ? 'selected' : '' }}>Últimos 90 días</option>
                                    <option value="custom" {{ $dateRange == 'custom' ? 'selected' : '' }}>Rango Personalizado</option>
                                </select>
                                <div id="customDateInputs" class="d-flex gap-2 {{ $dateRange != 'custom' ? 'd-none' : '' }}">
                                    <input type="date" name="date_from" class="form-control shadow-sm rounded-pill" value="{{ $dateFrom->format('Y-m-d') }}">
                                    <input type="date" name="date_to" class="form-control shadow-sm rounded-pill" value="{{ $dateTo->format('Y-m-d') }}">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-12 text-lg-end">
                            <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm">
                                <i class="bi bi-funnel me-1"></i> Filtrar
                            </button>
                            <a href="{{ route('performance.index') }}" class="btn btn-outline-secondary rounded-pill px-3 ms-2">Limpiar</a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Benchmarking Section (Manager Insights) --}}
            @if(count($benchmarking) > 0)
                <h5 class="mb-3 font-weight-bold text-muted text-uppercase small">Benchmarking por Etapa</h5>
                <div class="row mb-4">
                    @foreach($benchmarking as $bench)
                        <div class="col-xl-3 col-lg-4 col-md-6 mb-3">
                            <div class="card border-0 shadow-sm h-100 overflow-hidden">
                                <div class="card-header bg-dark text-white border-0 py-2">
                                    <span class="small font-weight-bold">{{ $bench->stage_name }}</span>
                                </div>
                                <div class="card-body py-3">
                                    <div class="d-flex justify-content-between mb-2 pb-2 border-bottom border-light">
                                        <span class="text-muted small">Promedio Global:</span>
                                        <span class="font-weight-bold text-primary small">{{ $bench->avg_all_human }}</span>
                                    </div>
                                    <div class="mb-2">
                                        <div class="text-success small mb-1">
                                            <i class="bi bi-lightning-fill me-1"></i>Más Rápido
                                        </div>
                                        <div class="ps-3 border-start border-success border-2">
                                            <div class="font-weight-bold small">{{ $bench->fastest->user->name }}</div>
                                            <div class="text-muted" style="font-size: 0.75rem;">{{ $bench->fastest->time_human }} /etapa</div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-danger small mb-1">
                                            <i class="bi bi-hourglass-split me-1"></i>Más Lento
                                        </div>
                                        <div class="ps-3 border-start border-danger border-2">
                                            <div class="font-weight-bold small">{{ $bench->slowest->user->name }}</div>
                                            <div class="text-muted" style="font-size: 0.75rem;">{{ $bench->slowest->time_human }} /etapa</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Main Employee Table --}}
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 font-weight-bold h6 text-uppercase text-muted">Resultados de Productividad</h5>
                    <div class="text-muted small">
                        Mostrando {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} de {{ $users->total() }} empleados
                    </div>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light text-muted text-uppercase small font-weight-bold">
                                <tr>
                                    <th class="px-4 py-3">Empleado</th>
                                    <th class="px-4 py-3 text-center">Órdenes</th>
                                    <th class="px-4 py-3 text-center">Etapas</th>
                                    <th class="px-4 py-3 text-center">Tiempo Total</th>
                                    <th class="px-4 py-3 text-center">Promedio / Etapa</th>
                                    <th class="px-4 py-3 text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="align-middle">
                                @forelse($users as $user)
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="d-flex align-items-center">
                                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3 shadow-sm"
                                                    style="width: 38px; height: 38px; font-size: 0.85rem; font-weight: bold;">
                                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                                </div>
                                                <div>
                                                    <div class="font-weight-bold">{{ $user->name }}</div>
                                                    <div class="text-muted small">{{ $user->document }} • {{ $user->role->name }}</div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3 text-center font-weight-bold text-primary">
                                            {{ $user->performance_summary->unique_orders }}
                                        </td>
                                        <td class="px-4 py-3 text-center font-weight-bold">
                                            {{ $user->performance_summary->total_stages }}
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="badge bg-dark-subtle text-dark border px-2 py-1">
                                                {{ $user->performance_summary->total_time_human }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <span class="badge bg-info-subtle text-info border px-2 py-1">
                                                {{ $user->performance_summary->avg_time_human }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm"
                                                onclick="showDetails({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                                data-bs-toggle="modal" data-bs-target="#performanceDetailModal">
                                                <i class="bi bi-list-ul me-1"></i> Detalles
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-5 text-center text-muted">
                                            <i class="bi bi-info-circle me-1 h4 d-block mb-3"></i>
                                            No se encontraron empleados con actividad en este rango.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($users->hasPages())
                    <div class="card-footer bg-white border-top py-3">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Single Performance Detail Modal --}}
    <div class="modal fade" id="performanceDetailModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white border-0 py-3">
                    <h5 class="modal-title font-weight-bold" id="modalTitle">Historial de Rendimiento</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="modalLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="text-muted mt-3 small text-uppercase font-weight-bold">Obteniendo datos de desempeño...</p>
                    </div>
                    <div id="modalContent" class="d-none">
                        <div class="bg-light p-3 d-flex justify-content-around text-center border-bottom shadow-sm">
                            <div class="flex-fill">
                                <small class="text-muted text-uppercase d-block mb-1 font-weight-bold" style="font-size: 0.65rem;">Total Etapas</small>
                                <span class="h5 mb-0 font-weight-bold text-dark" id="modalSummaryStages">0</span>
                            </div>
                        </div>
                        <div class="table-responsive" style="max-height: 450px;">
                            <table class="table table-sm table-striped table-hover mb-0 align-middle">
                                <thead class="bg-white sticky-top shadow-sm">
                                    <tr>
                                        <th class="px-3 py-3 border-0 small text-uppercase text-muted">Orden #</th>
                                        <th class="px-3 py-3 border-0 small text-uppercase text-muted">Etapa</th>
                                        <th class="px-3 py-3 border-0 small text-uppercase text-muted">Inicio</th>
                                        <th class="px-3 py-3 border-0 small text-uppercase text-muted">Fin</th>
                                        <th class="px-3 py-3 border-0 small text-uppercase text-muted text-end">Duración</th>
                                    </tr>
                                </thead>
                                <tbody id="modalTableBody">
                                    {{-- Loaded via JS --}}
                                </tbody>
                            </table>
                        </div>
                        <div class="p-3 border-top d-flex justify-content-between align-items-center bg-white" id="modalPagination">
                            {{-- Pagination controls --}}
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 bg-light">
                    <button type="button" class="btn btn-secondary rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .search-pill:focus-within {
            border-color: #0d6efd !important;
            background-color: #fff !important;
        }
        .search-pill {
            transition: all 0.2s ease;
        }
        .sticky-top {
            z-index: 10;
        }
        .card {
            transition: transform 0.2s ease;
        }
        .card:hover {
            transform: translateY(-2px);
        }
    </style>

    <script>
        let currentUserId = null;
        
        function showDetails(userId, userName) {
            currentUserId = userId;
            document.getElementById('modalTitle').innerText = 'Historial de Rendimiento: ' + userName;
            
            // Clear and show loading
            document.getElementById('modalLoading').classList.remove('d-none');
            document.getElementById('modalContent').classList.add('d-none');
            
            loadModalData(1);
        }

        function loadModalData(page = 1) {
            const currentUrlParams = new URLSearchParams(window.location.search);
            currentUrlParams.set('page', page);
            
            fetch(`/performance/details/${currentUserId}?${currentUrlParams.toString()}`)
                .then(response => response.json())
                .then(data => {
                    renderModalData(data);
                })
                .catch(error => {
                    console.error('Error loading performance details:', error);
                    alert('Error al cargar los detalles. Intente de nuevo.');
                });
        }

        function renderModalData(data) {
            const tbody = document.getElementById('modalTableBody');
            tbody.innerHTML = '';
            
            if (data.data.length === 0) {
                tbody.innerHTML = '<tr><td colspan="5" class="py-5 text-center text-muted small">Sin actividad registrada en este periodo.</td></tr>';
            } else {
                data.data.forEach(item => {
                    tbody.innerHTML += `
                        <tr>
                            <td class="px-3 py-2 font-weight-bold">#${item.order_invoice}</td>
                            <td class="px-3 py-2"><span class="badge bg-secondary-subtle text-secondary border small">${item.stage_name}</span></td>
                            <td class="px-3 py-2 small text-muted">${item.started_at}</td>
                            <td class="px-3 py-2 small text-muted">${item.completed_at}</td>
                            <td class="px-3 py-2 text-end text-nowrap">
                                <span class="text-primary small font-weight-bold">
                                    ${item.duration_human}
                                </span>
                            </td>
                        </tr>
                    `;
                });
            }
            
            document.getElementById('modalSummaryStages').innerText = data.summary.total_stages;
            
            // Pagination
            const pagin = document.getElementById('modalPagination');
            pagin.innerHTML = '';
            
            if (data.pagination.last_page > 1) {
                pagin.innerHTML = `
                    <span class="small text-muted font-weight-bold">Página ${data.pagination.current_page} de ${data.pagination.last_page}</span>
                    <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                        <button class="btn btn-sm btn-outline-primary px-3" ${data.pagination.current_page === 1 ? 'disabled' : ''} onclick="loadModalData(${data.pagination.current_page - 1})">
                            <i class="bi bi-chevron-left"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-primary px-3" ${data.pagination.current_page === data.pagination.last_page ? 'disabled' : ''} onclick="loadModalData(${data.pagination.current_page + 1})">
                            <i class="bi bi-chevron-right"></i>
                        </button>
                    </div>
                `;
            } else {
                pagin.innerHTML = `<span class="small text-muted">${data.pagination.total} registros encontrados</span>`;
            }
            
            document.getElementById('modalLoading').classList.add('d-none');
            document.getElementById('modalContent').classList.remove('d-none');
        }

        function toggleCustomDates(value) {
            const inputs = document.getElementById('customDateInputs');
            if (value === 'custom') {
                inputs.classList.remove('d-none');
            } else {
                inputs.classList.add('d-none');
                document.getElementById('filterForm').submit();
            }
        }
    </script>
</x-app-layout>