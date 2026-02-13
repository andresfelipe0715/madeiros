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
        @if(strtolower($stage->name) !== 'entrega' && strtolower($stage->name) !== 'corte')
            <div class="modal fade" id="remitirModal{{ $orderStage->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content border-0 shadow">
                        <form action="{{ route('order-stages.remit', $orderStage->id) }}" method="POST">
                            @csrf
                            <div class="modal-header bg-danger text-white border-0">
                                <h5 class="modal-title">Remitir Pedido #{{ $order->id }}</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <p class="text-muted">¿A qué etapa desea devolver este pedido? Esta acción reiniciará el progreso desde la etapa seleccionada.</p>
                                <div class="mb-3">
                                    <label class="form-label font-weight-bold">Etapa Destino</label>
                                    <select name="target_stage_id" class="form-select shadow-none" required>
                                        <option value="" selected disabled>Seleccione etapa...</option>
                                        @foreach($order->orderStages->where('sequence', '<', $orderStage->sequence)->sortBy('sequence') as $prevStage)
                                            <option value="{{ $prevStage->stage_id }}">{{ $prevStage->stage->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-0">
                                    <label class="form-label font-weight-bold">Motivo / Notas</label>
                                    <textarea name="notes" class="form-control shadow-none" rows="3" placeholder="Indique el motivo por el cual se remite el pedido..."></textarea>
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

    <!-- Modals for Notes -->
    @foreach($orders as $order)
        @php 
            $orderStage = $order->orderStages->firstWhere('stage_id', $stage->id);
            $canEdit = $authService->canActOnStage(auth()->user(), $order, $stage->id);
        @endphp
        <div class="modal fade" id="notesModal{{ $orderStage->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow">
                    <form action="{{ route('order-stages.update-notes', $orderStage->id) }}" method="POST">
                        @csrf
                        <div class="modal-header bg-primary text-white border-0">
                            <h5 class="modal-title">Observaciones - Pedido #{{ $order->id }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4">
                            <!-- Section A: Observaciones Generales -->
                            <div class="mb-4">
                                <label class="form-label font-weight-bold text-muted small text-uppercase">Sección A: Observaciones Generales</label>
                                <div class="p-3 bg-light rounded border text-muted text-break">
                                    {{ $order->notes ?: 'Sin observaciones generales.' }}
                                </div>
                                <p class="x-small text-muted mt-1"><i class="bi bi-info-circle"></i> Estas notas fueron creadas al registrar la orden y son de solo lectura.</p>
                            </div>

                            @php
                                $orderRemitLogs = $remitLogs[$order->id] ?? collect();
                                $showRemit = $orderRemitLogs->isNotEmpty();
                            @endphp

                            @if($showRemit)
                                <div class="mb-4">
                                    <label class="form-label font-weight-bold text-danger small text-uppercase">Sección C: Motivos de Remisión</label>
                                    
                                    @foreach($orderRemitLogs as $index => $log)
                                        @php 
                                            $data = $log->remit_data;
                                            $fromStageName = $stageNames[$data['from']] ?? 'Desconocida';
                                        @endphp
                                        <div class="p-3 mb-2 bg-soft-danger rounded border border-danger text-danger text-break">
                                            <strong>{{ $index === 0 ? 'Causa de remisión' : 'Remisión anterior' }} (De {{ $fromStageName }}):</strong><br>
                                            {{ $data['reason'] }}
                                        </div>
                                    @endforeach
                                    <p class="x-small text-danger mt-1"><i class="bi bi-info-circle"></i> Estas son las razones por las cuales el pedido fue devuelto a esta etapa.</p>
                                </div>
                            @endif

                            <hr class="my-4 opacity-50">

                            <!-- Section B: Observaciones de esta Etapa -->
                            <div class="mb-0">
                                <label for="notes_{{ $orderStage->id }}" class="form-label font-weight-bold text-primary small text-uppercase">Sección B: Observaciones de {{ $stage->name }}</label>
                                <textarea name="notes" id="notes_{{ $orderStage->id }}" 
                                    class="form-control shadow-none @if(!$canEdit) bg-light @endif" 
                                    rows="4" 
                                    placeholder="Ingrese observaciones específicas para esta etapa..."
                                    {{ !$canEdit ? 'readonly' : '' }}>{{ $orderStage->notes }}</textarea>
                                @if($canEdit)
                                    <p class="x-small text-muted mt-1"><i class="bi bi-pencil"></i> Solo tú y otros usuarios de esta etapa pueden editar estas notas.</p>
                                @else
                                    <p class="x-small text-danger mt-1"><i class="bi bi-lock-fill"></i> No tienes permisos para editar las notas de esta etapa.</p>
                                @endif
                            </div>
                        </div>
                        <div class="modal-footer border-0 p-4 pt-0">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                            @if($canEdit)
                                <button type="submit" class="btn btn-primary rounded-pill px-4">Guardar Cambios</button>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

    <style>
        .x-small {
            font-size: 0.75rem;
        }
        .breadcrumb-item + .breadcrumb-item::before {
            content: "/";
        }
        .bg-danger {
            background-color: #dc3545 !important;
        }
        .btn-close-white {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
        .font-weight-bold {
            font-weight: 600 !important;
        }
        .bg-soft-danger {
            background-color: rgba(220, 53, 69, 0.1);
        }
    </style>
</x-app-layout>
