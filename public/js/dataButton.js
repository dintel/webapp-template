(function($) {
    $.fn.dataButton = function(action,data) {
        if(!this.is(":button") && !this.is("a")) {
            console.error("dataButton can be called only on button or link element");
            return this;
        }

        if(action === "init") {
            var params = this.data("params");
            var prepare = this.data('prepare');
            var callback = this.data('callback');
            var options = {};
            
            if(params === undefined) {
                this.data("params",{});
            }
            options.params = params;

            if(callback) {
                options.callback = window[callback];
            }
            if(prepare) {
                options.prepare = window[prepare];
            }

            options.url = this.data('url');

            this.data("options",$.extend({},$.fn.dataButton.defaults,options));
            this.click(function(e){ $(this).dataButton('call'); });
            
            return this;
        }
        
        if(action === "call") {
            var button = this;
            var options = this.data("options");
            var params = {};

            options.button = button;
            
            if(options.prepare()) {
                if(typeof options.params === 'object') {
                    for(var prop in options.params) {
                        if(typeof options.params[prop] === 'object') {
                            params[prop] = JSON.stringify(options.params[prop]);
                        } else {
                            params[prop] = options.params[prop];
                        }
                    }
                }
                
                $.post(options.url,params,function(json){
                    if('message' in json) {
                        msg(json.message.type,json.message.text);
                    }
                    if('result' in json) {
                        options.callback(json.result);
                    } else {
                        options.callback();
                    }
                }).fail(function(jqxhr, textStatus, error){
                    var err = "Request Failed: " + textStatus + ", " + error;
                    console.error(err);
                    msg("error",err);
                });
            }
            
            return this;
        }

        if(action === "getParams") {
            return this.data("options").params;
        }

        if(action === "setParams") {
            var options = this.data("options");
            var params = $.extend({},data);
            options.params = params;
            this.data("params",params);
            this.data("options",options);
            return this;
        }

        return this;
    };

    $.fn.dataButton.defaults = {
        prepare: function() {return true;},
        callback: function() {return true;}
    };
}(jQuery));

$(document).ready(function(){
    $(":button[data-button]").each(function(i,e) {
        $(e).dataButton("init");
    });
});
