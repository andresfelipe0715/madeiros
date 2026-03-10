<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">
                {{ __('Nuevo Servicio Especial') }}
            </h2>
            <a href="{{ route('special-services.index') }}" class="btn btn-secondary">
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
                            <h5 class="card-title mb-0">Información del Servicio</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('special-services.store') }}" method="POST" novalidate>
                                @csrf

                                <div class="mb-3">
                                    <label for="name"
                                        class="form-label text-muted small text-uppercase font-weight-bold">Nombre del
                                        Servicio</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name') }}" required autofocus
                                        maxlength="255">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="active" name="active"
                                            value="1" {{ old('active', true) ? 'checked' : '' }}>
                                        <label class="form-check-label text-muted small text-uppercase font-weight-bold"
                                            for="active">
                                            Servicio Activo
                                        </label>
                                    </div>
                                    @error('active')
                                        <div class="text-danger small mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Guardar Servicio') }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('styles')
        <style>
            .form-switch .form-check-input {
                background-size: 1rem 1rem !important;
                background-position: left center !important;
            }

            .form-switch .form-check-input:checked {
                background-position: right center !important;
            }
        </style>
    @endpush
</x-app-layout>