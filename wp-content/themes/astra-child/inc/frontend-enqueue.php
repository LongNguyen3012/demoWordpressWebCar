<?php
/**
 * Frontend Enqueue slick slider
 */
function enqueue_slick_slider() {
    if (is_front_page()) {
        wp_enqueue_style('slick-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.css');
        wp_enqueue_style('slick-theme-css', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick-theme.css');
        wp_enqueue_script('slick-js', 'https://cdn.jsdelivr.net/npm/slick-carousel@1.8.1/slick/slick.min.js', array('jquery'), '1.8.1', true);

        wp_add_inline_script('slick-js', '
            jQuery(window).on("load", function() {
                // Helper to force hero slider to viewport width
                function setHeroWidths() {
                    var winWidth = jQuery(window).width();
                    jQuery(".hero-slider .slick-slide").css("width", winWidth + "px");
                    jQuery(".hero-slider .slick-list").css("width", winWidth + "px");
                    var slideCount = jQuery(".hero-slider .slick-slide").length;
                    jQuery(".hero-slider .slick-track").css("width", (winWidth * slideCount) + "px");
                    jQuery(".hero-slider").slick("setPosition");
                }

                // Init hero slider
                jQuery(".hero-slider").slick({
                    slidesToShow: 1,
                    slidesToScroll: 1,
                    arrows: true,
                    dots: false,
                    infinite: true,
                    speed: 500,
                    fade: true,
                    cssEase: "linear",
                    autoplay: true,
                    autoplaySpeed: 5000,
                    pauseOnHover: true,
                    adaptiveHeight: false
                });

                // Force widths after init and on resize
                setTimeout(setHeroWidths, 50);
                jQuery(window).on("resize", setHeroWidths);

                // Cars grid (no width issues)
                jQuery(".cars-grid").slick({
                    slidesToShow: 3,
                    slidesToScroll: 1,
                    arrows: true,
                    dots: false,
                    infinite: true,
                    speed: 300,
                    adaptiveHeight: false,
                    responsive: [
                        {
                            breakpoint: 1024,
                            settings: { slidesToShow: 2 }
                        },
                        {
                            breakpoint: 768,
                            settings: { slidesToShow: 1, arrows: false, dots: true }
                        }
                    ]
                });
            });
        ');
    }
}
add_action('wp_enqueue_scripts', 'enqueue_slick_slider');