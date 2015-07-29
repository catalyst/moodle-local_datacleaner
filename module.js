/**
 * Javascript to update the progress as the datacleansing runs.
 */

require(['core/ajax'], function(ajax) {
        var promises = ajax.call([
            { methodname: 'local_datacleaner_get_state', args: { cancel: false } }
            ]);

        promises[0].done(function(response) {
            // Update the status
            }).fail(function(ex) {
                // Say we failed.
                });
        });
