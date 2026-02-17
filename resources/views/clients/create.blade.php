<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">
                {{ __('Nuevo Cliente') }}
            </h2>
            <a href="{{ route('clients.index') }}" class="btn btn-secondary">
                {{ __('Volver') }}
            </a>
        </div>
    </x-slot>

    <div class="py-4">
        <div class="container text-start">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white py-3">
                            <h5 class="card-title mb-0">Información del Cliente</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('clients.store') }}" method="POST" novalidate>
                                @csrf

                                <div class="mb-3">
                                    <label for="name"
                                        class="form-label text-muted small text-uppercase font-weight-bold">Nombre
                                        Completo</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name') }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="document"
                                        class="form-label text-muted small text-uppercase font-weight-bold">Documento /
                                        NIT</label>
                                    <input type="text" class="form-control @error('document') is-invalid @enderror"
                                        id="document" name="document" value="{{ old('document') }}" required>
                                    @error('document')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="phone"
                                        class="form-label text-muted small text-uppercase font-weight-bold">Teléfono
                                        (Opcional)</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                        id="phone" name="phone" value="{{ old('phone') }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Guardar Cliente') }}
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