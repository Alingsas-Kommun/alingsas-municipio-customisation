/**
 * WYSIWYG "Knapp" dialog. Replaces Municipio's mce_hbg_buttons TinyMCE plugin
 * (see includes/TinyMceButtons.php). Plain ES5: loaded as-is by TinyMCE 4,
 * not built by Vite.
 */
(function () {
    if (typeof tinymce === 'undefined') {
        return;
    }

    var config = window.alingsasMceButtons || {};
    var colors = config.colors && config.colors.length ? config.colors : [
        { value: 'primary', label: 'Mörkgrön', background: '#236151', contrast: '#ffffff' },
        { value: 'secondary', label: 'Ljusgrön', background: '#cadfde', contrast: '#3c3939' }
    ];
    var t = config.i18n || {};
    var PREVIEW_ID = 'alingsas-mce-button-preview';

    function label(key, fallback) {
        return t[key] || fallback;
    }

    function findColor(value) {
        for (var i = 0; i < colors.length; i++) {
            if (colors[i].value === value) {
                return colors[i];
            }
        }
        return colors[0];
    }

    function previewStyle(data) {
        var color = findColor(data.color);
        var padding = { sm: '6px 16px', md: '8px 28px', lg: '12px 36px' }[data.size] || '8px 28px';
        var base = 'display:inline-block;border-radius:100vh;font-weight:500;line-height:1.4;padding:' + padding + ';';

        if (data.style === 'outlined') {
            return base + 'background:transparent;border:2px solid ' + color.background + ';color:' + color.background + ';';
        }

        return base + 'background:' + color.background + ';border:2px solid ' + color.background + ';color:' + color.contrast + ';';
    }

    function updatePreview(win, doc) {
        var el = doc.getElementById(PREVIEW_ID);
        if (!el || !win) {
            return;
        }

        var data = win.toJSON();
        el.setAttribute('style', previewStyle(data));
        el.textContent = data.text || label('text', 'Knapptext');
    }

    function buildMarkup(editor, data) {
        var style = data.style === 'outlined' ? 'outlined' : 'filled';
        var color = findColor(data.color).value;
        var size = ['sm', 'md', 'lg'].indexOf(data.size) !== -1 ? data.size : 'md';
        var classes = [
            'c-button',
            'c-button__' + style,
            'c-button__' + style + '--' + color,
            'c-button--' + size,
            'ripple',
            'ripple--before',
            'u-no-decoration'
        ].join(' ');
        var target = data.newWindow ? ' target="_blank" rel="noopener"' : '';

        return '<a href="' + editor.dom.encode(data.href) + '"' + target + ' class="' + classes + '">' +
            '<span class="c-button__label"><span class="c-button__label-text">' +
            editor.dom.encode(data.text) +
            '</span></span></a>';
    }

    function openDialog(editor) {
        var doc = document;
        var selectedText = editor.selection ? editor.selection.getContent({ format: 'text' }) : '';
        var win;
        var refresh = function () {
            updatePreview(win, doc);
        };

        win = editor.windowManager.open({
            title: label('title', 'Lägg till knapp'),
            width: 480,
            height: 330,
            body: [
                { type: 'textbox', name: 'text', label: label('text', 'Knapptext'), value: selectedText, onkeyup: refresh },
                { type: 'textbox', name: 'href', label: label('href', 'Länk'), value: 'https://' },
                {
                    type: 'listbox',
                    name: 'color',
                    label: label('color', 'Färg'),
                    value: colors[0].value,
                    values: colors.map(function (c) {
                        return { text: c.label, value: c.value };
                    }),
                    onselect: refresh
                },
                {
                    type: 'listbox',
                    name: 'style',
                    label: label('style', 'Stil'),
                    value: 'filled',
                    values: [
                        { text: label('filled', 'Fylld'), value: 'filled' },
                        { text: label('outlined', 'Kontur'), value: 'outlined' }
                    ],
                    onselect: refresh
                },
                {
                    type: 'listbox',
                    name: 'size',
                    label: label('size', 'Storlek'),
                    value: 'md',
                    values: [
                        { text: label('sizeMd', 'Normal'), value: 'md' },
                        { text: label('sizeLg', 'Stor'), value: 'lg' },
                        { text: label('sizeSm', 'Liten'), value: 'sm' }
                    ],
                    onselect: refresh
                },
                { type: 'checkbox', name: 'newWindow', text: label('newWindow', 'Öppna länken i nytt fönster') },
                {
                    type: 'container',
                    label: label('preview', 'Förhandsvisning'),
                    html: '<span id="' + PREVIEW_ID + '"></span>'
                }
            ],
            onsubmit: function (e) {
                var data = e.data;
                data.text = (data.text || '').trim();
                data.href = (data.href || '').trim();

                if (!data.text || !data.href || data.href === 'https://') {
                    e.preventDefault();
                    editor.windowManager.alert(label('missing', 'Fyll i knapptext och länk.'));
                    return false;
                }

                editor.insertContent(buildMarkup(editor, data));
            }
        });

        refresh();
    }

    tinymce.PluginManager.add('mce_hbg_buttons', function (editor) {
        editor.addButton('mce_hbg_buttons', {
            text: label('toolbar', 'Knapp'),
            icon: false,
            tooltip: label('tooltip', 'Lägg till knapp'),
            cmd: 'mce_hbg_buttons'
        });

        editor.addCommand('mce_hbg_buttons', function () {
            openDialog(editor);
        });
    });
})();
