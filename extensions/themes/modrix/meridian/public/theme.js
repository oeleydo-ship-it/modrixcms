/**
 * Meridian Advisory theme enhancements:
 * - Tracks scroll offset on the floating glass header bar
 * - Enables crisp desktop hover-intent and mutual exclusion on dropdown mega-menus
 */
(function () {
  var DESKTOP_BP = 961;
  var closeTimer = null;

  function updateScrolled() {
    var headers = document.querySelectorAll('.fnl-page [data-meridian="header"]');
    var scrolled = window.scrollY > 36;
    headers.forEach(function (header) {
      header.classList.toggle('is-scrolled', scrolled);
    });
  }

  function closeSiblingDropdowns(activeDetails) {
    var container = activeDetails.closest('nav');
    if (!container) return;
    container.querySelectorAll('details.fnl-nav-details[open]').forEach(function (node) {
      if (node !== activeDetails) {
        node.removeAttribute('open');
      }
    });
  }

  function wireDropdowns() {
    document.querySelectorAll('.fnl-page [data-meridian="header"] details.fnl-nav-details').forEach(function (details) {
      if (details.dataset.meridianBound === '1') return;
      details.dataset.meridianBound = '1';

      details.addEventListener('toggle', function () {
        if (details.open) {
          closeSiblingDropdowns(details);
        }
      });

      details.addEventListener('mouseenter', function () {
        if (window.innerWidth < DESKTOP_BP) return;
        if (closeTimer) {
          clearTimeout(closeTimer);
          closeTimer = null;
        }
        details.open = true;
      });

      details.addEventListener('mouseleave', function () {
        if (window.innerWidth < DESKTOP_BP) return;
        closeTimer = setTimeout(function () {
          details.open = false;
        }, 140);
      });
    });

    if (!document.body.dataset.meridianGlobalBound) {
      document.body.dataset.meridianGlobalBound = '1';
      document.addEventListener('click', function (event) {
        if (event.target && event.target.closest('details.fnl-nav-details')) return;
        document.querySelectorAll('.fnl-page [data-meridian="header"] details.fnl-nav-details[open]').forEach(function (node) {
          node.open = false;
        });
      });

      document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        document.querySelectorAll('.fnl-page [data-meridian="header"] details.fnl-nav-details[open]').forEach(function (node) {
          node.open = false;
        });
      });
    }
  }

  function init() {
    updateScrolled();
    wireDropdowns();
  }

  window.addEventListener('scroll', updateScrolled, { passive: true });

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }

  // Re-bind after React client hydration mounts the builder-page root
  if (typeof MutationObserver !== 'undefined') {
    var observer = new MutationObserver(function () {
      wireDropdowns();
    });
    observer.observe(document.documentElement, { childList: true, subtree: true });
  }
})();
