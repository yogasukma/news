/**
 * Read State Manager
 * Tracks read articles in localStorage with 7-day expiry.
 * Applies visual distinction (dimmed) to already-read article cards.
 */

const STORAGE_KEY = 'rss-read-articles';
const EXPIRY_MS = 7 * 24 * 60 * 60 * 1000; // 7 days in ms

/**
 * Get all read article IDs and their timestamps.
 */
function getReadState() {
    try {
        const data = localStorage.getItem(STORAGE_KEY);
        return data ? JSON.parse(data) : {};
    } catch {
        return {};
    }
}

/**
 * Save read state object to localStorage.
 */
function saveReadState(state) {
    try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(state));
    } catch {
        // localStorage full or disabled — silently ignore
    }
}

/**
 * Remove entries older than 7 days.
 */
function cleanup() {
    const state = getReadState();
    const now = Date.now();
    let changed = false;

    for (const [id, timestamp] of Object.entries(state)) {
        if (now - timestamp > EXPIRY_MS) {
            delete state[id];
            changed = true;
        }
    }

    if (changed) {
        saveReadState(state);
    }
}

/**
 * Mark an article as read.
 */
function markRead(articleId) {
    const state = getReadState();
    state[articleId] = Date.now();
    saveReadState(state);
    applyToCard(articleId);
}

/**
 * Check if an article is read.
 */
function isRead(articleId) {
    const state = getReadState();
    const timestamp = state[articleId];

    if (!timestamp) {
        return false;
    }

    // Check expiry
    if (Date.now() - timestamp > EXPIRY_MS) {
        return false;
    }

    return true;
}

/**
 * Apply read styling to a single card.
 */
function applyToCard(articleId) {
    const card = document.querySelector(`[data-article-id="${articleId}"]`);
    if (!card) {
        return;
    }

    card.classList.add('is-read');
}

/**
 * Apply read state to all article cards on the page.
 */
function applyReadState() {
    const cards = document.querySelectorAll('[data-article-id]');
    cards.forEach((card) => {
        const articleId = card.getAttribute('data-article-id');
        if (isRead(articleId)) {
            card.classList.add('is-read');
        }
    });
}

// Expose for use by app.js and spa.js
window.ReadState = { markRead, isRead, applyReadState, cleanup };

// Run cleanup and apply on load
cleanup();
applyReadState();
