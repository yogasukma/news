/**
 * SPA Navigation Controller
 * Intercepts date, folder, search, and pagination links to fetch HTML fragments
 * and swap content without a full page reload.
 */

const mainEl = document.querySelector('main');
const loadingBar = document.getElementById('spa-loading');
const loadingProgress = loadingBar?.querySelector('div');

let isNavigating = false;

// --- Loading bar helpers ---

function showLoading() {
    if (!loadingBar || !loadingProgress) {
        return;
    }
    isNavigating = true;
    loadingProgress.style.width = '0%';
    loadingProgress.style.transition = 'none';
    loadingBar.classList.remove('opacity-0');
    loadingBar.classList.add('opacity-100');

    // Animate to 70% quickly, then slow down
    requestAnimationFrame(() => {
        loadingProgress.style.transition = 'width 400ms ease-out';
        loadingProgress.style.width = '70%';
    });
}

function hideLoading() {
    if (!loadingBar || !loadingProgress) {
        return;
    }
    loadingProgress.style.transition = 'width 200ms ease-out';
    loadingProgress.style.width = '100%';

    setTimeout(() => {
        loadingBar.classList.remove('opacity-100');
        loadingBar.classList.add('opacity-0');
        isNavigating = false;
    }, 250);
}

// --- SPA Navigation ---

function appendFragmentParam(url) {
    const parsed = new URL(url, window.location.origin);

    if (parsed.origin !== window.location.origin) {
        return null; // External link — don't intercept
    }

    parsed.searchParams.set('fragment', '1');

    return parsed.toString();
}

async function navigateTo(url, pushState = true) {
    if (isNavigating) {
        return;
    }

    const fragmentUrl = appendFragmentParam(url);

    if (!fragmentUrl) {
        window.location.href = url;

        return;
    }

    showLoading();

    try {
        const response = await fetch(fragmentUrl, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });

        if (!response.ok) {
            window.location.href = url;

            return;
        }

        const html = await response.text();

        // Swap main content
        mainEl.innerHTML = html;

        // Update browser URL
        if (pushState) {
            history.pushState({ spaUrl: url }, '', url);
        }

        // Scroll to top of main content
        mainEl.scrollIntoView({ behavior: 'smooth', block: 'start' });

        // Re-apply read state to new article cards
        if (window.ReadState) {
            window.ReadState.applyReadState();
        }
    } catch {
        // Network error — fall back to normal navigation
        window.location.href = url;
    } finally {
        hideLoading();
    }
}

// --- Event Delegation ---

// Intercept clicks on SPA links (date nav, folder pills, pagination)
document.addEventListener('click', (e) => {
    const link = e.target.closest('a[data-spa]');

    if (!link) {
        return;
    }

    // Don't intercept if modifier keys held (open in new tab)
    if (e.metaKey || e.ctrlKey || e.shiftKey || e.altKey) {
        return;
    }

    const href = link.getAttribute('href');

    if (!href || href.startsWith('#') || href.startsWith('mailto:')) {
        return;
    }

    e.preventDefault();
    navigateTo(href);
});

// Intercept search form submissions
document.addEventListener('submit', (e) => {
    const form = e.target;

    if (!form.matches('form[data-spa-search]')) {
        return;
    }

    e.preventDefault();

    const url = form.action + '?' + new URLSearchParams(new FormData(form)).toString();
    navigateTo(url);
});

// Intercept date picker changes
document.addEventListener('change', (e) => {
    if (!e.target.matches('input[data-spa-date]')) {
        return;
    }

    const value = e.target.value;
    const today = e.target.max;

    const url = value === today ? '/' : '/date/' + value;
    navigateTo(url);
});

// Handle browser back/forward
window.addEventListener('popstate', (e) => {
    if (e.state?.spaUrl) {
        navigateTo(e.state.spaUrl, false);
    }
});

// Mark initial page state
if (!history.state?.spaUrl) {
    history.replaceState({ spaUrl: window.location.href }, '');
}
