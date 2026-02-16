<x-app-layout>
    <x-slot name="header">
        <div class="d-flex justify-content-between align-items-center">
            <h2 class="h4 font-weight-bold mb-0">
                {{ __('Editar Cliente') }}
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
                            <h5 class="card-title mb-0">Información del Cliente: {{ $client->name }}</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('clients.update', $client) }}" method="POST">
                                @csrf
                                @method('PUT')

                                @php
                                    $hasOrders = $client->orders()->exists();
                                @endphp

                                <div class="mb-3">
                                    <label for="name"
                                        class="form-label text-muted small text-uppercase font-weight-bold">Nombre
                                        Completo</label>
                                    <input type="text" class="form-control @error('name') is-invalid @enderror"
                                        id="name" name="name" value="{{ old('name', $client->name) }}" required>
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="document"
                                        class="form-label text-muted small text-uppercase font-weight-bold">Documento /
                                        NIT</label>
                                    <input type="text" class="form-control @error('document') is-invalid @enderror"
                                        id="document" name="document" value="{{ old('document', $client->document) }}"
                                        required {{ $hasOrders ? 'disabled' : '' }}>
                                    @if($hasOrders)
                                        <div class="form-text text-danger small fw-bold">
                                            <i class="bi bi-info-circle me-1"></i>
                                            El documento no se puede modificar porque este cliente ya tiene órdenes
                                            asociadas.
                                        </div>
                                    @endif
                                    @error('document')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="phone"
                                        class="form-label text-muted small text-uppercase font-weight-bold">Teléfono
                                        (Opcional)</label>
                                    <input type="text" class="form-control @error('phone') is-invalid @enderror"
                                        id="phone" name="phone" value="{{ old('phone', $client->phone) }}">
                                    @error('phone')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="d-grid mt-4">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Actualizar Cliente') }}
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