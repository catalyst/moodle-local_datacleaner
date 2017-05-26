define(['jquery'], function($) {
    return {
        init: function() {
            $("input[type=text], textarea").each(function() {
                var input = $(this);
                input.keypress(function() {
                    var cb = $(this).closest('.fgroup, .row').find('input[type="checkbox"]');
                    cb.prop('checked', 1);
                });
            });
        }
    };
});
