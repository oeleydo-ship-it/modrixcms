/**
 * Kairo Career theme enhancements:
 * - Tracks scroll offset on sticky crisp header bar
 * - Interactive Advantage Card active outline switcher
 * - Interactive scroll-reveal animations for cards (respecting prefers-reduced-motion)
 */
(function () {
  var REDUCED_MOTION =
    typeof window !== 'undefined' &&
    window.matchMedia &&
    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  function updateScrolled() {
    var headers = document.querySelectorAll('.fnl-page [data-kairo="header"]');
    var scrolled = window.scrollY > 24;
    headers.forEach(function (header) {
      header.classList.toggle('is-scrolled', scrolled);
    });
  }

  function wireAdvantageCards() {
    var stacks = document.querySelectorAll('.fnl-page [data-kairo="advantages"]');
    stacks.forEach(function (section) {
      var cards = Array.prototype.slice.call(section.querySelectorAll('[data-kairo-role="advantage-card"]'));
      if (cards.length === 0) return;

      var hasActive = cards.some(function (card) {
        return card.classList.contains('is-active');
      });
      if (!hasActive) {
        cards[0].classList.add('is-active');
      }

      cards.forEach(function (card) {
        if (card.dataset.kairoBound === '1') return;
        card.dataset.kairoBound = '1';
        card.setAttribute('tabindex', '0');

        function activate() {
          cards.forEach(function (other) {
            other.classList.toggle('is-active', other === card);
          });
        }

        card.addEventListener('click', activate);
        card.addEventListener('keydown', function (event) {
          if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            activate();
          }
        });
      });
    });
  }

  function wireReveals() {
    if (REDUCED_MOTION) return;
    if (typeof IntersectionObserver === 'undefined') return;

    var page = document.querySelector('.fnl-page:not(.fnl-editable)');
    if (!page) return;

    var targets = page.querySelectorAll(
      '[data-kairo] [data-kairo-role="bento-card"], ' +
      '[data-kairo] [data-kairo-role="stage-card"], ' +
      '[data-kairo] [data-kairo-role="review-card"], ' +
      '[data-kairo] [data-kairo-role="pricing-card"], ' +
      '[data-kairo] [data-kairo-role="value-card"]'
    );

    var observer = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            entry.target.setAttribute('data-kairo-reveal', 'visible');
            observer.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.08, rootMargin: '0px 0px -24px 0px' }
    );

    var viewportHeight = window.innerHeight || 900;
    targets.forEach(function (el, idx) {
      if (el.dataset.kairoRevealBound === '1') return;
      el.dataset.kairoRevealBound = '1';
      var rect = el.getBoundingClientRect();
      if (rect.top < viewportHeight * 0.92) {
        el.setAttribute('data-kairo-reveal', 'visible');
      } else {
        el.style.transitionDelay = ((idx % 4) * 55) + 'ms';
        el.setAttribute('data-kairo-reveal', 'pending');
        observer.observe(el);
      }
    });
  }

  function init() {
    updateScrolled();
    wireAdvantageCards();
    wireReveals();
  }

  window.addEventListener('scroll', updateScrolled, { passive: true });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  if (typeof MutationObserver !== 'undefined') {
    var domObserver = new MutationObserver(function () {
      wireAdvantageCards();
      wireReveals();
    });
    domObserver.observe(document.documentElement, { childList: true, subtree: true });
  }
})();
