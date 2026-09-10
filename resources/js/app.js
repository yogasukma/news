import './bootstrap';

const modal = document.getElementById('article-modal');
const modalTitle = document.getElementById('modal-title');
const modalMeta = document.getElementById('modal-meta');
const modalBody = document.getElementById('modal-body');
const modalOriginalLink = document.getElementById('modal-original-link');
const modalClose = document.getElementById('modal-close');

window.openArticle = async function (id) {
    try {
        const response = await fetch(`/article/${id}`);

        if (!response.ok) {
            return;
        }

        const article = await response.json();

        modalTitle.textContent = article.title;

        // Build meta line: feed name links to its source page (using DOM API to prevent XSS)
        modalMeta.innerHTML = '';
        const feedLink = document.createElement('a');
        feedLink.href = `/sources/${article.feed.id}`;
        feedLink.dataset.spa = '';
        feedLink.className = 'font-medium text-stone-600 hover:text-stone-900 hover:underline transition-colors inline-flex items-center gap-1';
        feedLink.addEventListener('click', closeModal);
        if (article.feed.favicon_url) {
            const faviconImg = document.createElement('img');
            faviconImg.src = article.feed.favicon_url;
            faviconImg.alt = '';
            faviconImg.className = 'w-4 h-4 rounded-sm inline-block align-text-bottom';
            faviconImg.loading = 'lazy';
            faviconImg.onerror = function () { this.style.display = 'none'; };
            feedLink.appendChild(faviconImg);
        }
        feedLink.appendChild(document.createTextNode(article.feed.title));
        modalMeta.appendChild(feedLink);
        const metaText = document.createTextNode(
            `${article.author ? ` · by ${article.author}` : ''} · ${new Date(article.published_at).toLocaleString()}`
        );
        modalMeta.appendChild(metaText);
        modalBody.innerHTML = article.content || '<p class="text-stone-400">No content available.</p>';

        // Open every link inside the article content in a new tab
        modalBody.querySelectorAll('a').forEach((a) => {
            a.target = '_blank';
            a.rel = 'noopener noreferrer';
        });

        modalOriginalLink.href = article.url;

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

        // Mark article as read
        if (window.ReadState) {
            window.ReadState.markRead(article.id);
        }

        modalClose.focus();
    } catch {
        // Silently handle network errors
    }
};

function closeModal() {
    modal.classList.add('hidden');
    document.body.style.overflow = '';
}

modalClose.addEventListener('click', closeModal);

// Close when clicking anywhere outside the modal content
// (the backdrop and the scrollable area around the white card).
// The overlay wrapper covers the full viewport above the backdrop,
// so this single delegated listener handles both cases.
document.getElementById('modal-overlay').addEventListener('click', (e) => {
    if (e.target.closest('#modal-content') || e.target.closest('#modal-close')) {
        return;
    }

    closeModal();
});

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
        closeModal();
    }
});
