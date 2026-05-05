import './bootstrap';

const modal = document.getElementById('article-modal');
const modalTitle = document.getElementById('modal-title');
const modalMeta = document.getElementById('modal-meta');
const modalBody = document.getElementById('modal-body');
const modalOriginalLink = document.getElementById('modal-original-link');
const modalClose = document.getElementById('modal-close');
const modalBackdrop = document.getElementById('modal-backdrop');

window.openArticle = async function (id) {
    try {
        const response = await fetch(`/article/${id}`);

        if (!response.ok) {
            return;
        }

        const article = await response.json();

        modalTitle.textContent = article.title;

        // Build meta line with optional favicon (using DOM API to prevent XSS)
        modalMeta.innerHTML = '';
        if (article.feed.favicon_url) {
            const faviconImg = document.createElement('img');
            faviconImg.src = article.feed.favicon_url;
            faviconImg.alt = '';
            faviconImg.className = 'w-4 h-4 rounded-sm inline-block align-text-bottom';
            faviconImg.loading = 'lazy';
            faviconImg.onerror = function () { this.style.display = 'none'; };
            modalMeta.appendChild(faviconImg);
            modalMeta.appendChild(document.createTextNode(' '));
        }
        const metaText = document.createTextNode(
            `${article.feed.title}${article.author ? ` · by ${article.author}` : ''} · ${new Date(article.published_at).toLocaleString()}`
        );
        modalMeta.appendChild(metaText);
        modalBody.innerHTML = article.content || '<p class="text-stone-400">No content available.</p>';
        modalOriginalLink.href = article.url;

        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';

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
modalBackdrop.addEventListener('click', closeModal);

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
        closeModal();
    }
});
