(function($) {
    $.fn.dataSelect = function(action,data) {
        if(!this.is("select")) {
            console.error("dataSelect can be called only on select element");
            return this;
        }

        if(action === "init") {
            var params = this.data("params");
            var convertFunc = this.data('convertFunc');
            var options = {};

            if(params === undefined) {
                this.data("params",{});
                params = {};
            }

            if(convertFunc) {
                options.convertFunc = window[convertFunc];
            }

            options.url = this.data("url");
            options.params = params;

            this.data("options",$.extend({},$.fn.dataSelect.defaults,options));

            return this;
        }

        if(action === "refresh") {
            var options = this.data("options");
            var select = this;
            var value = this.val();

            this.empty().prop('disabled',true).append($('<option value="">Loading...</option>'));

            $.post(options.url,options.params,function(json){
                if('message' in json) {
                    msg(json.message.type,json.message.text);
                } else {
                    if('result' in json && json.result.length != 0) {
                        select.prop('disabled',false);
                        select.empty();
                    }
                    $.each(json.result,function(i,r){
                        var option = options.convertFunc(r);
                        select.append(option);
                    });

                    if(json.result.length == 0) {
                        $("option",select).text("No data returned");
                    }

                    if(value != "" && value !== undefined && value !== null) {
                        select.val(value);
                        if(select.val() === null) {
                            $("option:first",select).prop("selected",true);
                        }
                    }
                    select.trigger('change');
                }
            }).fail(function(jqxhr, textStatus, error){
                var err = "Request Failed: " + textStatus + ", " + error;
                console.error(err);
                msg("error",err);
            });

            return this;
        }

        if(action === "getParams") {
            return this.data("options").params;
        }

        if(action === "setParams") {
            var options = this.data("options");
            options.params = data;
            this.data("options",options);
            return this;
        }

        return this;
    };

    $.fn.dataSelect.defaults = {
        convertFunc: function(data) {return $("<option></option>").val(data).text(data);}
    };
}(jQuery));

$(document).ready(function(){
    $("select[data-select]").each(function(i,e) {
        $(e).dataSelect("init");
    });
});
