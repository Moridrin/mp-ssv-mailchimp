/**
 * Created by moridrin on 5-2-17.
 */
var fieldOptions = merge_tag_settings.field_options;
var tagOptions = merge_tag_settings.tag_options;

function mp_ssv_add_new_merge_tag(fieldID, fieldName, tagName) {
    var container = document.getElementById("custom-tags-placeholder");
    var tr = document.createElement("tr");
    var fieldTD = document.createElement("td");
    fieldTD.appendChild(createLinkSelect(fieldID, "_field", fieldOptions, fieldName));
    tr.appendChild(fieldTD);
    var tagTD = document.createElement("td");
    tagTD.appendChild(createLinkSelect(fieldID, "_tag", tagOptions, tagName));
    tr.appendChild(tagTD);
    container.appendChild(tr);
}

function createLinkSelect(fieldID, fieldNameExtension, options, selected) {
    var select = document.createElement("select");
    select.setAttribute("id", fieldID + fieldNameExtension);
    select.setAttribute("name", "link_" + fieldID + fieldNameExtension);
    var clearOption = document.createElement("option");
    clearOption.setAttribute("value", "-1");
    clearOption.innerHTML = "[clear]";
    select.appendChild(clearOption);

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
