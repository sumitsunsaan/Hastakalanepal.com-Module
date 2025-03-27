document.addEventListener('DOMContentLoaded', function() {
    // Product click tracking
    document.querySelectorAll('.cvp-block .product-miniature a').forEach(link => {
        link.addEventListener('click', function(e) {
            if(typeof gtag !== 'undefined') {
                gtag('event', 'product_click', {
                    'event_category': 'Recommendations',
                    'event_label': this.dataset.productId
                });
            }
        });
    });

    // Carousel touch devices optimization
    if(window.matchMedia("(hover: none)").matches) {
        document.querySelectorAll('.cvp-carousel').forEach(carousel => {
            carousel.classList.add('owl-touch');
        });
    }
});