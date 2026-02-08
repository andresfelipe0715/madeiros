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

    <style>
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
    </style>
</x-app-layout>
