import * as ko from "knockout";

var blockElement = "<div class='customSpinner' style='width: 100%; height:100%; text-align: center; display:none;'>\
<div class='floatingCirclesG' style='margin:auto;position:absolute;top:0;left:0;bottom:0;right:0;height:128px'>\
    <div class='f_circleG frotateG_01'></div>\
    <div class='f_circleG frotateG_02'></div>\
    <div class='f_circleG frotateG_03'></div>\
    <div class='f_circleG frotateG_04'></div>\
    <div class='f_circleG frotateG_05'></div>\
    <div class='f_circleG frotateG_06'></div>\
    <div class='f_circleG frotateG_07'></div>\
    <div class='f_circleG frotateG_08'></div>\
</div>\
</div>";


var $blockElement = $(blockElement);

ko.bindingHandlers['block'] = {
    update: (element, valueAccessor, allBindingsAccessor, viewModel) => {
        var status = ko.unwrap(valueAccessor());
        var target = $(element);

        if (status) {
            target.block({message:''});
        }
        else {
            target.unblock();
        }

        var spinner = target.find('.customSpinner');
        if (spinner.length == 0) {
            spinner = $blockElement.clone();
            if (status)
                spinner.show();
            else
                spinner.hide();

            target.find('.blockUI, .blockMsg, .blockElement').append(spinner);
        }
        else {
            if (status)
                spinner.show();
            else
                spinner.hide();
        }
    }
};