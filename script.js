// Mobile Menu Toggle
let menu = document.querySelector('#menu-btn');
let navbar = document.querySelector('.header .navbar');

if(menu && navbar) {
    menu.onclick = () => {
        menu.classList.toggle('fa-times');
        navbar.classList.toggle('active');
    }
    
    window.onscroll = () => {
        menu.classList.remove('fa-times');
        navbar.classList.remove('active');
    }
}

// Home Slider
var homeSwiper = new Swiper(".home-slider", {
    loop: true,
    spaceBetween: 0,
    grabCursor: true,
    centeredSlides: true,
    autoplay: {
        delay: 5000,
        disableOnInteraction: false,
    },
    pagination: {
        el: ".home-slider .swiper-pagination",
        clickable: true,
    },
    effect: 'fade',
    fadeEffect: {
        crossFade: true
    }
});

// Services Slider
var serviceSwiper = new Swiper(".service-slider", {
    loop: true,
    spaceBetween: 20,
    grabCursor: true,
    centeredSlides: false,
    autoplay: {
        delay: 3000,
        disableOnInteraction: false,
    },
    pagination: {
        el: ".service-slider .swiper-pagination",
        clickable: true,
    },
    breakpoints: {
        0: {
            slidesPerView: 1,
            spaceBetween: 10,
        },
        450: {
            slidesPerView: 1,
            spaceBetween: 15,
        },
        768: {
            slidesPerView: 2,
            spaceBetween: 20,
        },
        1000: {
            slidesPerView: 3,
            spaceBetween: 20,
        },
    }
});

// Initialize both sliders when DOM is loaded
document.addEventListener('DOMContentLoaded', function() {
    // Add click event to close menu when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.header') && !e.target.closest('#menu-btn')) {
            if(menu && navbar) {
                menu.classList.remove('fa-times');
                navbar.classList.remove('active');
            }
        }
    });
});




var reviewsSwiper = new Swiper(".reviews-slider", {
    loop: true,
    spaceBetween: 20,
    grabCursor: true,
    centeredSlides: false,
    autoplay: {
        delay: 3000,
        disableOnInteraction: false,
    },
    pagination: {
        el: ".reviews-slider .swiper-pagination",
        clickable: true,
    },
    breakpoints: {
        0: {
            slidesPerView: 1,
            spaceBetween: 10,
        },
        450: {
            slidesPerView: 1,
            spaceBetween: 15,
        },
        768: {
            slidesPerView: 2,
            spaceBetween: 20,
        },
        1000: {
            slidesPerView: 3,
            spaceBetween: 20,
        },
    }
});
