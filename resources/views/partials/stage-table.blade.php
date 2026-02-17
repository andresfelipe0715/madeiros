@php
    $stageName = $stage->name;
    // Normalize stage name for condition checks
    $normName = strtolower($stageName);
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
                        @if(in_array($normName, ['corte', 'enchape', 'servicios especiales', 'revision', 'entrega']))
                            <th>Archivo Orden</th>
                            <th>Archivo Proyecto</th>
                        @endif
                        @if($normName === 'corte')
                            <th>Archivo Máquina</th>
                        @endif
                        <th>Fecha Envío</th>
                        <th>Estado</th>
                        @if($normName === 'corte')
                            <th>Tiempo</th>
                        @endif
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
                            @if(in_array($normName, ['corte', 'enchape', 'servicios especiales', 'revision', 'entrega']))
                                <td>
                                    @if($orderFile)
                                        <a href="{{ $orderFile->file_url }}" target="_blank" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-file-earmark-pdf"></i> PDF
                                        </a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if($projectFile)
                                        <a href="{{ $projectFile->file_url }}" target="_blank"
                                            class="btn btn-sm btn-outline-info">Ver</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            @endif
                            @if($normName === 'corte')
                                <td>
                                    @if($machineFile)
                                        <a href="{{ $machineFile->file_url }}" target="_blank"
                                            class="btn btn-sm btn-outline-info">Ver</a>
                                    @else
                                        -
                                    @endif
                                </td>
                            @endif
                            <td>{{ $order->created_at->format('d/m/Y') }}</td>
                            <td>
                                @if($orderStage->completed_at)
                                    <span class="badge bg-success">Finalizado</span>
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
                            @if($normName === 'corte')
                                <td>
                                    @if($orderStage->started_at && !$orderStage->completed_at)
                                        {{ $orderStage->started_at->diffForHumans(null, true) }}
                                    @elseif($orderStage->completed_at && $orderStage->started_at)
                                        {{ $orderStage->started_at->diff($orderStage->completed_at)->format('%H:%I:%S') }}
                                    @else
                                        -
                                    @endif
                                </td>
                            @endif
                            <td class="px-3">
                                <div style="cursor: pointer;" data-bs-toggle="modal"
                                    data-bs-target="#notesModal{{ $orderStage->id }}">
                                    @php
                                        $orderRemitLogs = $remitLogs[$order->id] ?? collect();
                                        $latestRemit = $orderRemitLogs->first();
                                        $remitData = $latestRemit?->remit_data;
                                        $showRemit = (bool) $remitData;
                                    @endphp
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
                                                <form action="{{ route('order-stages.start', $orderStage->id) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-primary btn-action {{ !$isNext && !$isAdmin ? 'disabled opacity-50' : '' }}"
                                                        {{ !$isNext && !$isAdmin ? 'disabled' : '' }}
                                                        {{ !$isNext && !$isAdmin ? 'title="Este pedido no es el siguiente en la fila."' : '' }}>
                                                        Iniciar
                                                    </button>
                                                </form>
                                            @elseif(!$orderStage->completed_at)
                                                <form action="{{ route('order-stages.pause', $orderStage->id) }}" method="POST"
                                                    class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-warning btn-action">Detener proceso</button>
                                                </form>
                                                <form action="{{ route('order-stages.finish', $orderStage->id) }}" method="POST"
                                                    class="d-inline">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-success btn-action {{ !$isNext && !$isAdmin ? 'disabled opacity-50' : '' }}"
                                                        {{ !$isNext && !$isAdmin ? 'disabled' : '' }}
                                                        {{ !$isNext && !$isAdmin ? 'title="Este pedido no es el siguiente en la fila."' : '' }}>
                                                        {{ $normName === 'entrega' ? 'Entrega del mueble realizada' : 'Finalizar' }}
                                                    </button>
                                                </form>
                                            @endif
                                        @else
                                            <span class="text-muted small">No autorizado</span>
                                        @endif

                                        @if($normName !== 'entrega' && $normName !== 'corte' && $canAct)
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-action {{ !$isNext && !$isAdmin ? 'disabled opacity-50' : '' }}" 
                                                data-bs-toggle="modal"
                                                data-bs-target="#remitirModal{{ $orderStage->id }}"
                                                {{ !$isNext && !$isAdmin ? 'disabled' : '' }}
                                                {{ !$isNext && !$isAdmin ? 'title="Este pedido no es el siguiente en la fila."' : '' }}>
                                                Remitir
                                            </button>
                                        @endif
                                    </div>

                                    {{-- Group B: Entregas (Right) --}}
                                    <div class="d-flex flex-wrap gap-2">
                                        @if($normName === 'entrega' && $canAct)
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
    <style>
        .btn-action {
            width: 130px;
            min-height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1.2;
            padding: 0.25rem 0.5rem;
            font-size: 0.7rem;
            text-transform: uppercase;
            font-weight: 700;
            white-space: normal;
            word-wrap: break-word;
            text-align: center;
        }
    </style>
</div>