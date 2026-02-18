@inject('visibility', 'App\Services\VisibilityService')
@php
    $stageName = $stage->name;
    $isAdmin = auth()->user()->hasRole('Admin');
    $permissions = $visibility::forUser(auth()->user());
@endphp

<div class="card mb-5 border-0 shadow-sm overflow-hidden">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-end">
        <div>
            <span class="text-uppercase text-xs font-bold text-primary tracking-wider mb-1 d-block opacity-75">Módulo de
                Producción</span>
            <h4 class="mb-0 font-weight-bolder">{{ $stageName }}</h4>
        </div>
        <div class="text-end">
            <span class="badge bg-soft-primary text-primary rounded-pill">
                {{ $orders->count() }} de {{ $orders->total() }} pedidos en cola
            </span>
        </div>
    </div>
    <div class="card-body px-0 pt-3 pb-2">
        <div class="table-responsive">
            <table class="table align-items-center mb-0">
                <thead>
                    <tr class="bg-light text-secondary opacity-7 text-uppercase text-xs font-weight-bolder">
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Material</th>
                        <th>Herrajería</th>
                        <th>Manual</th>
                        <th>Archivos</th>
                        <th>Fecha Envío</th>
                        <th>Estado</th>
                        <th>Observaciones</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        @php
                            $orderStage = $order->orderStages->firstWhere('stage_id', $stage->id);
                            $orderFile = $order->orderFiles->first(fn($f) => str_contains(strtolower($f->fileType->name), 'archivo_orden'));
                            $projectFile = $order->orderFiles->first(fn($f) => str_contains(strtolower($f->fileType->name), 'proyecto'));
                            $machineFile = $order->orderFiles->first(fn($f) => str_contains(strtolower($f->fileType->name), 'máquina'));
                            
                            $isNext = $authService->isNextInQueue($order, $stage->id);
                            $canAct = $authService->canActOnStage(auth()->user(), $order, $stage->id);
                            $isAdmin = auth()->user()->role->orderPermission?->can_edit ?? false;
                        @endphp
                        <tr>
                            <td>{{ $order->id }}</td>
                            <td>{{ $order->client->name }}</td>
                            <td>{{ $order->material }}</td>
                            <td>
                                @if($order->lleva_herrajeria)
                                    @if($order->herrajeria_delivered_at)
                                        <div class="text-success small">
                                            Entregado<br>
                                            <span class="x-small text-muted">{{ $order->herrajeria_delivered_at->format('d/m/Y') }}</span><br>
                                            <span class="x-small text-muted">Por: {{ $order->herrajeriaDeliveredBy?->name ?? '?' }}</span>
                                        </div>
                                    @else
                                        <span class="badge bg-soft-warning text-warning">Pendiente</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($order->lleva_manual_armado)
                                    @if($order->manual_armado_delivered_at)
                                        <div class="text-success small">
                                            Entregado<br>
                                            <span class="x-small text-muted">{{ $order->manual_armado_delivered_at->format('d/m/Y') }}</span><br>
                                            <span class="x-small text-muted">Por: {{ $order->manualArmadoDeliveredBy?->name ?? '?' }}</span>
                                        </div>
                                    @else
                                        <span class="badge bg-soft-warning text-warning">Pendiente</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                            <td>
                                @if($permissions->canViewFiles())
                                    <button type="button" class="btn btn-link btn-sm text-primary p-0 d-flex align-items-center" 
                                        data-bs-toggle="modal" 
                                        data-bs-target="#filesModal{{ $orderStage->id }}">
                                        <i class="bi bi-files me-1"></i> Ver archivos
                                    </button>
                                @else
                                    <span class="text-muted small"><i class="bi bi-lock"></i> Restringido</span>
                                @endif
                            </td>
                            <td>{{ $order->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                @if($orderStage->completed_at)
                                    <span class="badge bg-success">Finalizado</span>
                                @elseif($orderStage->is_pending)
                                    <span class="badge bg-danger" title="Pendiente: {{ $orderStage->pending_reason }}">Pendiente</span>
                                @elseif($orderStage->started_at)
                                    <span class="badge bg-warning text-dark">En proceso</span>
                                    @if(!$isNext)
                                        <span class="badge bg-info ms-1" title="Bypass de cola utilizado"><i class="bi bi-star-fill small"></i> Prioritario</span>
                                    @endif
                                @else
                                    <span class="badge bg-secondary">Pendiente</span>
                                    @if($isNext)
                                        <span class="badge bg-primary ms-1 shadow-sm animate-pulse">Siguiente</span>
                                    @endif
                                @endif
                            </td>
                            <td class="px-3">
                                <div style="cursor: pointer;" data-bs-toggle="modal"
                                    data-bs-target="#notesModal{{ $orderStage->id }}">
                                    @php
                                        $orderRemitLogs = $remitLogs[$order->id] ?? collect();
                                        $latestRemit = $orderRemitLogs->first();
                                        $remitData = $latestRemit?->remit_data;
                                        $showRemit = (bool) $remitData;
                                    @endphp
                                    @if($orderStage->is_pending)
                                        <small class="text-danger d-block fw-bold"><i class="bi bi-exclamation-triangle-fill"></i> Pendiente:
                                            {{ Str::limit($orderStage->pending_reason, 30) }}</small>
                                    @endif
                                    @if($showRemit)
                                        <small class="text-danger d-block fw-bold"><i
                                                class="bi bi-arrow-left-circle-fill"></i>Retorno:
                                            {{ Str::limit($remitData['reason'], 30) }}</small>
                                    @endif
                                    <small class="text-muted d-block"><span class="fw-bold">Gral:</span>
                                        {{ Str::limit($order->notes, 30) ?: '-' }}</small>
                                    <small class="text-info d-block fw-bold"><span
                                            class="text-dark">{{ $stageName }}:</span>
                                        {{ Str::limit($orderStage->notes, 30) ?: '-' }}</small>
                                    <div class="text-primary x-small mt-1"><i class="bi bi-pencil-square"></i> Ver/Editar
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex justify-content-between align-items-center w-100 flex-wrap gap-2">
                                    {{-- Group A: Producción (Left) --}}
                                    <div class="d-flex flex-wrap gap-2">
                                        @if($canAct)
                                            @if(!$orderStage->started_at)
                                                @if($orderStage->is_pending)
                                                    <button type="button" class="btn btn-sm btn-primary btn-action opacity-50" disabled title="No se puede procesar mientras esté pendiente.">
                                                        Iniciar
                                                    </button>
                                                @else
                                                    <form action="{{ route('order-stages.start', $orderStage->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-primary btn-action {{ !$isNext && !$isAdmin ? 'disabled opacity-50' : '' }}"
                                                            {{ !$isNext && !$isAdmin ? 'disabled' : '' }}
                                                            {{ !$isNext && !$isAdmin ? 'title="Este pedido no es el siguiente en la fila."' : '' }}>
                                                            Iniciar
                                                        </button>
                                                    </form>
                                                @endif
                                            @elseif(!$orderStage->completed_at)
                                                <form action="{{ route('order-stages.pause', $orderStage->id) }}" method="POST"
                                                    class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-warning btn-action">Detener proceso</button>
                                                </form>
                                                <form action="{{ route('order-stages.finish', $orderStage->id) }}" method="POST"
                                                    class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success btn-action {{ (!$isNext && !$isAdmin) || $orderStage->is_pending ? 'disabled opacity-50' : '' }}"
                                                        {{ (!$isNext && !$isAdmin) || $orderStage->is_pending ? 'disabled' : '' }}
                                                        {{ $orderStage->is_pending ? 'title="No se puede procesar mientras esté pendiente."' : (!$isNext && !$isAdmin ? 'title="Este pedido no es el siguiente en la fila."' : '') }}>
                                                        {{ $stage->is_delivery_stage ? 'Entrega del mueble realizada' : 'Finalizar' }}
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <span class="text-muted small">No autorizado</span>
                                        @endif

                                        @if($stage->can_remit && $canAct)
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-action {{ (!$isNext && !$isAdmin) || $orderStage->is_pending ? 'disabled opacity-50' : '' }}" 
                                                data-bs-toggle="modal"
                                                data-bs-target="#remitirModal{{ $orderStage->id }}"
                                                {{ (!$isNext && !$isAdmin) || $orderStage->is_pending ? 'disabled' : '' }}
                                                {{ $orderStage->is_pending ? 'title="No se puede procesar mientras esté pendiente."' : (!$isNext && !$isAdmin ? 'title="Este pedido no es el siguiente en la fila."' : '') }}>
                                                Remitir
                                            </button>
                                        @endif

                                        {{-- Pending Logic Controls (Admin/can_edit only) --}}
                                        @if($isAdmin && !$orderStage->completed_at && !$orderStage->started_at)
                                            @if($orderStage->is_pending)
                                                <form action="{{ route('order-stages.remove-pending', $orderStage->id) }}" method="POST" class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-success btn-action">Quitar pendiente</button>
                                                </form>
                                            @else
                                                <button type="button" class="btn btn-sm btn-outline-warning btn-action" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#pendingModal{{ $orderStage->id }}">
                                                    Marcar pendiente
                                                </button>
                                            @endif
                                        @endif
                                    </div>
                                    </div>

                                    {{-- Group B: Entregas (Right) --}}
                                    <div class="d-flex flex-wrap gap-2">
                                        @if($stage->is_delivery_stage && $canAct)
                                            @if($order->lleva_herrajeria && !$order->herrajeria_delivered_at)
                                                <form action="{{ route('order-stages.deliver-hardware', $orderStage->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Confirmar entrega de herrajería?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary btn-action">Entregar herrajería</button>
                                                </form>
                                            @endif
                                            @if($order->lleva_manual_armado && !$order->manual_armado_delivered_at)
                                                <form action="{{ route('order-stages.deliver-manual', $orderStage->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Confirmar entrega de manual de armado?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-outline-secondary btn-action">Entregar manual de armado</button>
                                                </form>
                                            @endif
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="text-center py-4 text-muted">No hay pedidos en esta etapa.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4 px-4">
            {{ $orders->links() }}
        </div>
    </div>
    </style>

    {{-- Modal para marcar como pendiente --}}
    @foreach($orders as $order)
        @php $orderStage = $order->orderStages->firstWhere('stage_id', $stage->id); @endphp
        @if($isAdmin && $orderStage && !$orderStage->completed_at && !$orderStage->started_at && !$orderStage->is_pending)
            <div class="modal fade" id="pendingModal{{ $orderStage->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form action="{{ route('order-stages.mark-as-pending', $orderStage->id) }}" method="POST">
                            @csrf
                            <div class="modal-header">
                                <h5 class="modal-title">Marcar pedido #{{ $order->id }} como Pendiente</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label">Razón del pendiente <span class="text-danger">*</span></label>
                                    <textarea name="pending_reason" class="form-control" rows="3" required maxlength="250" placeholder="Ej: Falta material, cliente solicitó retraso..."></textarea>
                                    <div class="form-text">Máximo 250 caracteres.</div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="submit" class="btn btn-warning">Confirmar Pendiente</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>