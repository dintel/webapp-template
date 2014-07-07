(function($) {
    $.fn.dataTable = function(action,data) {
        if(!this.is("table")) {
            console.error("dataTable can be called only on table element");
            return this;
        }

        if(action === "init") {
            var params = this.data("params");
            var checkboxClass = this.data("checkboxClass");
            var convertRowFuncName = this.data('tableConvertRowFunc');
            var options = {};
            
            if(params === undefined) {
                this.data("params",{});
                params = {};
            }
            
            if(checkboxClass === undefined) {
                this.data("checkboxClass","");
            }

            if(convertRowFuncName) {
                options.convertRowFunc = window[convertRowFuncName];
            }

            options.url = this.data("url");
            options.params = params;
            options.checkboxClass = checkboxClass;

            this.data("options",$.extend({},$.fn.dataTable.defaults,options));
            
            return this.dataTable("refresh");
        }
        
        if(action === "refresh") {
            var table = this;
            var tbody = $("tbody:first",this);
            var options = this.data("options");

            tbody.empty();

            table.data("data",data);

            $.post(options.url,options.params,function(json){
                if('message' in json) {
                    msg(json.message.type,json.message.text);
                } else {
                    $.each(json.result,function(i,rawRow){
                        var row = options.convertRowFunc(rawRow);
                        if("checkboxClass" in options) {
                            var cell = $('<td><input type="checkbox" class="'+options.checkboxClass+'" /></td>');
                            $(row).prepend(cell);
                        }
                        tbody.append(row);
                    });

                    if(json.result.length == 0) {
                        var cols = $("th",this).length;
                        row = $('<tr><td colspan="'+cols+'"><center><strong>'+options.emptyLabel+'</strong></center></td></tr>');
                        tbody.append(row);
                    }

                    $("tbody input[type=checkbox]:first",table).triggerHandler("click");
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

    $.fn.dataTable.defaults = {
        convertRowFunc: function(row) {row.cells = $.map(row.cells,function(c){return $('<td></td>').text(c);});return row;},
    };
}(jQuery));

function toggleTableButton(button,e) {
    var table = $(button.data("tableButton"));
    if($("input:checked",table).length > 0) {
        button.prop("disabled",false);
    } else {
        button.prop("disabled",true);
    }
}

$(document).ready(function(){
    $("table[data-table]").each(function(i,e) {
        $(e).dataTable("init");
    });

    $(":button[data-table-button]").each(function(i,e) {
        var table = $($(e).data("tableButton"));
        var chkboxClass = table.data("checkboxClass");
        var button = $(this);
        table.on("click",'.'+chkboxClass,function(e){ toggleTableButton(button,e); });
        toggleTableButton(button,null);
    });

    $(":button[data-table-refresh]").click(function(e){
        var table = $($(this).data("tableRefresh"));
        var cols = $("th",table).length;

        var row = $('<tr class="info"><td colspan="'+cols+'"><center><strong>Loading...</strong></center></td></tr>');

        $("tbody tr",table).remove();
        $("tbody",table).append(row);
        table.dataTable("refresh");
    });
});
