{{-- Shared Image Lightbox Modal with Carousel --}}
<div class="modal fade" id="imageLightboxModal" tabindex="-1" aria-hidden="true" style="z-index: 99999;">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content bg-dark border-0 shadow-lg" style="min-height: 80vh;">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close btn-close-white ms-auto shadow-none" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 d-flex align-items-center justify-content-center"
                style="background: #000; position: relative;">
                <div id="lightboxCarousel" class="carousel slide" data-bs-interval="false" style="width: 100%;">
                    <div class="carousel-inner" id="carouselInner" style="width: 100%;">
                        {{-- Items injected via JS --}}
                    </div>
                    <button class="carousel-control-prev" type="button" data-bs-target="#lightboxCarousel"
                        data-bs-slide="prev" id="carouselPrev">
                        <span class="carousel-control-prev-icon p-4 bg-dark bg-opacity-75 rounded-circle"
                            aria-hidden="true"></span>
                        <span class="visually-hidden">Anterior</span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#lightboxCarousel"
                        data-bs-slide="next" id="carouselNext">
                        <span class="carousel-control-next-icon p-4 bg-dark bg-opacity-75 rounded-circle"
                            aria-hidden="true"></span>
                        <span class="visually-hidden">Siguiente</span>
                    </button>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0 justify-content-center">
                <div class="badge bg-secondary px-3 py-2 rounded-pill shadow-sm" id="carouselCounter"></div>
            </div>
        </div>
    </div>
</div>

<script>
    function openLightbox(images, startIndex = 0) {
        console.log('openLightbox called', { images, startIndex });
        if (!images || images.length === 0) {
            console.error('No images provided to openLightbox');
            return;
        }

        if (typeof images === 'string') {
            images = [images];
        }

        const index = parseInt(startIndex) || 0;
        const carouselInner = document.getElementById('carouselInner');
        const carouselEl = document.getElementById('lightboxCarousel');
        const modalEl = document.getElementById('imageLightboxModal');

        if (!carouselInner || !carouselEl || !modalEl) {
            console.error('Lightbox modal elements not found in DOM');
            return;
        }

        // Cleanup previous state
        const existingCarousel = bootstrap.Carousel.getInstance(carouselEl);
        if (existingCarousel) {
            existingCarousel.dispose();
        }

        carouselInner.innerHTML = '';

        images.forEach((url, i) => {
            const itemDiv = document.createElement('div');
            itemDiv.className = 'carousel-item' + (i === index ? ' active' : '');

            itemDiv.innerHTML = `
                <div class="d-flex justify-content-center align-items-center" style="width: 100%; height: 75vh; background: #000;">
                    <img src="${url}"
                         class="img-fluid rounded shadow-lg"
                         style="max-height: 100%; max-width: 100%; object-fit: contain;"
                         alt="Imagen ${i + 1}"
                         onerror="this.src='/img/madeiros.png'; console.warn('Failed to load image:', '${url}')">
                </div>`;
            carouselInner.appendChild(itemDiv);
        });

        const prevBtn = document.getElementById('carouselPrev');
        const nextBtn = document.getElementById('carouselNext');
        const counter = document.getElementById('carouselCounter');

        if (images.length <= 1) {
            if (prevBtn) prevBtn.classList.add('d-none');
            if (nextBtn) nextBtn.classList.add('d-none');
            if (counter) counter.innerText = '1 / 1';
        } else {
            if (prevBtn) prevBtn.classList.remove('d-none');
            if (nextBtn) nextBtn.classList.remove('d-none');
            if (counter) counter.innerText = (index + 1) + ' / ' + images.length;
        }

        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);

        // Initialize carousel
        new bootstrap.Carousel(carouselEl, {
            interval: false,
            wrap: true
        });

        // Counter logic
        const updateCounter = function (event) {
            if (counter) counter.innerText = (event.to + 1) + ' / ' + images.length;
        };
        carouselEl.removeEventListener('slide.bs.carousel', updateCounter);
        carouselEl.addEventListener('slide.bs.carousel', updateCounter);

        modal.show();
    }
</script>

<style>
    .cursor-zoom-in {
        cursor: zoom-in;
    }

    #imageLightboxModal .carousel-control-prev,
    #imageLightboxModal .carousel-control-next {
        width: 15%;
        opacity: 0.85;
    }

    #imageLightboxModal .carousel-item {
        background-color: #000;
        transition: transform 0.4s ease-in-out, opacity 0.2s ease-out;
    }

    /* Force display block on active carousel item just in case Bootstrap CSS is overridden or missing */
    #imageLightboxModal .carousel-item.active {
        display: block !important;
        opacity: 1 !important;
        visibility: visible !important;
        z-index: 10 !important;
    }
</style>