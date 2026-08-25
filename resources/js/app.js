import axios from 'axios';

window.axios = axios;
window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

document.querySelectorAll('[data-dg-carousel]').forEach((carousel) => {
    const slides = Array.from(carousel.querySelectorAll('[data-dg-slide]'));
    const buttons = Array.from(carousel.querySelectorAll('[data-dg-slide-button]'));
    const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    let activeIndex = 0;
    let timer;

    const show = (index) => {
        activeIndex = index;
        slides.forEach((slide, slideIndex) => {
            const active = slideIndex === activeIndex;
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', active ? 'false' : 'true');
        });
        buttons.forEach((button, buttonIndex) => {
            const active = buttonIndex === activeIndex;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-current', active ? 'true' : 'false');
        });
    };

    const stop = () => window.clearInterval(timer);
    const start = () => {
        stop();
        if (!reduceMotion && slides.length > 1) {
            timer = window.setInterval(() => show((activeIndex + 1) % slides.length), 5000);
        }
    };

    buttons.forEach((button) => button.addEventListener('click', () => {
        show(Number(button.dataset.dgSlideButton));
        start();
    }));
    carousel.addEventListener('mouseenter', stop);
    carousel.addEventListener('mouseleave', start);
    carousel.addEventListener('focusin', stop);
    carousel.addEventListener('focusout', start);
    start();
});
