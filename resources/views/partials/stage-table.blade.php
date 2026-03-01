@inject('visibility', 'App\Services\VisibilityService')
@php
    $stageName = $stage->name;
    $isAdmin = auth()->user()->role->hasPermission('orders', 'edit');
    $permissions = $visibility::forUser(auth()->user());
@endphp

<div class="card mb-5 border-0 shadow-sm overflow-hidden">
    <div class="card-header bg-white border-bottom-0 pt-4 pb-0 d-flex justify-content-between align-items-center">
        <div>
            <span class="text-uppercase text-xs font-bold text-primary tracking-wider mb-1 d-block opacity-75">Módulo de
                Producción</span>
            <h4 class="mb-0 font-weight-bolder">{{ $stageName }}</h4>
        </div>
        <div class="d-flex align-items-center gap-3">
            <form action="{{ url()->current() }}" method="GET" class="d-flex align-items-center">
                <div class="input-group input-group-sm border rounded-pill overflow-hidden bg-light search-pill" 
                    style="width: 250px; transition: border-color 0.2s ease-in-out;">
                    <span class="input-group-text bg-transparent border-0 ps-3">
                        <i class="bi bi-search text-muted"></i>
                    </span>
                    <input type="text" name="search" class="form-control bg-transparent border-0 py-2 shadow-none" 
                        placeholder="Buscar por cliente o factura..." 
                        value="{{ request('search') }}"
                        onkeyup="debounceSubmit(this.form)"
                        onfocus="this.parentElement.style.borderColor = '#0d6efd'"
                        onblur="this.parentElement.style.borderColor = '#dee2e6'">
                </div>
                @if(request('search'))
                    <a href="{{ url()->current() }}" class="btn btn-link btn-sm text-decoration-none text-muted ms-2">Limpiar</a>
                @endif
            </form>
            <span class="badge bg-soft-primary text-primary rounded-pill">
                {{ $orders->total() }} pedidos en cola
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
                        <th>Sr. Especiales</th>
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
                            $isAdmin = auth()->user()->role->hasPermission('orders', 'edit');
                        @endphp
                        <tr>
                            <td>{{ $order->id }}</td>
                            <td>
                                @if(Str::length($order->client->name) > 50)
                                    <span style="cursor: pointer;" 
                                          data-bs-toggle="modal" 
                                          data-bs-target="#clientDetailModal{{ $orderStage->id }}">
                                        {{ Str::limit($order->client->name, 50) }}
                                        <i class="bi bi-info-circle text-primary small ms-1"></i>
                                    </span>
                                @else
                                    {{ $order->client->name }}
                                @endif
                            </td>
                            <td>
                                @php
                                    $activeMaterials = $order->orderMaterials->filter(fn($om) => is_null($om->cancelled_at));
                                    $materialLabels = $activeMaterials->map(function($om) {
                                        return $om->material->name . " (" . (floor($om->estimated_quantity) == $om->estimated_quantity ? number_format($om->estimated_quantity, 0) : number_format($om->estimated_quantity, 1)) . ")" . ($om->notes ? " - {$om->notes}" : "");
                                    });
                                    $materialText = $materialLabels->implode(', ');
                                @endphp

                                @if(Str::length($materialText) > 50)
                                    <span style="cursor: pointer;" 
                                          data-bs-toggle="modal" 
                                          data-bs-target="#materialDetailModal{{ $orderStage->id }}">
                                        {{ Str::limit($materialText, 50) }}
                                        <i class="bi bi-info-circle text-primary small ms-1"></i>
                                    </span>
                                @else
                                    {{ $materialText ?: '-' }}
                                @endif
                            </td>
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
                                @php
                                    $activeServices = $order->orderSpecialServices->filter(fn($oss) => is_null($oss->cancelled_at));
                                    $serviceLabels = $activeServices->map(function($oss) {
                                        return $oss->specialService->name . ($oss->notes ? " ({$oss->notes})" : "");
                                    });
                                    $serviceText = $serviceLabels->implode(', ');
                                @endphp

                                @if($activeServices->count() > 0)
                                    @if(Str::length($serviceText) > 40)
                                        <span style="cursor: pointer;" 
                                              data-bs-toggle="modal" 
                                              data-bs-target="#specialServicesModal{{ $orderStage->id }}">
                                            {{ Str::limit($serviceText, 40) }}
                                            <i class="bi bi-info-circle text-primary small ms-1"></i>
                                        </span>
                                    @else
                                        <small>{{ $serviceText }}</small>
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
                                    <span class="badge bg-danger" title="Razón: {{ $orderStage->pending_reason }}">Pendiente</span>
                                    <div class="text-xs text-danger mt-1 fw-bold" style="font-size: 0.7rem; line-height: 1;">
                                        <i class="bi bi-shield-lock-fill"></i> Solicitar desbloqueo al Admin
                                    </div>
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
                                    <small class="text-primary d-block fw-bold"><span
                                            class="text-dark">{{ $stageName }}:</span>
                                        {{ Str::limit($orderStage->notes, 30) ?: '-' }}</small>
                                    <div class="text-primary x-small mt-1"><i class="bi bi-pencil-square"></i> Ver/Editar
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="d-flex justify-content-start align-items-center w-100 flex-wrap gap-2">
                                    {{-- Group A: Producción (Left) --}}
                                    <div class="d-flex flex-wrap gap-2">
                                        @if($canAct)
                                            @if(!$orderStage->started_at)
                                                @if($orderStage->is_pending)
                                                    <button type="button" class="btn btn-sm btn-primary btn-action opacity-50" disabled title="Bloqueado: El pedido está pendiente. Solicite al administrador que lo desbloquee.">
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
                                                @if($isAdmin)
                                                    <form action="{{ route('order-stages.pause', $orderStage->id) }}" method="POST"
                                                        class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-warning btn-action">Detener proceso</button>
                                                    </form>
                                                @endif
                                                    <form action="{{ route('order-stages.finish', $orderStage->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        <button type="submit" class="btn btn-sm btn-success btn-action {{ !$canAct || $orderStage->is_pending ? 'disabled opacity-50' : '' }}"
                                                            {{ !$canAct || $orderStage->is_pending ? 'disabled' : '' }}
                                                            {{ $orderStage->is_pending ? 'title="Bloqueado: El pedido está pendiente. Solicite al administrador que lo desbloquee."' : (!$canAct ? 'title="Este pedido no es el siguiente en la fila."' : '') }}>
                                                            {{ $stage->is_delivery_stage ? 'Entrega del mueble realizada' : 'Finalizar' }}
                                                        </button>
                                                    </form>

                                                @if($stage->is_delivery_stage)
                                                    <button type="button" class="btn btn-sm btn-outline-info btn-action" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#evidenceModal{{ $orderStage->id }}">
                                                        <i class="bi bi-camera-fill"></i> Capturar Evidencia
                                                        @php
                                                            $evidenceCount = $order->orderFiles->filter(fn($f) => str_contains(strtolower($f->fileType->name), 'evidencia'))->count();
                                                        @endphp
                                                        @if($evidenceCount > 0)
                                                            <span class="badge bg-info text-white rounded-pill ms-1">{{ $evidenceCount }}</span>
                                                        @endif
                                                    </button>
                                                @endif
                                            @endif
                                        @else
                                            <span class="text-muted small">No autorizado</span>
                                        @endif

                                        @if($stage->can_remit && $canAct)
                                            <button type="button" class="btn btn-sm btn-outline-danger btn-action {{ !$canAct || $orderStage->is_pending ? 'disabled opacity-50' : '' }}" 
                                                data-bs-toggle="modal"
                                                data-bs-target="#remitirModal{{ $orderStage->id }}"
                                                {{ !$canAct || $orderStage->is_pending ? 'disabled' : '' }}
                                                {{ $orderStage->is_pending ? 'title="Bloqueado: El pedido está pendiente. Solicite al administrador que lo desbloquee."' : (!$canAct ? 'title="Este pedido no es el siguiente en la fila."' : '') }}>
                                                Remitir
                                            </button>
                                        @endif

                                        {{-- Pending Logic Controls (Admin/can_edit only) --}}
                                        @if($isAdmin && !$orderStage->completed_at)
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

                                    {{-- Group B: Entregas (Right) --}}
                                    <div class="d-flex flex-wrap gap-2">
                                        @if($stage->is_delivery_stage && $canAct)
                                            @if($order->lleva_herrajeria && !$order->herrajeria_delivered_at)
                                                <form action="{{ route('order-stages.deliver-hardware', $orderStage->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Confirmar entrega de herrajería?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-secondary btn-action" {{ $orderStage->is_pending ? 'disabled title="Bloqueado"' : '' }}>Entregar herrajería</button>
                                                </form>
                                            @endif
                                            @if($order->lleva_manual_armado && !$order->manual_armado_delivered_at)
                                                <form action="{{ route('order-stages.deliver-manual', $orderStage->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Confirmar entrega de manual de armado?')">
                                                    @csrf
                                                    <button type="submit" class="btn btn-sm btn-secondary btn-action" {{ $orderStage->is_pending ? 'disabled title="Bloqueado"' : '' }}>Entregar manual de armado</button>
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
</div>

