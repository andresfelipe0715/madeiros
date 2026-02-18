<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">
                {{ __('Usuarios') }}
            </h2>
            @can('create-users')
                <a href="{{ route('users.create') }}" class="btn btn-primary">
                    {{ __('Nuevo Usuario') }}
                </a>
            @endcan
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container-fluid px-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <form action="{{ route('users.index') }}" method="GET" class="d-flex align-items-center">
                    <div class="input-group shadow-sm border rounded-pill overflow-hidden bg-white search-pill"
                        style="width: 350px; transition: border-color 0.2s ease-in-out;">
                        <span class="input-group-text bg-white border-0">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-0 py-2 shadow-none"
                            placeholder="Buscar por nombre o documento..." value="{{ request('search') }}"
                            onkeyup="debounceSubmit(this.form)"
                            onfocus="this.parentElement.style.borderColor = '#0d6efd'"
                            onblur="this.parentElement.style.borderColor = '#dee2e6'">
                    </div>
                    @if(request('search'))
                        <a href="{{ route('users.index') }}"
                            class="btn btn-link btn-sm text-decoration-none text-muted ms-2">Limpiar</a>
                    @endif
                </form>
                <div class="text-muted small">
                    Mostrando {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} de
                    {{ $users->total() }} usuarios
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light text-muted text-uppercase small font-weight-bold">
                                <tr>
                                    <th class="px-4 py-3">ID</th>
                                    <th class="px-4 py-3">Nombre</th>
                                    <th class="px-4 py-3">Documento</th>
                                    <th class="px-4 py-3">Rol</th>
                                    <th class="px-4 py-3 text-center">Estado</th>
                                    @can('edit-users')
                                        <th class="px-4 py-3 text-center">Acciones</th>
                                    @endcan
                                </tr>
                            </thead>
                            <tbody class="align-middle">
                                @forelse($users as $user)
                                    <tr>
                                        <td class="px-4 py-3 text-muted">#{{ $user->id }}</td>
                                        <td class="px-4 py-3 font-weight-bold">{{ $user->name }}</td>
                                        <td class="px-4 py-3 text-nowrap">{{ $user->document }}</td>
                                        <td class="px-4 py-3 text-nowrap">
                                            <span class="badge bg-secondary opacity-75">{{ $user->role->name }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-center">
                                            @if($user->active)
                                                <span class="badge bg-success rounded-pill px-3">Activo</span>
                                            @else
                                                <span class="badge bg-danger rounded-pill px-3">Inactivo</span>
                                            @endif
                                        </td>
                                        @can('edit-users')
                                            <td class="px-4 py-3 text-center">
                                                <div class="d-flex justify-content-center gap-2">
                                                    <a href="{{ route('users.edit', $user) }}"
                                                        class="btn btn-sm btn-outline-primary" title="Editar Usuario">
                                                        <i class="fas fa-edit me-1"></i> Editar
                                                    </a>
                                                    <button type="button" class="btn btn-sm btn-outline-info"
                                                        data-bs-toggle="modal" data-bs-target="#userModal{{ $user->id }}"
                                                        title="Ver Detalles">
                                                        <i class="fas fa-eye me-1"></i> Detalles
                                                    </button>
                                                </div>
                                            </td>
                                        @endcan
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-5 text-center text-muted">
                                            No se encontraron usuarios.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($users->hasPages())
                    <div class="card-footer bg-white border-top-0 py-3">
                        {{ $users->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Details Modals --}}
    @foreach($users as $u)
        <div class="modal fade" id="userModal{{ $u->id }}" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content border-0 shadow">
                    <div class="modal-header bg-dark text-white border-0">
                        <h5 class="modal-title">Detalles del Usuario - ID #{{ $u->id }}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4 text-start">
                        <div class="mb-3">
                            <label class="text-muted small text-uppercase font-weight-bold">Nombre Completo</label>
                            <p class="mb-0 text-dark">{{ $u->name }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small text-uppercase font-weight-bold">Documento</label>
                            <p class="mb-0 text-dark">{{ $u->document }}</p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small text-uppercase font-weight-bold">Rol</label>
                            <p class="mb-0">
                                <span class="badge bg-secondary">{{ $u->role->name }}</span>
                            </p>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted small text-uppercase font-weight-bold">Estado</label>
                            <p class="mb-0">
                                @if($u->active)
                                    <span class="badge bg-success rounded-pill px-3">Activo</span>
                                @else
                                    <span class="badge bg-danger rounded-pill px-3">Inactivo</span>
                                @endif
                            </p>
                        </div>
                        <div class="mb-0">
                            <label class="text-muted small text-uppercase font-weight-bold">Fecha de Registro</label>
                            <p class="mb-0 text-dark">{{ $u->created_at->format('d/m/Y H:i') }}</p>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light rounded-pill px-4"
                            data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    <style>
        .search-pill input {
            padding-left: 1rem;
            outline: none;
        }

        .search-pill .input-group-text {
            padding-left: 0rem;
            padding-right: 0rem;
        }
    </style>

    <script>
        let timer;
        function debounceSubmit(form) {
            clearTimeout(timer);
            timer = setTimeout(() => {
                form.submit();
            }, 500);
        }
    </script>

</x-app-layout>