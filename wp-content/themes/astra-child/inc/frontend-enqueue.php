<?php
function enqueue_slick_slider() {
    if (is_front_page()) {
        wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css');
        wp_enqueue_style('slick-theme-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css');
        wp_enqueue_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', array('jquery'), '1.8.1', true);

        wp_add_inline_script('slick-js', '
            jQuery(document).ready(function($) {
                $(".cars-grid").slick({
                    slidesToShow: 3,
                    slidesToScroll: 1,
                    arrows: true,
                    dots: false,
                    infinite: true,
                    speed: 300,
                    adaptiveHeight: false,
                    responsive: [
                        { breakpoint: 1024, settings: { slidesToShow: 2 } },
                        { breakpoint: 768, settings: { slidesToShow: 1, arrows: false, dots: true } }
                    ]
                });
            });
        ');

        wp_add_inline_script('jquery', '
            jQuery(document).ready(function($) {
                var slider = $(".hero-slider");
                var slides = slider.children(".hero-slide");
                var total = slides.length;
                var current = 0;
                var interval;

                function goTo(index) {
                    if (index < 0) index = total - 1;
                    if (index >= total) index = 0;
                    current = index;
                    var offset = -current * 100;
                    slider.css("transform", "translateX(" + offset + "%)");
                    $(".hero-dots span").removeClass("active").eq(current).addClass("active");
                }

                // Create dots
                var dotsContainer = $(".hero-dots");
                dotsContainer.empty();
                for (var i = 0; i < total; i++) {
                    var dot = $("<span></span>");
                    dot.data("index", i);
                    dot.on("click", function() {
                        clearInterval(interval);
                        goTo($(this).data("index"));
                        startAutoplay();
                    });
                    dotsContainer.append(dot);
                }
                dotsContainer.children().first().addClass("active");

                // Arrows
                $(".hero-prev").on("click", function() {
                    clearInterval(interval);
                    goTo(current - 1);
                    startAutoplay();
                });
                $(".hero-next").on("click", function() {
                    clearInterval(interval);
                    goTo(current + 1);
                    startAutoplay();
                });

                // Autoplay
                function startAutoplay() {
                    clearInterval(interval);
                    if (total > 1) {
                        interval = setInterval(function() { goTo(current + 1); }, 5000);
                    }
                }
                startAutoplay();

                // Pause on hover
                $(".hero-slider-wrapper").on("mouseenter", function() { clearInterval(interval); });
                $(".hero-slider-wrapper").on("mouseleave", startAutoplay);

                // Recalculate on window resize (optional)
                $(window).on("resize", function() {
                    // no-op: flex handles it
                });
            });
        ');
    }
}
add_action('wp_enqueue_scripts', 'enqueue_slick_slider');