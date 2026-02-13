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
            <span class="badge bg-soft-primary text-primary rounded-pill">{{ $orders->count() }} Pedidos en cola</span>
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
                            $canAct = $authService->canActOnStage(auth()->user(), $order, $stage->id);
                        @endphp
                        <tr>
                            <td>{{ $order->id }}</td>
                            <td>{{ $order->client->name }}</td>
                            <td>{{ $order->material }}</td>
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
                                @else
                                    <span class="badge bg-secondary">Pendiente</span>
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
                                <div class="btn-group btn-group-sm">
                                    @if($canAct)
                                        @if(!$orderStage->started_at)
                                            <form action="{{ route('order-stages.start', $orderStage->id) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="btn btn-primary">Iniciar</button>
                                            </form>
                                        @elseif(!$orderStage->completed_at)
                                            <form action="{{ route('order-stages.pause', $orderStage->id) }}" method="POST"
                                                class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-warning">Pausar</button>
                                            </form>
                                            <form action="{{ route('order-stages.finish', $orderStage->id) }}" method="POST"
                                                class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-success">Finalizar</button>
                                            </form>
                                        @endif
                                    @else
                                        <span class="text-muted small">No autorizado</span>
                                    @endif

                                    @if($normName !== 'entrega' && $normName !== 'corte')
                                        <button type="button" class="btn btn-outline-danger ms-1" data-bs-toggle="modal"
                                            data-bs-target="#remitirModal{{ $orderStage->id }}">
                                            Remitir
                                        </button>
                                    @endif
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
    </div>
</div>