<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center w-100">
            <div>
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Módulos de Producción') }}
                </h2>
                <p class="text-sm text-secondary mb-0">Seleccione un módulo para gestionar los pedidos en esa etapa.</p>
            </div>
            @if($isAdmin)
                <a href="{{ route('orders.create') }}" class="btn btn-primary rounded-pill px-4 shadow-sm">
                    <i class="bi bi-plus-circle me-1"></i> Nueva Orden
                </a>
            @endif
        </div>
    </x-slot>

    <div class="py-12">
        <div class="container">
            <div class="row g-4">
                @forelse($accessibleStages as $stage)
                    <div class="col-md-4 col-lg-3">
                        <a href="{{ route('dashboard.stage', $stage->id) }}" class="text-decoration-none">
                            <div class="card h-100 border-0 shadow-sm hover-elevate transition-all">
                                <div class="card-body text-center p-4">
                                    <div class="module-icon mb-3">
                                        <div class="bg-soft-primary text-primary rounded-circle d-inline-flex align-items-center justify-content-center"
                                            style="width: 64px; height: 64px;">
                                            <i class="bi bi-gear-fill fs-3"></i>
                                        </div>
                                    </div>
                                    <h5 class="font-weight-bolder mb-1">{{ $stage->name }}</h5>
                                    <p class="text-muted small mb-0">Gestionar flujo de {{ strtolower($stage->name) }}</p>
                                </div>
                                <div class="card-footer bg-white border-0 text-center pb-4">
                                    <span class="btn btn-primary btn-sm rounded-pill px-4">Entrar</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="alert alert-info">
                            No tienes módulos de producción asignados. Contacta al administrador.
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <style>
        .hover-elevate:hover {
            transform: translateY(-5px);
            box-shadow: 0 1rem 3rem rgba(0, 0, 0, 0.1) !important;
        }

        .transition-all {
            transition: all 0.3s ease;
        }

        .bg-soft-primary {
            background-color: rgba(13, 110, 253, 0.1);
        }
    </style>
</x-app-layout>