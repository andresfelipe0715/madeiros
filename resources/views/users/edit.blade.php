<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">
                {{ __('Editar Usuario') }}: {{ $user->name }}
            </h2>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </x-slot>

    @php
        $hasOrders = \App\Models\Order::where('created_by', $user->id)
            ->orWhere('delivered_by', $user->id)
            ->orWhere('herrajeria_delivered_by', $user->id)
            ->orWhere('manual_armado_delivered_by', $user->id)
            ->exists();
            
        $hasOrderStages = \App\Models\OrderStage::where('started_by', $user->id)
            ->orWhere('completed_by', $user->id)
            ->orWhere('pending_marked_by', $user->id)
            ->exists();

        $isDocumentLocked = $hasOrders || $hasOrderStages;
    @endphp

    <div class="py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <form action="{{ route('users.update', $user) }}" method="POST">
                                @csrf
                                @method('PUT')

                                <div class="mb-3">
                                    <label for="name" class="form-label font-weight-bold">Nombre Completo</label>
                                    <input type="text" name="name" id="name" maxlength="150"
                                        class="form-control @error('name') is-invalid @enderror" 
                                        value="{{ old('name', $user->name) }}" required autofocus>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text text-muted small">Máximo 150 caracteres.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="document" class="form-label font-weight-bold">
                                        Documento / Identificación
                                        @if($isDocumentLocked)
                                            <i class="bi bi-lock-fill text-muted ms-1" title="Campo bloqueado por trazabilidad"></i>
                                        @endif
                                    </label>
                                    <input type="text" name="document" id="document" maxlength="50"
                                        class="form-control @error('document') is-invalid @enderror" 
                                        value="{{ old('document', $user->document) }}" 
                                        {{ $isDocumentLocked ? 'readonly' : '' }} required>
                                    @if($isDocumentLocked)
                                        <input type="hidden" name="document" value="{{ $user->document }}">
                                        <div class="form-text text-warning small">
                                            <i class="bi bi-exclamation-triangle"></i> El documento no se puede modificar porque este usuario ya tiene órdenes asociadas.
                                        </div>
                                    @else
                                        <div class="form-text text-muted small">Máximo 50 caracteres.</div>
                                    @endif
                                    @error('document')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label font-weight-bold mb-0">Contraseña</label>
                                        <button type="button" class="btn btn-sm btn-outline-warning rounded-pill" 
                                            data-bs-toggle="modal" data-bs-target="#changePasswordModal">
                                            <i class="bi bi-key-fill"></i> Cambiar Contraseña
                                        </button>
                                    </div>
                                    <input type="text" class="form-control bg-light" value="********" readonly disabled>
                                    <div class="form-text text-muted small">La contraseña está encriptada por seguridad.</div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="role_id" class="form-label font-weight-bold">Rol</label>
                                        <select name="role_id" id="role_id" class="form-select @error('role_id') is-invalid @enderror" required>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                                                    {{ $role->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @error('role_id')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-6">
                                        <label for="active" class="form-label font-weight-bold">Estado</label>
                                        <select name="active" id="active" class="form-select @error('active') is-invalid @enderror" required>
                                            <option value="1" {{ old('active', $user->active) ? 'selected' : '' }}>Activo</option>
                                            <option value="0" {{ !old('active', $user->active) ? 'selected' : '' }}>Inactivo</option>
                                        </select>
                                        @error('active')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary py-2 rounded-pill font-weight-bold">
                                        Guardar Cambios
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Change Password Modal --}}
    <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content border-0 shadow">
                <form action="{{ route('users.update', $user) }}" method="POST">
                    @csrf
                    @method('PUT')
                    {{-- Carry over current required data for partial update --}}
                    <input type="hidden" name="name" value="{{ $user->name }}">
                    <input type="hidden" name="document" value="{{ $user->document }}">
                    <input type="hidden" name="role_id" value="{{ $user->role_id }}">
                    <input type="hidden" name="active" value="{{ $user->active }}">

                    <div class="modal-header bg-dark text-white border-0">
                        <h5 class="modal-title">Cambiar Contraseña</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label for="modal_password" class="form-label font-weight-bold">Nueva Contraseña</label>
                            <input type="password" name="password" id="modal_password" minlength="6" maxlength="30" 
                                placeholder="Contraseña"
                                class="form-control @error('password') is-invalid @enderror" required>
                            @error('password')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text text-muted small">Mínimo 6, máximo 30 caracteres.</div>
                        </div>
                        <div class="mb-0">
                            <label for="modal_password_confirmation" class="form-label font-weight-bold">Confirmar Nueva Contraseña</label>
                            <input type="password" name="password_confirmation" id="modal_password_confirmation" 
                                placeholder="Confirmar Nueva Contraseña" maxlength="30"
                                class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer border-0">
                        <button type="button" class="btn btn-light rounded-pill px-4" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning rounded-pill px-4">Actualizar Contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @if ($errors->has('password'))
        <script>
            window.onload = () => {
                new bootstrap.Modal(document.getElementById('changePasswordModal')).show();
            };
        </script>
    @endif

</x-app-layout>
