define(['jquery'], function($) {
    return {
        init: function() {
            $(":text").keypress(function() {
                var cb = $(this).closest('.fgroup').find('input[type="checkbox"]');
                cb.attr('checked', true);
            });
        }
    };
});
