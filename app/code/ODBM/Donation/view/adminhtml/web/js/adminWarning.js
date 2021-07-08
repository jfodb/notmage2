define(['jquery', 'domReady!'], function ($) {
    "use strict";
    if ($('.adminhtml-system_config-edit #store-change-button').length && $('.adminhtml-system_config-edit #store-change-button').html().indexOf('Default Config') > 0) {
        $('.adminhtml-system_config-edit #store-change-button').css('background-color', '#F44');
    }
});