/**
 * Javascript to update the progress as the datacleansing runs.
 */

require(['core/ajax'], function(ajax) {
        var promises = ajax.call([
            { methodname: 'local_datacleaner_get_datacleaner_state', args: { cancel: false } }
            ]);

        promises[0].done(function(response) {
            // Update the status
            if (!response.data) {
            $('.admintable tr:first td.c1').css({animation: throbber 2s;});
            }
            }).fail(function(ex) {
                // Say we failed.
                });
        });

jQuery.keyframe.define([{
name: 'throbber',
'0%': {width: 0%;}
'50%': {width: 100%;}
'99%': {width: 0%;}
}]);
