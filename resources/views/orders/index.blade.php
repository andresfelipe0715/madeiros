<x-app-layout>
    <x-slot name="header">
        <div class="d-flex flex-column flex-sm-row justify-content-between align-items-start align-items-sm-center gap-3">
            <h2 class="h4 font-weight-bold mb-0">
                {{ __('Lista de Órdenes') }}
            </h2>
            @can('create-orders')
                <a href="{{ route('orders.create') }}" class="btn btn-primary">
                    {{ __('Nueva Orden') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-fluid px-3 px-md-5">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-4">
                <div class="d-flex align-items-center gap-3 w-100 w-md-auto">
                    <button type="button" onclick="window.location.reload();" class="btn btn-sm btn-light border rounded-pill d-flex align-items-center px-3 shadow-none text-muted" title="Actualizar registros">
                        <i class="bi bi-arrow-clockwise me-1"></i> Actualizar
                    </button>
                    <form action="{{ route('orders.index') }}" method="GET" class="d-flex align-items-center w-100 w-sm-auto">
                        <div class="input-group shadow-sm border rounded-pill overflow-hidden bg-light search-pill w-100"
                            style="min-width: 250px; max-width: 350px;">
                            <span class="input-group-text bg-transparent border-0 ps-3">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" name="search" class="form-control bg-transparent border-0 py-2 shadow-none"
                                placeholder="Factura, cliente o documento..." value="{{ request('search') }}"
                                onkeyup="debounceSubmit(this.form)">
                        </div>

                        @if(request('search'))
                            <a href="{{ route('orders.index') }}"
                                class="btn btn-link btn-sm text-decoration-none text-muted ms-2">Limpiar</a>
                        @endif
                    </form>
                </div>
                <div class="text-muted small">
                    Mostrando {{ $orders->firstItem() ?? 0 }} - {{ $orders->lastItem() ?? 0 }} de {{ $orders->total() }}
                    órdenes
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light text-muted text-uppercase small font-weight-bold">
                                <tr>
                                    <th class="px-4 py-3">ID</th>
                                    <th class="px-4 py-3">Cliente</th>
                                    <th class="px-4 py-3 text-nowrap">Creado por</th>
                                    <th class="px-4 py-3">Factura</th>
                                    <th class="px-4 py-3">Material</th>
                                    <th class="px-4 py-3">Observaciones</th>
                                    <th class="px-4 py-3">Herrajería</th>
                                    <th class="px-4 py-3">Manual</th>
                                    <th class="px-4 py-3">Etapa Actual</th>
                                    <th class="px-4 py-3 text-nowrap">Fecha Creación</th>
                                    <th class="px-4 py-3 text-nowrap">Fecha Entrega</th>
                                    <th class="px-4 py-3 text-end">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="align-middle">
                                @forelse($orders as $order)
                                    <tr>
                                        <td class="px-4 py-3">#{{ $order->id }}</td>
                                        <td class="px-4 py-3">
                                            @php $clientName = $order->client->name ?? 'Cliente Eliminado'; @endphp
                                            @if(Str::length($clientName) > 25)
                                                <span style="cursor: pointer;" data-bs-toggle="modal"
                                                    data-bs-target="#clientDetailModal{{ $order->id }}">
                                                    {{ Str::limit($clientName, 25) }}
                                                    <i class="bi bi-info-circle text-primary small ms-1"></i>
                                                </span>
                                            @else
                                                {{ $clientName }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-nowrap">
                                            @if(Str::length($order->creator_name) > 25)
                                                <span style="cursor: pointer;" data-bs-toggle="modal"
                                                    data-bs-target="#creatorDetailModal{{ $order->id }}">
                                                    {{ Str::limit($order->creator_name, 25) }}
                                                    <i class="bi bi-info-circle text-primary small ms-1"></i>
                                                </span>
                                            @else
                                                {{ $order->creator_name }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if(Str::length($order->invoice_number) > 20)
                                                <span style="cursor: pointer;" data-bs-toggle="modal"
                                                    data-bs-target="#invoiceDetailModal{{ $order->id }}">
                                                    {{ Str::limit($order->invoice_number, 20) }}
                                                    <i class="bi bi-info-circle text-primary small ms-1"></i>
                                                </span>
                                            @else
                                                {{ $order->invoice_number }}
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @php
                                                $activeMaterials = $order->orderMaterials->filter(fn($om) => is_null($om->cancelled_at));
                                                $cancelledMaterials = $order->orderMaterials->filter(fn($om) => !is_null($om->cancelled_at));
                                                $materialLabels = $activeMaterials->map(function ($om) {
                                                    $name = $om->material->name ?? 'Material Eliminado';
                                                    return $name . " (" . (floor($om->estimated_quantity) == $om->estimated_quantity ? number_format($om->estimated_quantity, 0) : number_format($om->estimated_quantity, 1)) . ")" . ($om->notes ? " - {$om->notes}" : "");
                                                });
                                                $materialText = $materialLabels->implode(', ');
                                            @endphp

                                            <div class="d-flex align-items-center" style="cursor: pointer;"
                                                data-bs-toggle="modal"
                                                data-bs-target="#materialDetailModal{{ $order->id }}">
                                                <span class="text-truncate" style="max-width: 150px;">
                                                    {{ $materialText ?: '-' }}
                                                </span>
                                                <i class="bi bi-info-circle text-primary ms-2"></i>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            @php
                                                $currentOrderStage = $order->orderStages->whereNull('completed_at')->sortBy('sequence')->first();
                                                $remitLogs = $order->logs()->where('action', 'like', 'remit|%')->latest()->get();
                                                $latestRemit = $remitLogs->first();
                                                $remitData = $latestRemit?->remit_data;
                                            @endphp
                                            <div style="cursor: pointer;" data-bs-toggle="modal"
                                                data-bs-target="#notesModal{{ $order->id }}">
                                                @if($currentOrderStage && $currentOrderStage->is_pending)
                                                    <small class="text-danger d-block fw-bold"><i
                                                            class="bi bi-exclamation-triangle-fill"></i> Pendiente:
                                                        {{ Str::limit($currentOrderStage->pending_reason, 20) }}</small>
                                                @endif
                                                @if($remitData)
                                                    <small class="text-danger d-block fw-bold"><i
                                                            class="bi bi-arrow-left-circle-fill"></i>Retorno:
                                                        {{ Str::limit($remitData['reason'], 20) }}</small>
                                                @endif
                                                <small class="text-muted d-block"><span class="fw-bold">Gral:</span>
                                                    {{ Str::limit($order->notes, 20) ?: '-' }}</small>
                                                @if($currentOrderStage)
                                                    <small class="text-primary d-block fw-bold"><span
                                                            class="text-dark">{{ $currentOrderStage->stage->name ?? 'Etapa Eliminada' }}:</span>
                                                        {{ Str::limit($currentOrderStage->notes, 20) ?: '-' }}</small>
                                                @endif
                                                <div class="text-primary x-small mt-1"><i class="bi bi-pencil-square"></i>
                                                    Ver historial
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($order->lleva_herrajeria)
                                                @if($order->herrajeria_delivered_at)
                                                    <span class="text-success small"><i class="bi bi-check-circle-fill"></i>
                                                        Entregada</span>
                                                @else
                                                    <span class="text-warning small"><i class="bi bi-clock-history"></i>
                                                        Pendiente</span>
                                                @endif
                                            @else
                                                <span class="text-muted small">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @if($order->lleva_manual_armado)
                                                @if($order->manual_armado_delivered_at)
                                                    <span class="text-success small"><i class="bi bi-check-circle-fill"></i>
                                                        Entregado</span>
                                                @else
                                                    <span class="text-warning small"><i class="bi bi-clock-history"></i>
                                                        Pendiente</span>
                                                @endif
                                            @else
                                                <span class="text-muted small">N/A</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3">
                                            @php
                                                $stageName = $order->currentStageName();
                                            @endphp
                                            @if($stageName === 'Entregada')
                                                <span class="badge bg-success-subtle text-success">
                                                    {{ $stageName }}
                                                </span>
                                            @elseif($stageName === 'Sin etapa')
                                                <span class="badge bg-secondary-subtle text-secondary">
                                                    {{ $stageName }}
                                                </span>
                                            @else
                                                <span class="badge bg-primary-subtle text-primary">
                                                    {{ $stageName }}
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-nowrap">
                                            {{ $order->created_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-4 py-3 text-nowrap">
                                            @if($order->delivered_at)
                                                {{ $order->delivered_at->format('d/m/Y H:i') }}
                                            @else
                                                <span class="text-muted italic small">No entregada</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-end">
                                            @can('edit-orders')
                                                <a href="{{ route('orders.edit', $order) }}"
                                                    class="btn btn-sm btn-outline-primary">
                                                    Gestionar
                                                </a>
                                            @endcan
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="px-4 py-5 text-center text-muted">
                                            No se encontraron órdenes.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($orders->hasPages())
                    <div class="card-footer bg-white border-top-0 py-3">
                        {{ $orders->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modals Loop --}}
    @foreach($orders as $order)
        {{-- Modal de Detalle de Cliente --}}
        @php $clientName = $order->client->name ?? 'Cliente Eliminado'; @endphp
        @if(Str::length($clientName) > 25)
            <div class="modal fade" id="clientDetailModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-dark text-white border-0">
                            <h5 class="modal-title">Nombre del Cliente - Orden #{{ $order->id }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 text-start">
                            <p class="mb-0 text-dark text-break"><span class="preserve-text">{{ $clientName }}</span></p>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-light rounded-pill px-4"
                                data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Modal de Creado por --}}
        @if(Str::length($order->creator_name) > 25)
            <div class="modal fade" id="creatorDetailModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-dark text-white border-0">
                            <h5 class="modal-title">Creado por - Orden #{{ $order->id }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 text-start">
                            <p class="mb-0 text-dark text-break"><span class="preserve-text">{{ $order->creator_name }}</span></p>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-light rounded-pill px-4"
                                data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Modal de Factura --}}
        @if(Str::length($order->invoice_number) > 20)
            <div class="modal fade" id="invoiceDetailModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content border-0 shadow">
                        <div class="modal-header bg-dark text-white border-0">
                            <h5 class="modal-title">Número de Factura - Orden #{{ $order->id }}</h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                                aria-label="Close"></button>
                        </div>
                        <div class="modal-body p-4 text-start">
                            <p class="mb-0 text-dark text-break"><span class="preserve-text">{{ $order->invoice_number }}</span></p>
                        </div>
                        <div class="modal-footer border-0">
                            <button type="button" class="btn btn-light rounded-pill px-4"
                                data-bs-dismiss="modal">Cerrar</button>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Modal de Detalle de Material --}}
        @php
            $activeMaterials = $order->orderMaterials->filter(fn($om) => is_null($om->cancelled_at));
            $cancelledMaterials = $order->orderMaterials->filter(fn($om) => !is_null($om->cancelled_at));
        @endphp
        <div class="modal fade" id="materialDetailModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-scrollable">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-dark text-white border-0">
                        <h5 class="modal-title">Detalle de Materiales - Orden #{{ $order->id }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-start" style="max-height: 60vh; overflow-y: auto;">
                        <h6 class="small fw-bold text-primary text-uppercase mb-3">Materiales Activos</h6>
                        @if($activeMaterials->isEmpty())
                            <p class="text-muted small">No hay materiales activos.</p>
                        @else
                            <ul class="list-group list-group-flush mb-4">
                                @foreach($activeMaterials as $om)
                                    <li class="list-group-item px-0 border-0 py-1">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span class="fw-bold text-break"><span class="preserve-text">{{ $om->material->name ?? 'Material Eliminado' }}</span></span>
                                            <span class="badge bg-light text-dark border">{{ $om->estimated_quantity }}</span>
                                        </div>
                                        @if($om->notes)
                                            <div class="small text-muted">{{ $om->notes }}</div>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        @if($cancelledMaterials->isNotEmpty())
                            <h6 class="small fw-bold text-danger text-uppercase mb-3">Materiales Cancelados</h6>
                            <ul class="list-group list-group-flush">
                                @foreach($cancelledMaterials as $om)
                                    <li class="list-group-item px-0 border-0 py-1 opacity-75">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <span
                                                class="text-danger fw-bold text-decoration-line-through text-break"><span class="preserve-text">{{ $om->material->name ?? 'Material Eliminado' }}</span></span>
                                            <span class="badge bg-danger-subtle text-danger">{{ $om->estimated_quantity }}
                                                (Cancelado)</span>
                                        </div>
                                        @if($om->notes)
                                            <div class="small text-muted">{{ $om->notes }}</div>
                                        @endif
                                        <div class="extra-small text-muted">
                                            Cancelado el {{ $om->cancelled_at->format('d/m/Y H:i') }}
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                            data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>

    {{-- Modal de Notas y Traceabilidad (History) --}}
    @php
        $remitLogs = $order->logs()->where('action', 'like', 'remit|%')->latest()->get();
        $allOrderStages = $order->orderStages->sortBy('sequence');
    @endphp
    <div class="modal fade" id="notesModal{{ $order->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content border-0 shadow">
                <div class="modal-header bg-primary text-white border-0">
                    <h5 class="modal-title">Traceabilidad y Notas - Orden #{{ $order->id }}</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4 bg-light" style="max-height: 70vh; overflow-y: auto;">
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

                        <!-- 2. Full Stages and Remit History -->
                        {{-- We merge stages and remit logs for a chronological view --}}
                        @php
                            $timelineItems = collect();
                            
                            foreach($allOrderStages as $os) {
                                if($os->notes || $os->is_pending) {
                                    $timelineItems->push([
                                        'date' => $os->updated_at,
                                        'type' => 'stage',
                                        'data' => $os
                                    ]);
                                }
                            }

                            foreach($remitLogs as $log) {
                                $timelineItems->push([
                                    'date' => $log->created_at,
                                    'type' => 'remit',
                                    'data' => $log
                                ]);
                            }

                            $timelineItems = $timelineItems->sortBy('date');
                        @endphp

                        @foreach($timelineItems as $item)
                            @if($item['type'] === 'stage')
                                @php $os = $item['data']; @endphp
                                @if($os->notes)
                                    <div class="timeline-item pb-4 position-relative">
                                        <div class="timeline-indicator bg-primary position-absolute rounded-circle" style="left: -20px; width: 12px; height: 12px; top: 5px;"></div>
                                        <div class="card border-0 shadow-sm border-left-primary" style="border-left: 3px solid #0d6efd !important;">
                                            <div class="card-body p-3">
                                                <h6 class="text-uppercase text-xs font-weight-bold text-primary mb-2">Etapa: {{ $os->stage->name ?? 'Etapa Eliminada' }}</h6>
                                                <p class="mb-0 text-dark small text-break">{{ $os->notes }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                                @if($os->is_pending)
                                    <div class="timeline-item pb-4 position-relative">
                                        <div class="timeline-indicator bg-warning position-absolute rounded-circle" style="left: -20px; width: 12px; height: 12px; top: 5px;"></div>
                                        <div class="card border-0 shadow-sm border-left-warning" style="border-left: 3px solid #ffc107 !important;">
                                            <div class="card-body p-3">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <h6 class="text-xs font-weight-bold text-warning mb-0 text-uppercase">Pendiente en {{ $os->stage->name ?? 'Etapa Eliminada' }}</h6>
                                                    <small class="x-small text-muted">{{ $os->pending_marked_at?->format('d/m/Y H:i') ?? '' }}</small>
                                                </div>
                                                <p class="mb-0 text-dark small text-break"><span class="fw-bold">Razón:</span> {{ $os->pending_reason }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            @else
                                @php 
                                    $log = $item['data'];
                                    $data = $log->remit_data;
                                    $fromStage = \App\Models\Stage::find($data['from'] ?? null);
                                    $toStage = \App\Models\Stage::find($data['to'] ?? null);
                                @endphp
                                <div class="timeline-item pb-4 position-relative">
                                    <div class="timeline-indicator bg-danger position-absolute rounded-circle" style="left: -20px; width: 12px; height: 12px; top: 5px;"></div>
                                    <div class="card border-0 shadow-sm border-left-danger" style="border-left: 3px solid #dc3545 !important;">
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-1">
                                                <h6 class="text-xs font-weight-bold text-danger mb-0 text-uppercase">Retorno de Producción</h6>
                                                <small class="x-small text-muted">{{ $log->created_at->format('d/m/Y H:i') }}</small>
                                            </div>
                                            <p class="mb-1 text-dark small">
                                                <span class="fw-bold">Desde:</span> {{ $fromStage?->name ?? 'Etapa anterior' }}
                                                <span class="fw-bold ms-2">A:</span> {{ $toStage?->name ?? 'Etapa destino' }}
                                            </p>
                                            <p class="mb-0 text-dark small text-break"><span class="fw-bold">Motivo:</span> {{ $data['reason'] ?? '' }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
                <div class="modal-footer border-0 p-4 pt-0">
                    <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    @endforeach

    <style>
        /* Search bar fixes */
        .search-pill input {
            padding-left: 1rem;
            /* remove extra space inside input */
            outline: none;
            /* remove blue focus line */
        }

        .search-pill .input-group-text {
            padding-left: 0rem;
            /* adjust icon spacing */
            padding-right: 0rem;
        }

        .timeline-container {
            border-left: 2px dashed #dee2e6;
            margin-left: 25px;
            padding-left: 10px;
        }

        .border-left-danger {
            border-left-width: 4px !important;
        }

        .border-left-warning {
            border-left-width: 4px !important;
        }

        .border-left-primary {
            border-left-width: 4px !important;
        }

        .text-xs {
            font-size: 0.7rem;
        }

        .x-small {
            font-size: 0.75rem;
        }
    </style>
</x-app-layout>