{{-- Modals Loop outside the overflow-hidden card --}}
@foreach($orders as $order)
        @php $orderStage = $order->orderStages->firstWhere('stage_id', $stage->id); @endphp
        @if($orderStage)
            {{-- Modal para marcar como pendiente --}}
            @if($isAdmin && !$orderStage->completed_at && !$orderStage->is_pending)
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

            {{-- Modal de Detalle de Cliente --}}
            @if(Str::length($order->client->name) > 50)
                <div class="modal fade" id="clientDetailModal{{ $orderStage->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content border-0 shadow">
                            <div class="modal-header bg-dark text-white border-0">
                                <h5 class="modal-title">Nombre del Cliente - Pedido #{{ $order->id }}</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body p-4">
                                <p class="mb-0 text-dark" style="white-space: pre-wrap;">{{ $order->client->name }}</p>
                            </div>
                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Modal de Detalle de Material --}}
            @php
                $activeMaterials = $order->orderMaterials->filter(fn($om) => is_null($om->cancelled_at));
            @endphp
            <div class="modal fade" id="materialDetailModal{{ $orderStage->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-dark text-white border-0">
                            <h5 class="modal-title">Detalle de Materiales - Pedido #{{ $order->id }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 text-start">
                            <ul class="list-group list-group-flush">
                                @foreach($activeMaterials as $om)
                                    <li class="list-group-item px-0 border-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold">{{ $om->material->name }}</span>
                                            <span class="badge bg-light text-dark border">{{ $om->estimated_quantity }}</span>
                                        </div>
                                        @if($om->notes)
                                            <div class="small text-muted">{{ $om->notes }}</div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal de Servicios Especiales --}}
            <div class="modal fade" id="specialServicesModal{{ $orderStage->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-dark text-white border-0">
                            <h5 class="modal-title">Servicios Especiales - Pedido #{{ $order->id }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 text-start">
                            <ul class="list-group list-group-flush">
                                @php
                                    $activeServices = $order->orderSpecialServices->filter(fn($oss) => is_null($oss->cancelled_at));
                                @endphp
                                @foreach($activeServices as $oss)
                                    <li class="list-group-item px-0 border-0">
                                        <div class="fw-bold text-primary">{{ $oss->specialService->name }}</div>
                                        @if($oss->notes)
                                            <div class="small text-muted ps-2 border-start ms-1">{{ $oss->notes }}</div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Modal for Evidence Photos --}}
            @if($stage->is_delivery_stage && $orderStage->started_at && !$orderStage->completed_at)
                <div class="modal fade" id="evidenceModal{{ $orderStage->id }}" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content border-0 shadow">
                            <form action="{{ route('order-stages.upload-evidence', $orderStage->id) }}" method="POST" enctype="multipart/form-data" 
                                x-data="{ files: null, count: 0, existing: {{ $order->orderFiles->filter(fn($f) => str_contains(strtolower($f->fileType->name), 'evidencia'))->count() }} }">
                                @csrf
                                <div class="modal-header bg-info text-white border-0">
                                    <h5 class="modal-title"><i class="bi bi-camera-fill me-2"></i>Capturar Evidencia - #{{ $order->id }}</h5>
                                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body p-4">
                                    <div class="alert alert-info py-2 small mb-3">
                                        <i class="bi bi-info-circle-fill me-1"></i> Se permiten máximo 2 fotos de evidencia por pedido.
                                        <br>Actual: <span class="fw-bold" x-text="existing"></span>/2
                                    </div>

                                    <div class="mb-3" x-show="existing < 2">
                                        <label class="form-label font-weight-bold">Seleccionar fotos (Máx <span x-text="2 - existing"></span>)</label>
                                        <input type="file" name="evidence_photos[]" class="form-control" accept="image/*" multiple 
                                            @change="files = $event.target.files; count = files.length">
                                        <div class="form-text mt-2" x-show="count > 0">
                                            Seleccionado: <span class="fw-bold" x-text="count"></span> archivo(s)
                                            <template x-if="count + existing > 2">
                                                <div class="text-danger fw-bold mt-1">
                                                    <i class="bi bi-exclamation-triangle-fill"></i> Supera el límite de 2 fotos.
                                                </div>
                                            </template>
                                        </div>
                                    </div>

                                    <div x-show="existing >= 2" class="text-center py-3">
                                        <i class="bi bi-check-circle-fill text-success" style="font-size: 2rem;"></i>
                                        <p class="mt-2 fw-bold">Ya se han subido las 2 fotos reglamentarias.</p>
                                    </div>

                                    @php
                                        $evPhotos = $order->orderFiles->filter(fn($f) => str_contains(strtolower($f->fileType->name), 'evidencia'))->values();
                                    @endphp
                                    @if($evPhotos->count() > 0)
                                        <div class="mt-4">
                                            <label class="form-label font-weight-bold">Evidencia Actual</label>
                                            <div class="row g-2">
                                                @php
                                                    $imageUrls = $evPhotos->map(fn($p) => $p->fileUrl)->values()->toArray();
                                                @endphp
                                                @foreach($evPhotos as $index => $photo)
                                                    <div class="col-6">
                                                        <div class="position-relative rounded overflow-hidden shadow-sm shadow-hover" style="height: 120px;">
                                                            <img src="{{ $photo->fileUrl }}" 
                                                                class="w-100 h-100 object-fit-cover cursor-zoom-in" 
                                                                alt="Foto de evidencia"
                                                                data-images="{{ json_encode($imageUrls) }}"
                                                                onclick="openLightbox(JSON.parse(this.dataset.images), {{ $index }})">
                                                            
                                                            <div class="position-absolute top-0 end-0 p-1">
                                                                <button type="button" 
                                                                    class="btn btn-sm btn-danger rounded-circle p-0 d-flex align-items-center justify-content-center shadow-sm" 
                                                                    style="width: 32px; height: 32px;" 
                                                                    title="Eliminar foto"
                                                                    onclick="confirmDeletion('{{ route('order-files.destroy', $photo->id) }}')">
                                                                    <i class="bi bi-trash3-fill" style="font-size: 1rem;"></i>
                                                                </button>
                                                            </div>

                                                            <div class="position-absolute bottom-0 start-0 w-100 bg-dark bg-opacity-50 py-1 px-2 text-white x-small d-flex justify-content-between align-items-center">
                                                                <span>#{{ $loop->iteration }}</span>
                                                                <span class="text-white"><i class="bi bi-image"></i></span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                </div>
                                <div class="modal-footer border-0">
                                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                                    <button type="submit" class="btn btn-info rounded-pill px-4 text-white" 
                                        x-show="existing < 2"
                                        :disabled="count == 0 || (count + existing > 2)">
                                        Guardar fotos
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        @endif
    @endforeach