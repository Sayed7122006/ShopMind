const browse = document.querySelector('.category_btn');
const menu = document.querySelector('.category_dropdown .nav_links');

browse.addEventListener('click', function(e) {
    e.stopPropagation();

    if (menu.style.display === 'flex') {
        menu.style.display = 'none';
    } else {
        menu.style.display = 'flex';
        menu.style.flexDirection = 'column';
    }
});

document.addEventListener('click', function() {
    menu.style.display = 'none';
});
var swiper = new Swiper('.mySwiper', {
    pagination: {
        el: '.swiper-pagination',
        clickable: true,
    },

    autoplay: {
        delay: 2000,
    },
});