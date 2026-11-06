/**
 * Manual Input: show "Visa som slider", "Centrera allt textinnehåll" and
 * "Dölj pil-ikon i rubrik" only while "Visa som" (display_as) is Card.
 *
 * Done here instead of ACF conditional logic because display_as is in
 * Modularity's field group, and ACF only finds condition fields within the
 * same group. Plain ES5, served as-is (see includes/modules/ManualInput.php).
 */
(function ($) {
    if (typeof acf === 'undefined' || !window.alingsasManualInputSettings) {
        return;
    }

    var config = window.alingsasManualInputSettings;
    var LOCK = 'alingsas-card-only';

    // Post edit screen: the groups are separate postboxes in one form.
    // Block editor: each block has its own fields container.
    function scopeOf($el) {
        var $scope = $el.closest('.acf-block-fields, .acf-block-form, form');
        return $scope.length ? $scope : $(document);
    }

    // null: no display_as field in this scope, leave our fields alone.
    // '': display_as present but nothing chosen yet (counts as not Card).
    function displayAsValue($scope) {
        var $field = $scope.find('.acf-field[data-key="' + config.displayAsKey + '"]');
        if (!$field.length) {
            return null;
        }

        var $checked = $field.find('input:checked');
        return $checked.length ? $checked.val() : '';
    }

    function apply($scope) {
        var value = displayAsValue($scope);
        if (value === null) {
            return;
        }

        var visible = config.templates.indexOf(value) !== -1;

        config.fieldKeys.forEach(function (key) {
            acf.getFields({ key: key, parent: $scope }).forEach(function (field) {
                if (visible) {
                    field.show(LOCK);
                } else {
                    field.hide(LOCK);
                }
            });
        });
    }

    acf.addAction('ready_field/key=' + config.displayAsKey, function (field) {
        apply(scopeOf(field.$el));
    });

    acf.addAction('append_field/key=' + config.displayAsKey, function (field) {
        apply(scopeOf(field.$el));
    });

    config.fieldKeys.forEach(function (key) {
        acf.addAction('ready_field/key=' + key, function (field) {
            apply(scopeOf(field.$el));
        });
        acf.addAction('append_field/key=' + key, function (field) {
            apply(scopeOf(field.$el));
        });
    });

    $(document).on('change', '.acf-field[data-key="' + config.displayAsKey + '"] input', function () {
        apply(scopeOf($(this)));
    });
})(jQuery);
