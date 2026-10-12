/**
 * Apply intrinsic dimensions to gallery lightbox images after they load.
 *
 * Styleguide injects the large image into an empty modal figure. Without
 * width/height the figure collapses (fit-content + max-width 100%). Matches
 * helsingborg-stad/styleguide#1319 until that package is upgraded.
 */
export default function galleryLightbox() {
    const applySize = (image) => {
        if (!(image instanceof HTMLImageElement)) {
            return;
        }

        if (image.naturalWidth > 0 && image.naturalHeight > 0) {
            image.setAttribute('width', String(image.naturalWidth));
            image.setAttribute('height', String(image.naturalHeight));
        }

        image.classList.add('c-image__image', 'c-image__image--is-visible');
    };

    const bindImage = (image) => {
        if (!(image instanceof HTMLImageElement)) {
            return;
        }

        if (image.dataset.alingsasLightboxBound === 'true') {
            return;
        }

        image.dataset.alingsasLightboxBound = 'true';

        if (image.complete) {
            applySize(image);
        }

        image.addEventListener('load', () => applySize(image));
    };

    const scan = (root) => {
        root.querySelectorAll('.c-modal--gallery img').forEach(bindImage);
    };

    const observeModal = (modal) => {
        if (!(modal instanceof Element) || modal.dataset.alingsasLightboxObserved === 'true') {
            return;
        }

        modal.dataset.alingsasLightboxObserved = 'true';
        scan(modal);

        const observer = new MutationObserver((mutations) => {
            for (const mutation of mutations) {
                if (mutation.type === 'attributes' && mutation.target instanceof HTMLImageElement) {
                    bindImage(mutation.target);
                    continue;
                }

                mutation.addedNodes.forEach((node) => {
                    if (!(node instanceof Element)) {
                        return;
                    }

                    if (node instanceof HTMLImageElement) {
                        bindImage(node);
                    }

                    node.querySelectorAll('img').forEach(bindImage);
                });
            }
        });

        observer.observe(modal, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['src'],
        });
    };

    document.querySelectorAll('.c-modal--gallery').forEach(observeModal);

    const bodyObserver = new MutationObserver((mutations) => {
        for (const mutation of mutations) {
            mutation.addedNodes.forEach((node) => {
                if (!(node instanceof Element)) {
                    return;
                }

                if (node.matches('.c-modal--gallery')) {
                    observeModal(node);
                }

                node.querySelectorAll('.c-modal--gallery').forEach(observeModal);
            });
        }
    });

    if (document.body) {
        bodyObserver.observe(document.body, { childList: true, subtree: true });
    }
}
