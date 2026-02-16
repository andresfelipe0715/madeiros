<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">
                {{ __('Clientes') }}
            </h2>
            @can('create-clients')
                <a href="{{ route('clients.create') }}" class="btn btn-primary">
                    {{ __('Nuevo Cliente') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-fluid px-5">
            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light text-muted text-uppercase small font-weight-bold">
                                <tr>
                                    <th class="px-4 py-3">ID</th>
                                    <th class="px-4 py-3">Nombre</th>
                                    <th class="px-4 py-3">Documento</th>
                                    <th class="px-4 py-3">Teléfono</th>
                                    <th class="px-4 py-3 text-nowrap">Fecha Creación</th>
                                    @can('edit-clients')
                                        <th class="px-4 py-3 text-center">Acciones</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody class="align-middle">
                                @forelse($clients as $client)
                                    <tr>
                                        <td class="px-4 py-3 text-muted">#{{ $client->id }}</td>
                                        <td class="px-4 py-3 font-weight-bold">{{ Str::limit($client->name, 50) }}</td>
                                        <td class="px-4 py-3">{{ Str::limit($client->document, 50) }}</td>
                                        <td class="px-4 py-3 text-nowrap">{{ $client->phone ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-nowrap">
                                            {{ $client->created_at->format('d/m/Y H:i') }}
                                        </td>
                                        @can('edit-clients')
                                            <td class="px-4 py-3 text-center">
                                                <a href="{{ route('clients.edit', $client) }}"
                                                    class="btn btn-sm btn-outline-primary" title="Editar Cliente">
                                                    <i class="fas fa-edit"></i> Editar
                                                </a>
                                            </td>
                                        @endcan
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-5 text-center text-muted">
                                            No se encontraron clientes.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($clients->hasPages())
                    <div class="card-footer bg-white border-top-0 py-3">
                        {{ $clients->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>