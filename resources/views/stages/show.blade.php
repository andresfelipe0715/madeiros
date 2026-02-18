<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="{{ route('dashboard') }}" class="text-decoration-none">Panel</a></li>
                        <li class="breadcrumb-item active" aria-current="page">{{ $stage->name }}</li>
                    </ol>
                </nav>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight mb-0">
                    {{ $stage->name }}
                </h2>
            </div>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm rounded-pill">
                <i class="bi bi-arrow-left me-1"></i> Volver al Menú
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-fluid">
            @if(session('status'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                    {{ session('status') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="row">
                <div class="col-12">
                    @include('partials.stage-table', [
                        'stage' => $stage,
                        'orders' => $orders,
                        'authService' => $authService
                    ])
                </div>
            </div>
        </div>
    </div>

    <!-- Modals for Remitir -->
    @foreach($orders as $order)
        @php $orderStage = $order->orderStages->firstWhere('stage_id', $stage->id); @endphp
        @if($stage->can_remit)
            <div class="modal fade" id="remitirModal{{ $orderStage->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content border-0 shadow">
                        <form action="{{ route('order-stages.remit', $orderStage->id) }}" method="POST" novalidate>
                            @csrf
                            <div class="modal-header bg-danger text-white border-0">
                                <h5 class="modal-title">Remitir Pedido #{{ $order->id }}</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <p class="text-muted">¿A qué etapa desea devolver este pedido? Esta acción reiniciará el progreso desde la etapa seleccionada.</p>
                                <div class="mb-3">
                                    <label class="form-label font-weight-bold">Etapa Destino</label>
                                    <select name="target_stage_id" class="form-select shadow-none @error('target_stage_id') is-invalid @enderror" required>
                                        <option value="" selected disabled>Seleccione etapa...</option>
                                        @foreach($order->orderStages->where('sequence', '<', $orderStage->sequence)->sortBy('sequence') as $prevStage)
                                            <option value="{{ $prevStage->stage_id }}" {{ old('target_stage_id') == $prevStage->stage_id ? 'selected' : '' }}>{{ $prevStage->stage->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('target_stage_id')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="mb-0">
                                    <label class="form-label font-weight-bold">Motivo / Notas</label>
                                    <textarea name="notes" class="form-control shadow-none @error('notes') is-invalid @enderror" rows="3" placeholder="Indique el motivo por el cual se remite el pedido..." required>{{ old('notes') }}</textarea>
                                    @error('notes')
                                        <div class="invalid-feedback d-block">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="modal-footer border-0 p-4 pt-0">
                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-danger rounded-pill px-4">Confirmar Remisión</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach

@inject('visibility', 'App\Services\VisibilityService')
@php
    $permissions = $visibility::forUser(auth()->user());
@endphp

    <!-- Modals for Files -->
    @foreach($orders as $order)
        @php 
            $orderStage = $order->orderStages->firstWhere('stage_id', $stage->id);
            $orderFile = $order->orderFiles->first(fn($f) => str_contains(strtolower($f->fileType->name), 'archivo_orden'));
            $projectFile = $order->orderFiles->first(fn($f) => str_contains(strtolower($f->fileType->name), 'proyecto'));
            $machineFile = $order->orderFiles->first(fn($f) => str_contains(strtolower($f->fileType->name), 'máquina'));
        @endphp
        <div class="modal fade" id="filesModal{{ $orderStage->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-dark text-white border-0">
                        <h5 class="modal-title">Documentación - Pedido #{{ $order->id }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="list-group list-group-flush">
                            @if($orderFile && $permissions->canViewOrderFile())
                                <a href="{{ $orderFile->file_url }}" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-file-earmark-pdf fs-4 text-danger me-3"></i>
                                        <div>
                                            <h6 class="mb-0">Archivo de Orden</h6>
                                            <small class="text-muted">Documento PDF oficial</small>
                                        </div>
                                    </div>
                                    <i class="bi bi-box-arrow-up-right text-muted"></i>
                                </a>
                            @endif

                            @if($projectFile && $permissions->canViewFiles()) {{-- Project file falls under general files --}}
                                <a href="{{ $projectFile->file_url }}" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-file-earmark-image fs-4 text-info me-3"></i>
                                        <div>
                                            <h6 class="mb-0">Archivo de Proyecto</h6>
                                            <small class="text-muted">Diseño y despiece</small>
                                        </div>
                                    </div>
                                    <i class="bi bi-box-arrow-up-right text-muted"></i>
                                </a>
                            @endif

                            @if($machineFile && $permissions->canViewMachineFile())
                                <a href="{{ $machineFile->file_url }}" target="_blank" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-3">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-cpu fs-4 text-dark me-3"></i>
                                        <div>
                                            <h6 class="mb-0">Archivo Máquina</h6>
                                            <small class="text-muted">Para CNC / Dimensionadora</small>
                                        </div>
                                    </div>
                                    <i class="bi bi-box-arrow-up-right text-muted"></i>
                                </a>
                            @endif

                            @php
                                $visibleFiles = 0;
                                if ($orderFile && $permissions->canViewOrderFile()) $visibleFiles++;
                                if ($projectFile && $permissions->canViewFiles()) $visibleFiles++;
                                if ($machineFile && $permissions->canViewMachineFile()) $visibleFiles++;
                            @endphp

                            @if($visibleFiles === 0)
                                <div class="text-center py-4 text-muted">
                                    <i class="bi bi-folder-x display-4 opacity-50 mb-3 d-block"></i>
                                    No hay archivos accesibles para este pedido o no tienes permisos para verlos.
                                </div>
                            @endif
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <!-- Modals for Notes (Timeline Style) -->
    @foreach($orders as $order)
        @php 
            $orderStage = $order->orderStages->firstWhere('stage_id', $stage->id);
            $canEdit = $authService->canActOnStage(auth()->user(), $order, $stage->id);
            $orderRemitLogs = $remitLogs[$order->id] ?? collect();
        @endphp
        <div class="modal fade" id="notesModal{{ $orderStage->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content border-0 shadow">
                    <form action="{{ route('order-stages.update-notes', $orderStage->id) }}" method="POST">
                        @csrf
                        <div class="modal-header bg-primary text-white border-0">
                            <h5 class="modal-title">Traceabilidad y Notas - Pedido #{{ $order->id }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 bg-light">
                            <div class="timeline-container">
                                <!-- 1. General Notes -->
                                <div class="timeline-item pb-4 position-relative">
                                    <div class="timeline-indicator bg-secondary position-absolute rounded-circle" style="left: -20px; width: 12px; height: 12px; top: 5px;"></div>
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body p-3">
                                            <h6 class="text-uppercase text-xs font-weight-bold text-muted mb-2">Observaciones Generales</h6>
                                            <p class="mb-0 text-dark small text-break">{{ $order->notes ?: 'Sin observaciones generales.' }}</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. Remit History -->
                                @foreach($orderRemitLogs as $log)
                                    @php 
                                        $data = $log->remit_data;
                                        $fromStageName = $stageNames[$data['from']] ?? 'Etapa anterior';
                                    @endphp
                                    <div class="timeline-item pb-4 position-relative">
                                        <div class="timeline-indicator bg-danger position-absolute rounded-circle" style="left: -20px; width: 12px; height: 12px; top: 5px;"></div>
                                        <div class="card border-0 shadow-sm border-left-danger" style="border-left: 3px solid #dc3545 !important;">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <h6 class="text-xs font-weight-bold text-danger mb-0 text-uppercase">Retorno de Producción</h6>
                                                    <small class="x-small text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</small>
                                                </div>
                                                <p class="mb-1 text-dark small"><span class="fw-bold">Desde:</span> {{ $fromStageName }}</p>
                                                <p class="mb-0 text-dark small text-break"><span class="fw-bold">Motivo:</span> {{ $data['reason'] }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach

                                <!-- 3. Pending Reasons -->
                                @if($orderStage->is_pending)
                                    <div class="timeline-item pb-4 position-relative">
                                        <div class="timeline-indicator bg-warning position-absolute rounded-circle" style="left: -20px; width: 12px; height: 12px; top: 5px;"></div>
                                        <div class="card border-0 shadow-sm border-left-warning" style="border-left: 3px solid #ffc107 !important;">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <h6 class="text-xs font-weight-bold text-warning mb-0 text-uppercase">Estado: Pendiente (Bloqueado)</h6>
                                                    <small class="x-small text-muted">{{ $orderStage->pending_marked_at?->format('d/m/Y H:i') ?? '' }}</small>
                                                </div>
                                                <p class="mb-0 text-dark small text-break"><span class="fw-bold">Razón:</span> {{ $orderStage->pending_reason }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <!-- 4. Current Stage Notes (Editable) -->
                                <div class="timeline-item position-relative">
                                    <div class="timeline-indicator bg-primary position-absolute rounded-circle" style="left: -20px; width: 12px; height: 12px; top: 5px;"></div>
                                    <div class="card border-0 shadow-sm border-left-primary" style="border-left: 3px solid #0d6efd !important;">
                                        <div class="card-body p-3">
                                            <h6 class="text-uppercase text-xs font-weight-bold text-primary mb-3">Notas de {{ $stage->name }}</h6>
                                            <textarea name="notes" 
                                                class="form-control shadow-none border-light bg-light-soft @if(!$canEdit) bg-light @endif" 
                                                rows="4" 
                                                placeholder="Agregar detalles sobre el progreso en esta etapa..."
                                                {{ !$canEdit ? 'readonly' : '' }}>{{ $orderStage->notes }}</textarea>
                                            @if($canEdit)
                                                <div class="mt-2 text-end">
                                                    <button type="submit" class="btn btn-primary btn-sm rounded-pill px-3">Actualizar notas</button>
                                                </div>
                                            @else
                                                <p class="x-small text-danger mt-2 mb-0"><i class="bi bi-lock-fill"></i> Lectura únicamente</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer border-0 p-4 pt-0">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    <style>
        .timeline-container {
            border-left: 2px dashed #dee2e6;
            margin-left: 25px;
            padding-left: 10px;
        }
        .bg-light-soft {
            background-color: #f8f9fa;
        }
        .border-left-danger { border-left-width: 4px !important; }
        .border-left-warning { border-left-width: 4px !important; }
        .border-left-primary { border-left-width: 4px !important; }
        .text-xs { font-size: 0.7rem; }
        .x-small { font-size: 0.75rem; }
        .breadcrumb-item + .breadcrumb-item::before { content: "/"; }
        .bg-danger { background-color: #dc3545 !important; }
        .btn-close-white { filter: invert(1) grayscale(100%) brightness(200%); }
        .font-weight-bold { font-weight: 600 !important; }
        .bg-soft-danger { background-color: rgba(220, 53, 69, 0.1); }
    </style>

    @if(session('failed_remit_id'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                var modalId = '#remitirModal' + '{{ session('failed_remit_id') }}';
                var modalElement = document.querySelector(modalId);
                if (modalElement) {
                    var modal = new bootstrap.Modal(modalElement);
                    modal.show();
                }
            });
        </script>
    @endif
</x-app-layout>
