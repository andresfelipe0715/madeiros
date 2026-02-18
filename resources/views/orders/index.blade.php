<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
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
        <div class="container-fluid px-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <form action="{{ route('orders.index') }}" method="GET" class="d-flex align-items-center">
                    <div class="input-group shadow-sm border rounded-pill overflow-hidden bg-white search-pill"
                        style="width:350px;">
                        <span class="input-group-text bg-white border-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-0 py-2 shadow-none"
                            placeholder="Buscar por factura o nombre de cliente..." value="{{ request('search') }}"
                            onkeyup="debounceSubmit(this.form)">
                    </div>

                    @if(request('search'))
                        <a href="{{ route('orders.index') }}"
                            class="btn btn-link btn-sm text-decoration-none text-muted ms-2">Limpiar</a>
                    @endif
                </form>
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
                                        <td class="px-4 py-3">{{ Str::limit($order->client->name, 50) }}</td>
                                        <td class="px-4 py-3 text-nowrap">{{ $order->creator_name }}</td>
                                        <td class="px-4 py-3">{{ $order->invoice_number }}</td>
                                        <td class="px-4 py-3">{{ Str::limit($order->material, 50) }}</td>
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
                                        <td colspan="9" class="px-4 py-5 text-center text-muted">
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
    </style>
</x-app-layout>