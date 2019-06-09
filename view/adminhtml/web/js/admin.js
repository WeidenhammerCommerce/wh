define([
    "jquery",
    "domReady!"
], function($) {
    "use strict";

    if($('body').hasClass('adminhtml-cache-index')) {
        var last_columns = $('#cache_grid_table tr td.last .grid-severity-notice span');
        $(last_columns).each(function(index){
            var tr = $(this).parents('tr');

            // Cache Management > Hide all enabled checkboxes
            tr.find('label').hide();
        });
    }
});