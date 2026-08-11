// Fades the scrollable edge(s) of overflowing article tables so it's obvious
// there's more to see, pairing with the mask-image rules in pages/article.scss.
export default function scrollableTables() {
    const EDGE_TOLERANCE = 1;
    const tables = document.querySelectorAll('.c-article table');
    if (!tables.length) return;

    function updateScrollState(table) {
        const canScroll = table.scrollWidth > table.clientWidth + EDGE_TOLERANCE;
        table.classList.toggle('has-overflow', canScroll);

        if (!canScroll) {
            table.removeAttribute('data-scroll-position');
            return;
        }

        const atStart = table.scrollLeft <= EDGE_TOLERANCE;
        const atEnd = table.scrollLeft + table.clientWidth >= table.scrollWidth - EDGE_TOLERANCE;
        table.setAttribute('data-scroll-position', atStart ? 'start' : atEnd ? 'end' : 'middle');
    }

    tables.forEach((table) => {
        updateScrollState(table);
        table.addEventListener('scroll', () => updateScrollState(table), { passive: true });
    });

    window.addEventListener('resize', () => tables.forEach(updateScrollState));
}
