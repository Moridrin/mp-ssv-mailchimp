/**
 * Created by moridrin on 5-2-17.
 */
var fieldOptions = settings.field_options;
var tagOptions = settings.tag_options;

function mp_ssv_add_new_merge_tag(fieldID, fieldName, tagName) {
    var container = document.getElementById("custom-tags-placeholder");
    var tr = document.createElement("tr");
    var fieldTD = document.createElement("td");
    fieldTD.appendChild(createSelect(fieldID, "_field", fieldOptions, fieldName));
    tr.appendChild(fieldTD);
    var tagTD = document.createElement("td");
    tagTD.appendChild(createSelect(fieldID, "_tag", tagOptions, tagName));
    tr.appendChild(tagTD);
    container.appendChild(tr);
}

function createSelect(fieldID, fieldNameExtension, options, selected) {
    var select = document.createElement("select");
    select.setAttribute("id", fieldID + fieldNameExtension);
    select.setAttribute("name", "link_" + fieldID + fieldNameExtension);

    for (var i = 0; i < options.length; i++) {
        var option = document.createElement("option");
        option.setAttribute("value", options[i].toLowerCase());
        if (options[i].toLowerCase() == selected) {
            option.setAttribute("selected", "selected");
        }
        option.innerHTML = options[i];
        select.appendChild(option);
    }

    return select;
}
