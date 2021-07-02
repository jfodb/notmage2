define(['jquery', 'domReady!'], function ($) {
    "use strict";
    if ($('.adminhtml-system_config-edit #store-change-button').length && $('.adminhtml-system_config-edit #store-change-button').html().indexOf('Default Config') > 0) {
        $('.adminhtml-system_config-edit #store-change-button').css('background-color', '#F44');
        $('.adminhtml-system_config-edit #store-change-button').parent().addClass('default_warning');
    } else {
        $('.adminhtml-system_config-edit #store-change-button').css('background-color', '#FFF');
        $('.adminhtml-system_config-edit #store-change-button').parent().removeClass('default_warning');
    }
});