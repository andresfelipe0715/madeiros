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

    @php
        $stageMappings = [
            'corte' => ['color' => '#64748b', 'bg' => 'rgba(100, 116, 139, 0.04)'],
            'enchape' => ['color' => '#3b82f6', 'bg' => 'rgba(59, 130, 246, 0.04)'],
            'servicios especiales' => ['color' => '#6366f1', 'bg' => 'rgba(99, 102, 241, 0.04)'],
            'revision' => ['color' => '#f59e0b', 'bg' => 'rgba(245, 158, 11, 0.04)'],
            'entrega' => ['color' => '#10b981', 'bg' => 'rgba(16, 185, 129, 0.04)'],
        ];
        $defaultMapping = ['color' => '#94a3b8', 'bg' => 'rgba(148, 163, 184, 0.04)'];
    @endphp

    <div class="py-12">
        <div class="container">
            <div class="row g-4">
                @forelse($accessibleStages as $stage)
                    @php
                        $normName = strtolower($stage->name);
                        $mapping = $stageMappings[$normName] ?? $defaultMapping;
                    @endphp
                    <div class="col-md-4 col-lg-3">
                        <a href="{{ route('dashboard.stage', $stage->id) }}" class="text-decoration-none h-100 d-block">
                            <div class="card h-100 border-0 shadow-sm-modern hover-lift transition-all bg-white overflow-hidden"
                                style="border-left: 4px solid {{ $mapping['color'] }} !important;">
                                <div class="card-body p-4 d-flex flex-column">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div class="px-2 py-1 rounded small font-weight-bold shadow-xs border"
                                            style="color: {{ $mapping['color'] }}; background-color: {{ $mapping['bg'] }}; border-color: rgba(0,0,0,0.05) !important;">
                                            Módulo
                                        </div>
                                        <i class="bi bi-arrow-right text-muted opacity-50"></i>
                                    </div>
                                    <h5 class="font-weight-bold text-gray-900 mb-2">{{ $stage->name }}</h5>
                                    <p class="text-secondary small mb-4">Producción y control de
                                        {{ strtolower($stage->name) }}
                                    </p>
                                    <div
                                        class="mt-auto pt-3 border-top border-light d-flex justify-content-between align-items-center">
                                        <span class="small font-weight-bold" style="color: {{ $mapping['color'] }}">
                                            Gestionar Etapa
                                        </span>
                                        <i class="bi bi-chevron-right small text-muted"></i>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                @empty
                    <div class="col-12 text-center py-5">
                        <div class="mb-3">
                            <i class="bi bi-folder2-open display-4 text-muted opacity-50"></i>
                        </div>
                        <h5 class="text-secondary font-weight-bold mb-2">Sin módulos asignados</h5>
                        <p class="text-muted small">Contacta al administrador para habilitar tus permisos de producción.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>

    <style>
        :root {
            --bs-primary: #0f172a;
            --bs-primary-rgb: 15, 23, 42;
        }

        .shadow-sm-modern {
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.05), 0 1px 2px 0 rgba(0, 0, 0, 0.03) !important;
        }

        .shadow-xs {
            box-shadow: 0 1px 2px 0 rgba(0, 0, 0, 0.05) !important;
        }

        .hover-lift {
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02) !important;
            filter: contrast(1.02);
        }

        .font-weight-bold {
            font-weight: 600 !important;
        }

        .font-weight-bolder {
            font-weight: 700 !important;
        }
    </style>
</x-app-layout>