<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">
                {{ __('Nuevo Usuario') }}
            </h2>
            <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Volver
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-body p-4">
                            <form action="{{ route('users.store') }}" method="POST">
                                @csrf

                                <div class="mb-3">
                                    <label for="name" class="form-label font-weight-bold">Nombre Completo</label>
                                    <input type="text" name="name" id="name" maxlength="150"
                                        class="form-control @error('name') is-invalid @enderror"
                                        value="{{ old('name') }}" required autofocus>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text text-muted small">Máximo 150 caracteres.</div>
                                </div>

                                <div class="mb-3">
                                    <label for="document" class="form-label font-weight-bold">Documento /
                                        Identificación</label>
                                    <input type="text" name="document" id="document" maxlength="50"
                                        class="form-control @error('document') is-invalid @enderror"
                                        value="{{ old('document') }}" required>
                                    @error('document')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text text-muted small">Máximo 50 caracteres.</div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="password" class="form-label font-weight-bold">Contraseña</label>
                                        <input type="password" name="password" id="password" minlength="6"
                                            maxlength="30" placeholder="Contraseña"
                                            class="form-control @error('password') is-invalid @enderror" required>
                                        @error('password')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                        <div class="form-text text-muted small">Mínimo 6, máximo 30 caracteres.</div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="password_confirmation" class="form-label font-weight-bold">Confirmar
                                            Contraseña</label>
                                        <input type="password" name="password_confirmation" id="password_confirmation"
                                            placeholder="Confirmar Contraseña" maxlength="30" class="form-control"
                                            required>
                                    </div>
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="role_id" class="form-label font-weight-bold">Rol</label>
                                        <select name="role_id" id="role_id"
                                            class="form-select @error('role_id') is-invalid @enderror" required>
                                            <option value="">Seleccione un rol...</option>
                                            @foreach($roles as $role)
                                                <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
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
                                        <select name="active" id="active"
                                            class="form-select @error('active') is-invalid @enderror" required>
                                            <option value="1" {{ old('active', '1') == '1' ? 'selected' : '' }}>Activo
                                            </option>
                                            <option value="0" {{ old('active') == '0' ? 'selected' : '' }}>Inactivo
                                            </option>
                                        </select>
                                        @error('active')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary py-2 rounded-pill font-weight-bold">
                                        Crear Usuario
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>