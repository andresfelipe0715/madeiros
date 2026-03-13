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
                                    @endphp
                                    <tr>
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
                                                @if(Str::length($log->material->name) > 50)
                                                    <span style="cursor: pointer;" class="text-primary" data-bs-toggle="modal"
                                                        data-bs-target="#materialModal{{ $log->id }}">
                                                        {{ Str::limit($log->material->name, 50) }}
                                                        <i class="bi bi-info-circle small ms-1"></i>
                                                    </span>
                                                @else
                                                    {{ $log->material->name }}
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
                                            @if(Str::length($log->notes) > 50)
                                                <span style="cursor: pointer;" data-bs-toggle="modal"
                                                    data-bs-target="#notesModal{{ $log->id }}">
                                                    {{ Str::limit($log->notes, 50) }}
                                                    <i class="bi bi-plus-circle text-primary small ms-1"></i>
                                                </span>
                                            @else
                                                {{ $log->notes }}
                                            @endif
                                        </td>
                                    </tr>

                                    {{-- Modals for Long Text --}}
                                    @if(Str::length($log?->material?->name) > 50)
                                        <div class="modal fade" id="materialModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content border-0 shadow">
                                                    <div class="modal-header bg-dark text-white border-0">
                                                        <h5 class="modal-title font-weight-bold">Nombre del Material</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4 text-start">
                                                        <p class="mb-0 text-dark text-break"><span class="preserve-text">{{ $log->material->name }}</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if(Str::length($log->notes) > 50)
                                        <div class="modal fade" id="notesModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content border-0 shadow">
                                                    <div class="modal-header bg-primary text-white border-0">
                                                        <h5 class="modal-title font-weight-bold">Nota Completa</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4 text-start">
                                                        <p class="mb-0 text-dark text-break"><span class="preserve-text">{{ $log->notes }}</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    @if(Str::length($log?->user?->name ?? 'Sistema') > 50)
                                        <div class="modal fade" id="userModal{{ $log->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content border-0 shadow">
                                                    <div class="modal-header bg-success text-white border-0">
                                                        <h5 class="modal-title font-weight-bold">Nombre del Usuario</h5>
                                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body p-4 text-start">
                                                        <p class="mb-0 text-dark text-break"><span class="preserve-text">{{ $log->user->name ?? 'Sistema' }}</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @empty
                                    <tr>
                                        <td colspan="{{ $material ? 7 : 8 }}" class="px-4 py-5 text-center text-muted">
                                            No hay registros de movimientos para este material.
                                        </td>
                                    </tr>
                                @endforelse
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
</x-app-layout>
