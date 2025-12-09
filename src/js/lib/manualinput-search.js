const $ = jQuery;
const store = {};
/* 
import Fuse from 'fuse.js'
import { debounce } from './shared';

const threshhold = 0.5; 

const doSearch = (e, id) => {
    const $field = $(e.currentTarget);
    const term = $field.val().trim();

    if (term.length === 0) {
        store[id].rows.show();
        return;
    }

    const result = store[id].fuse.search(term);

    store[id].rows.hide();
    result.forEach(element => {
        if (element.score <= threshhold) {
            store[id].rows.eq(element.refIndex).show();
        }
    });
}
*/

const doSearch = (e, id) => {
    const $field = $(e.currentTarget);
    const term = $field.val().trim().toLowerCase();

    if (term.length === 0) {
        store[id].rows.show();
        return;
    }

    store[id].data.forEach((item, index) => {
        if (item.title.indexOf(term) > 0 || item.body.indexOf(term) > 0) {
            store[id].rows.eq(index).show();
        } else {
            store[id].rows.eq(index).hide(); 
        }
    });
}

const faqSearch = () => {
    const $containers = $('.modularity-mod-manualinput.has-search');

    if ($containers.length === 0) {
        return;
    }

    $containers.each((index, container) => {
        const $container = $(container);
        const $rows = $container.find('.c-accordion__section');
        const id = $container.attr('id').replace(/-/g, '_');

        $container.addClass('js-search');

        const data = $rows.toArray().map((row) => {
            const $row = $(row);
            return {
                title: $row.find('.c-accordion__button').text().trim().toLowerCase(),
                body: $row.find('.c-accordion__content').text().trim().toLowerCase(),
            }
        });

        store[id] = {
            rows: $rows,
            data,
            /* fuse: new Fuse(data, { 
                isCaseSensitive: true,
                includeScore: true,
                ignoreLocation: true,
                keys: [
                    {
                        name: 'title',
                        weight: 2,
                    },
                    'body'
                ]
            }), */
        };

        /* const debouncedSearch = debounce((e) => doSearch(e, id), 500); */
        $container.on('keyup', 'input[type=search]', (e) => { doSearch(e, id); });
    });
}

export default faqSearch;