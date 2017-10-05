function randomString(length, chars) {
    var result = '';
    for (var i = length; i > 0; --i) result += chars[Math.round(Math.random() * (chars.length - 1))];
    return result;
}

function create_alert(data,alert_type) {

    // set up the output div
    var element =  document.getElementById('alert-container');
    if (typeof(element) != 'undefined' && element != null)
    {
        element.outerHTML = "";
        delete element;
    }
    var div = document.createElement("div");
    div.id = "alert-container";
    div.removeAttribute("class");
    div.style.margin = "0 15% 10px 15%";

    document.getElementById("script-text").appendChild(div);
    div.setAttribute('class',"alert alert-dismissable fade in "+alert_type); 
    $("#alert-container").html(data)

    return false;
}

$(function() {
    $('.error').hide(); // hides the required field text in error class
    $(".button").click(function() {
        // Get the form values DO NOT process them for PHP/Python use
        // To get memory to work we process them in PHP
        // replace spaces with underscores and get rid of commas
        // To do this use these regexp utilities: https://stackoverflow.com/a/1144788
        function escapeRegExp(str) {
            return str.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
        }
        function replaceAll(str, find, replace) {
            return str.replace(new RegExp(escapeRegExp(find), 'g'), replace);
        }

        // ### PARSING OF FORM INPUT ###
        // row 1
        var gene = replaceAll(replaceAll($("input#gene").val(),' ',''),',','_').toUpperCase(); // separate multiple inputs with underscore
        var cluster = replaceAll($("select#cluster").val(),' ','_');
        var color = replaceAll($("select#color").val(),' ','_');
        var int_type = replaceAll($("select#int_type").val(),' ','_');
        // row 2
        var experiments = $("input#experiments").val();
        var publications = $("input#publications").val();
        var methods = $("input#methods").val();
        var method_types = $("select#method_types").val().join();
        // row 3
        var process = replaceAll($("select#process").val().join(),' ','_');
        var compartment = replaceAll($("select#compartment").val(),': ',':');
        var expression = replaceAll($("select#expression").val().join(),' ','_');
        // row 4
        var max_nodes = $("input#max_nodes").val();
        var filter_condition = replaceAll($("select#filter_condition").val(),' ','_');
        // additional
        var unique_str = randomString(7, '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ');

        console.log(process,method_types)

        // ### HIGHLIGHT INPUT ERRORS IN THE FORM ###
        if (gene == "") {
            $("label#gene_error").show();
            $("input#gene").focus(); // show error message if blank and focus on the field
            return false; // do not submit in this case
        }
        if (experiments < 1) {
            $("label#experiments_error").show();
            $("input#experiments").focus(); // show error message if blank and focus on the field
            return false; // do not submit in this case
        }
        if (publications < 1) {
            $("label#publications_error").show();
            $("input#publications").focus(); // show error message if blank and focus on the field
            return false; // do not submit in this case
        }
        if (methods < 1) {
            $("label#methods_error").show();
            $("input#methods").focus(); // show error message if blank and focus on the field
            return false; // do not submit in this case
        }
        if (max_nodes < 1 || max_nodes > 100) {
            $("label#max_nodes_error").show();
            $("input#max_nodes").focus(); // show error message if blank and focus on the field
            return false; // do not submit in this case
        }

        data = {// create object
            gene                : gene,
            cluster             : cluster,
            color               : color,
            int_type            : int_type,
            experiments         : experiments,
            publications        : publications,
            methods             : methods,
            method_types        : method_types,
            process             : process,
            compartment         : compartment,
            expression          : expression,
            max_nodes           : max_nodes,
            filter_condition    : filter_condition,
            unique_str          : unique_str, 
        }
        console.log("Input from the form:",data)
        var link_to_open = "index.php?";
        for (var key in data) {
            console.log(key)
            link_to_open += key + "=" + data[key] + "&";
        }
        link_to_open = link_to_open.slice(0, -1);
        console.log(link_to_open)

        $.ajax({
            type: "POST",
            url: "process_user_input.php",
            data: data,
            beforeSend: function() {
                data = "<h4>Request submitted!</h4><p>Your visualization will be ready soon.</p>";
                create_alert(data,"alert-info");
            },
            success: function(data) {
                // Save python/PHP output to console

                // Only open viz if PHP process script says everything is okay
                // Surround PHP/Python output in green or red alert box based on success or failure
                if (data.indexOf("Everything went A-OK.") != -1) { // this string has to exist in data
                    create_alert(data,"alert-success");

                    window.location.href = link_to_open;
                }
                else {
                    if (data.indexOf("No interactions matching these conditions.") != -1 ) {
                        console.log("Original php return:" + data);
                        data = "No interactions matching these conditions. Try reducing the specificity of your search or reduce the number of required experiments, methods and/or publications."
                    }
                    create_alert(data,"alert-danger");
                }
            }
        });
        return false;
    });
});