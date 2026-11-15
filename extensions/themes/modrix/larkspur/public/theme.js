/**
 * Larkspur Family Health: header shadow on scroll for pages outside the visual builder.
 * Builder pages get their motion (headline reveals, fade-ups, marquee) from the page renderer.
 */
(function () {
  function update() {
    var scrolled = window.scrollY > 16;
    document.querySelectorAll('.site-header, header.nav').forEach(function (header) {
      header.classList.toggle('is-scrolled', scrolled);
    });
  }

  window.addEventListener('scroll', update, { passive: true });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', update);
  } else {
    update();
  }
})();
