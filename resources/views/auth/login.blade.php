<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}">
        @csrf

        <!-- Document -->
        <div>
            <x-input-label for="document" :value="__('Documento')" />
            <input id="document" type="text" name="document" value="{{ old('document') }}" 
                class="form-control mt-1 bg-light border-gray-300 shadow-none focus:ring-0 focus:border-gray-300 @error('document') is-invalid @enderror"
                style="background-color: #fcfcfc !important;"
                required autofocus autocomplete="username">
            <x-input-error :messages="$errors->get('document')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" value="Contraseña" />

            <div class="input-group mt-1">
                <input id="password" type="password" name="password" required
                    autocomplete="current-password" 
                    class="form-control bg-light border-gray-300 shadow-none focus:ring-0 focus:border-gray-300 @error('password') is-invalid @enderror"
                    style="background-color: #fcfcfc !important;">
                <button class="btn btn-outline-secondary border-gray-300" type="button" id="togglePassword">
                    <i class="bi bi-eye text-gray-500" id="eyeIcon"></i>
                </button>
            </div>

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <script>
            document.getElementById('togglePassword').addEventListener('click', function() {
                const passwordInput = document.getElementById('password');
                const eyeIcon = document.getElementById('eyeIcon');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    eyeIcon.classList.remove('bi-eye');
                    eyeIcon.classList.add('bi-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    eyeIcon.classList.remove('bi-eye-slash');
                    eyeIcon.classList.add('bi-eye');
                }
            });
        </script>



        <div class="flex items-center justify-center my-4">


            <x-primary-button class="ms-3">
                {{ __('Log in') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>