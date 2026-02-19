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
                                <input type="text" name="search"
                                    class="form-control border-0 py-2 bg-transparent shadow-none"
                                    placeholder="Nombre o documento..." value="{{ request('search') }}">
                            </div>
                        </div>
                        <div class="col-lg-5 col-md-6">
                            <label class="small text-muted mb-2 text-uppercase font-weight-bold">Rango de Fecha</label>
                            <div class="d-flex gap-2">
                                <select name="date_range" class="form-select border shadow-sm rounded-pill px-3"
                                    onchange="toggleCustomDates(this.value)">
                                    <option value="7" {{ $dateRange == '7' ? 'selected' : '' }}>Últimos 7 días</option>
                                    <option value="30" {{ $dateRange == '30' ? 'selected' : '' }}>Últimos 30 días</option>
                                    <option value="60" {{ $dateRange == '60' ? 'selected' : '' }}>Últimos 60 días</option>
                                    <option value="90" {{ $dateRange == '90' ? 'selected' : '' }}>Últimos 90 días</option>
                                    <option value="custom" {{ $dateRange == 'custom' ? 'selected' : '' }}>Rango
                                        Personalizado</option>
                                </select>
                                <div id="customDateInputs"
                                    class="d-flex gap-2 {{ $dateRange != 'custom' ? 'd-none' : '' }}">
                                    <input type="date" name="date_from" class="form-control shadow-sm rounded-pill"
                                        value="{{ $dateFrom->format('Y-m-d') }}">
                                    <input type="date" name="date_to" class="form-control shadow-sm rounded-pill"
                                        value="{{ $dateTo->format('Y-m-d') }}">
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-12 text-lg-end">
                            <button type="submit" class="btn btn-primary px-4 rounded-pill shadow-sm">
                                <i class="bi bi-funnel me-1"></i> Filtrar
                            </button>
                            <a href="{{ route('performance.index') }}"
                                class="btn btn-outline-secondary rounded-pill px-3 ms-2">Limpiar</a>
                        </div>
                    </div>
                </form>
            </div>

            {{-- Benchmarking Section (Manager Insights) --}}
            @if(count($benchmarking) > 0)
                <h5 class="mb-3 font-weight-bold text-muted text-uppercase small">Benchmarking por Etapa</h5>
                <div class="row mb-4">php
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
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                {{ $bench->fastest->time_human }} /etapa</div>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-danger small mb-1">
                                            <i class="bi bi-hourglass-split me-1"></i>Más Lento
                                        </div>
                                        <div class="ps-3 border-start border-danger border-2">
                                            <div class="font-weight-bold small">{{ $bench->slowest->user->name }}</div>
                                            <div class="text-muted" style="font-size: 0.75rem;">
                                                {{ $bench->slowest->time_human }} /etapa</div>
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
                        Mostrando {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} de
                        {{ $users->total() }} empleados
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
                                                    <div class="text-muted small">{{ $user->document }} •
                                                        {{ $user->role->name }}</div>
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
                                            <button type="button"
                                                class="btn btn-sm btn-outline-primary rounded-pill px-3 shadow-sm"
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body p-0">
                    <div id="modalLoading" class="text-center py-5">
                        <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                            <span class="visually-hidden">Cargando...</span>
                        </div>
                        <p class="text-muted mt-3 small text-uppercase font-weight-bold">Obteniendo datos de
                            desempeño...</p>
                    </div>
                    <div id="modalContent" class="d-none">
                        <div class="bg-light p-3 border-bottom shadow-sm">
                            <div class="row align-items-center g-2">
                                <div class="col-sm-7">
                                    <div class="d-flex align-items-center">
                                        <label for="modalStageFilter" class="small text-muted text-uppercase font-weight-bold me-2 text-nowrap">Filtrar:</label>
                                        <select id="modalStageFilter" class="form-select form-select-sm shadow-sm border-0" onchange="loadModalData(1)">
                                            <option value="all">Todas las Etapas</option>
                                            @foreach($stagesList as $stage)
                                                <option value="{{ $stage->id }}">{{ $stage->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-sm-5 text-end">
                                    <div class="bg-white rounded-pill px-3 py-1 d-inline-block shadow-sm border">
                                        <small class="text-muted text-uppercase font-weight-bold me-2" style="font-size: 0.7rem;">Total:</small>
                                        <span class="h6 mb-0 font-weight-bold text-primary" id="modalSummaryStages">0</span>
                                    </div>
                                </div>
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

            // Reset filter
            if(document.getElementById('modalStageFilter')) {
                document.getElementById('modalStageFilter').value = 'all';
            }

            // Clear and show loading
            document.getElementById('modalLoading').classList.remove('d-none');
            document.getElementById('modalContent').classList.add('d-none');

            loadModalData(1);
        }

        function loadModalData(page = 1) {
            const currentUrlParams = new URLSearchParams(window.location.search);
            currentUrlParams.set('page', page);

            // Add Stage Filter
            const stageFilter = document.getElementById('modalStageFilter');
            if (stageFilter) {
                currentUrlParams.set('stage_id', stageFilter.value);
            }

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
                tbody.innerHTML = '<tr><td colspan="5" class="py-5 text-center text-muted small">Sin actividad registrada con los filtros actuales.</td></tr>';
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

            if (data.pagination.total > 0) {
                const from = (data.pagination.current_page - 1) * 15 + 1;
                const to = Math.min(data.pagination.current_page * 15, data.pagination.total);
                
                let controls = '';
                
                // Previous Page
                if (data.pagination.current_page === 1) {
                    controls += `
                        <li class="page-item disabled" aria-disabled="true" aria-label="Anterior">
                            <span class="page-link" aria-hidden="true">&lsaquo;</span>
                        </li>`;
                } else {
                    controls += `
                        <li class="page-item">
                            <button class="page-link" onclick="loadModalData(${data.pagination.current_page - 1})" rel="prev" aria-label="Anterior">&lsaquo;</button>
                        </li>`;
                }
                
                // Next Page
                if (data.pagination.current_page === data.pagination.last_page) {
                    controls += `
                        <li class="page-item disabled" aria-disabled="true" aria-label="Siguiente">
                            <span class="page-link" aria-hidden="true">&rsaquo;</span>
                        </li>`;
                } else {
                    controls += `
                        <li class="page-item">
                            <button class="page-link" onclick="loadModalData(${data.pagination.current_page + 1})" rel="next" aria-label="Siguiente">&rsaquo;</button>
                        </li>`;
                }

                // Numeric Links Logic
                let numericLinks = '';
                const lastPage = data.pagination.last_page;
                const currentPage = data.pagination.current_page;
                let pages = [];

                if (lastPage <= 10) {
                    for (let i = 1; i <= lastPage; i++) pages.push(i);
                } else {
                    pages.push(1);
                    pages.push(2);

                    let start = Math.max(3, currentPage - 2);
                    let end = Math.min(lastPage - 2, currentPage + 2);

                    if (start > 3) {
                        pages.push('...');
                    }

                    for (let i = start; i <= end; i++) {
                        pages.push(i);
                    }

                    if (end < lastPage - 2) {
                        pages.push('...');
                    }

                    if (start <= lastPage - 2) { 
                         // Ensure we don't duplicate if overlap (strictly though standard algo usually separates)
                         // Actually, my simple push logic above might double add if ranges overlap awkwardly? 
                         // With start=3, end=whatever, and pushing 1,2 and last-1,last.
                         // Overlap happens if lastPage is small, but I handle <= 10 case separately.
                         // So duplicates shouldn't happen for > 10.
                    }
                    
                    pages.push(lastPage - 1);
                    pages.push(lastPage);
                    
                    // Filter duplicates and sort just to be safe from logic edge cases?
                    // No, let's trust the logic for > 10.
                    // But wait, if start=3, we push 1, 2. then ... then 3. Safe.
                    // If end=lastPage-2. push ..., then lastPage-1. Safe.
                }
                
                // Generate HTML for pages
                pages.forEach(page => {
                    if (page === '...') {
                         numericLinks += `<li class="page-item disabled" aria-disabled="true"><span class="page-link">...</span></li>`;
                    } else {
                        if (page === currentPage) {
                            numericLinks += `<li class="page-item active" aria-current="page"><span class="page-link">${page}</span></li>`;
                        } else {
                            numericLinks += `<li class="page-item"><button class="page-link" onclick="loadModalData(${page})">${page}</button></li>`;
                        }
                    }
                });
                
                // Assemble Controls: Prev + Numbers + Next
                // We need to inject numericLinks between Prev and Next.
                // Re-assembling controls variable to include numericLinks in the middle.
                
                let finalControls = '';
                
                // Previous
                if (data.pagination.current_page === 1) {
                    finalControls += `
                        <li class="page-item disabled" aria-disabled="true" aria-label="Anterior">
                            <span class="page-link" aria-hidden="true">&lsaquo;</span>
                        </li>`;
                } else {
                    finalControls += `
                        <li class="page-item">
                            <button class="page-link" onclick="loadModalData(${data.pagination.current_page - 1})" rel="prev" aria-label="Anterior">&lsaquo;</button>
                        </li>`;
                }
                
                finalControls += numericLinks;
                
                // Next
                if (data.pagination.current_page === data.pagination.last_page) {
                    finalControls += `
                        <li class="page-item disabled" aria-disabled="true" aria-label="Siguiente">
                            <span class="page-link" aria-hidden="true">&rsaquo;</span>
                        </li>`;
                } else {
                    finalControls += `
                        <li class="page-item">
                            <button class="page-link" onclick="loadModalData(${data.pagination.current_page + 1})" rel="next" aria-label="Siguiente">&rsaquo;</button>
                        </li>`;
                }

                pagin.innerHTML = `
                    <div class="d-flex justify-content-between align-items-center w-100 flex-wrap">
                        <div class="mb-2 mb-md-0">
                            <p class="small text-muted mb-0">
                                Mostrando <span class="fw-semibold">${from}</span> a <span class="fw-semibold">${to}</span> de <span class="fw-semibold">${data.pagination.total}</span> resultados
                            </p>
                        </div>
                        <div>
                            <ul class="pagination pagination-sm mb-0">
                                ${finalControls}
                            </ul>
                        </div>
                    </div>
                `;
            } else {
                pagin.innerHTML = '';
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