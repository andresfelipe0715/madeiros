<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">
                {{ __('Historial de Movimientos') }}
                @if($material)
                    : {{ \Illuminate\Support\Str::limit($material->name, 40) }}
                @else
                    (General Bodega)
                @endif
            </h2>
            <div class="d-flex gap-2">
                @if($material)
                    <a href="{{ route('bodega.logs.all') }}" class="btn btn-outline-primary">
                        {{ __('Ver Todo el Historial') }}
                    </a>
                @endif
                <a href="{{ route('bodega.index') }}" class="btn btn-secondary">
                    {{ __('Volver a Bodega') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-fluid px-5">
            <div class="card shadow-sm border-0">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light text-muted text-uppercase small font-weight-bold">
                                <tr>
                                    <th class="px-4 py-3">Fecha</th>
                                    <th class="px-4 py-3">Usuario</th>
                                    @if(!$material)
                                        <th class="px-4 py-3">Material</th>
                                    @endif
                                    <th class="px-4 py-3">Acción</th>
                                    <th class="px-4 py-3 text-end">Anterior</th>
                                    <th class="px-4 py-3 text-end">Nuevo</th>
                                    <th class="px-4 py-3 text-end">Cambio</th>
                                    <th class="px-4 py-3">Notas</th>
                                </tr>
                            </thead>
                            <tbody class="align-middle">
                                @forelse ($logs as $log)
                                    @php
                                        $diff = $log->new_stock_quantity - $log->previous_stock_quantity;
                                        $badgeClass = match($log->action) {
                                            'bodega_entry' => 'bg-success',
                                            'transfer' => 'bg-info text-dark',
                                            'bodega_adjustment' => 'bg-warning text-dark',
                                            default => 'bg-secondary'
                                        };
                                        $actionLabel = match($log->action) {
                                            'bodega_entry' => 'Ingreso',
                                            'transfer' => 'Transferencia',
                                            'bodega_adjustment' => 'Ajuste',
                                            default => $log->action
                                        };

                                        $details = [
                                            'fecha' => $log->created_at->format('d/m/Y H:i'),
                                            'usuario' => $log->user->name ?? 'Sistema',
                                            'material' => $log->material->name ?? 'Material Eliminado',
                                            'accion' => $actionLabel,
                                            'anterior' => number_format($log->previous_stock_quantity, 2),
                                            'nuevo' => number_format($log->new_stock_quantity, 2),
                                            'cambio' => ($diff > 0 ? '+' : '').number_format($diff, 2),
                                            'notas' => $log->notes ?? 'Sin notas'
                                        ];
                                    @endphp
                                    <tr class="cursor-pointer hover-bg-light" 
                                        onclick="showLogDetails(@json($details))"
                                        style="transition: background-color 0.2s;">
                                        <td class="px-4 py-3 text-muted small">
                                            {{ $log->created_at->format('d/m/Y H:i') }}
                                        </td>
                                        <td class="px-4 py-3">
                                            @php $userName = $log->user->name ?? 'Sistema'; @endphp
                                            @if(Str::length($userName) > 50)
                                                <span style="cursor: pointer;" class="text-primary" data-bs-toggle="modal"
                                                    data-bs-target="#userModal{{ $log->id }}">
                                                    {{ Str::limit($userName, 50) }}
                                                    <i class="bi bi-person-badge small ms-1"></i>
                                                </span>
                                            @else
                                                {{ $userName }}
                                            @endif
                                        </td>
                                        @if(!$material)
                                            <td class="px-4 py-3 font-weight-bold">
                                                @php $matName = $log->material->name ?? 'Material Eliminado'; @endphp
                                                @if(Str::length($matName) > 50)
                                                    <span style="cursor: pointer;" class="text-primary" data-bs-toggle="modal"
                                                        data-bs-target="#materialModal{{ $log->id }}">
                                                        {{ Str::limit($matName, 50) }}
                                                        <i class="bi bi-info-circle small ms-1"></i>
                                                    </span>
                                                @else
                                                    {{ $matName }}
                                                @endif
                                            </td>
                                        @endif
                                        <td class="px-4 py-3">
                                            <span class="badge {{ $badgeClass }} border-0 shadow-sm">
                                                {{ $actionLabel }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-end text-muted">
                                            {{ number_format($log->previous_stock_quantity, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-end font-weight-bold">
                                            {{ number_format($log->new_stock_quantity, 2) }}
                                        </td>
                                        <td class="px-4 py-3 text-end {{ $diff >= 0 ? 'text-success' : 'text-danger' }} font-weight-bold">
                                            {{ $diff > 0 ? '+' : '' }}{{ number_format($diff, 2) }}
                                        </td>
                                        <td class="px-4 py-3 small text-muted">
                                            @if(Str::length($log->notes ?? '') > 50)
                                                <span style="cursor: pointer;" data-bs-toggle="modal"
                                                    data-bs-target="#notesModal{{ $log->id }}">
                                                    {{ Str::limit($log->notes, 50) }}
                                                    <i class="bi bi-plus-circle text-primary small ms-1"></i>
                                                </span>
                                            @else
                                                {{ $log->notes ?? 'Sin notas' }}
                                            @endif
                                        </td>
                                    </tr>

                                    {{-- Modals for Long Text --}}
                                    @if(Str::length($log->material->name ?? 'Material Eliminado') > 50)
                                        <div class="modal fade" id="materialModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-scrollable">
                                                <div class="modal-content border-0 shadow">
                                                    <div class="modal-header bg-dark text-white border-0">
                                                        <h5 class="modal-title font-weight-bold">Nombre del Material</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4 text-start" style="max-height: 50vh; overflow-y: auto;">
                                                        <p class="mb-0 text-dark text-break"><span class="preserve-text">{{ $log->material->name }}</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if(Str::length($log->notes ?? '') > 50)
                                        <div class="modal fade" id="notesModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-scrollable">
                                                <div class="modal-content border-0 shadow">
                                                    <div class="modal-header bg-primary text-white border-0">
                                                        <h5 class="modal-title font-weight-bold">Nota Completa</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4 text-start" style="max-height: 60vh; overflow-y: auto;">
                                                        <p class="mb-0 text-dark text-break"><span class="preserve-text">{{ $log->notes }}</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @php $uName = $log->user->name ?? 'Sistema'; @endphp
                                    @if(Str::length($uName) > 50)
                                        <div class="modal fade" id="userModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog modal-dialog-scrollable">
                                                <div class="modal-content border-0 shadow">
                                                    <div class="modal-header bg-success text-white border-0">
                                                        <h5 class="modal-title font-weight-bold">Nombre del Usuario</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4 text-start" style="max-height: 50vh; overflow-y: auto;">
                                                        <p class="mb-0 text-dark text-break"><span class="preserve-text">{{ $uName }}</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($logs->hasPages())
                    <div class="card-footer bg-white border-top-0 py-3">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
    <!-- Log Detail Modal -->
    <div class="modal fade" id="logDetailModal" tabindex="-1" aria-labelledby="logDetailModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-dark text-white border-0 py-3">
                    <h5 class="modal-title fw-bold" id="logDetailModalLabel">
                        <i class="bi bi-info-circle me-2"></i>
                        {{ __('Detalles del Movimiento') }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" style="max-height: 70vh; overflow-y: auto;">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted small fw-bold text-uppercase">{{ __('Fecha') }}</span>
                            <span id="detailFecha" class="fw-bold"></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted small fw-bold text-uppercase">{{ __('Usuario') }}</span>
                            <span id="detailUsuario" class="fw-bold text-primary"></span>
                        </div>
                        <div class="list-group-item py-3">
                            <span class="text-muted small fw-bold text-uppercase d-block mb-1">{{ __('Material') }}</span>
                            <span id="detailMaterial" class="fw-bold d-block"></span>
                        </div>
                        <div class="list-group-item d-flex justify-content-between align-items-center py-3">
                            <span class="text-muted small fw-bold text-uppercase">{{ __('Acción') }}</span>
                            <span id="detailAccion" class="badge rounded-pill border-0 px-3 py-2"></span>
                        </div>
                        <div class="list-group-item p-0">
                            <div class="row g-0 text-center">
                                <div class="col-4 border-end py-3 bg-light">
                                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">{{ __('Anterior') }}</span>
                                    <span id="detailAnterior" class="fw-bold"></span>
                                </div>
                                <div class="col-4 border-end py-3">
                                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">{{ __('Nuevo') }}</span>
                                    <span id="detailNuevo" class="fw-bold h5 mb-0"></span>
                                </div>
                                <div class="col-4 py-3 bg-light">
                                    <span class="text-muted small fw-bold text-uppercase d-block mb-1">{{ __('Cambio') }}</span>
                                    <span id="detailCambio" class="fw-bold"></span>
                                </div>
                            </div>
                        </div>
                        <div class="list-group-item py-4">
                            <span class="text-muted small fw-bold text-uppercase d-block mb-2">{{ __('Notas / Observaciones') }}</span>
                            <p id="detailNotas" class="mb-0 text-dark bg-light p-3 rounded" style="white-space: pre-wrap;"></p>
                        </div>
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
            .cursor-pointer {
                cursor: pointer;
            }
            .hover-bg-light:hover {
                background-color: #f8f9fa !important;
            }
        </style>
    @endpush

    @push('scripts')
        <script>
            function showLogDetails(details) {
                document.getElementById('detailFecha').innerText = details.fecha;
                document.getElementById('detailUsuario').innerText = details.usuario;
                document.getElementById('detailMaterial').innerText = details.material;
                document.getElementById('detailAccion').innerText = details.accion;
                document.getElementById('detailAnterior').innerText = details.anterior;
                document.getElementById('detailNuevo').innerText = details.nuevo;
                document.getElementById('detailCambio').innerText = details.cambio;
                document.getElementById('detailNotas').innerText = details.notas;

                const cambioEl = document.getElementById('detailCambio');
                if (details.cambio.startsWith('+')) {
                    cambioEl.className = 'fw-bold text-success';
                } else if (details.cambio.startsWith('-')) {
                    cambioEl.className = 'fw-bold text-danger';
                } else {
                    cambioEl.className = 'fw-bold text-muted';
                }

                const accionEl = document.getElementById('detailAccion');
                accionEl.className = 'badge rounded-pill border-0 px-3 py-2 ';
                if (details.accion === 'Ingreso') accionEl.classList.add('bg-success');
                else if (details.accion === 'Transferencia') accionEl.classList.add('bg-info', 'text-dark');
                else if (details.accion === 'Ajuste') accionEl.classList.add('bg-warning', 'text-dark');
                else accionEl.classList.add('bg-secondary');

                const modal = new bootstrap.Modal(document.getElementById('logDetailModal'));
                modal.show();
            }
        </script>
    @endpush
</x-app-layout>
