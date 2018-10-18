<?php
add_filter('gform_add_field_buttons', 'bb_click_array_add_field', 10, 1);
function bb_click_array_add_field($field_groups) {
    foreach ($field_groups as &$group) {
        if ($group["name"] == "advanced_fields") {
            $group["fields"][] = array(
                    "class" => "button",
                    "value" => __("Click Array"),
                    "data-type" => "bb_click_array",
                    "onclick" => "StartAddField('bb_click_array');",
            );
            break;
        }
    }
    return $field_groups;
}

// Adds title to GF custom field
add_filter('gform_field_type_title', 'bb_click_array_field_title', 5, 2);
function bb_click_array_field_title($title, $field_type) {
    if ($field_type == 'bb_click_array') {
        return __('Click Array', 'bb_click_array');
    }
    return $title;
}

// Adds the input area to the external side
add_action("gform_field_input", "bb_click_array_field_input", 10, 5);
function bb_click_array_field_input($input, $field, $value, $lead_id, $form_id) {
    if ($field["type"] == "bb_click_array") {
        $field_id = IS_ADMIN || $form_id == 0 ? "input_$id" : "input_".$form_id."_$id";

        $input_name = $form_id.'_'.$field["id"];
        $css = isset($field['cssClass']) ? $field['cssClass'] : "";
        $disabled_text = (IS_ADMIN && RG_CURRENT_VIEW != "entry") ? "disabled='disabled'" : "";

        $amount = '';
        $clicked = '';
        if (is_array($value)) {
            $amount = esc_attr(rgget($field["id"].".1", $value));
            $clicked = rgget($field["id"].".5", $value);
        }

        $html = "<div data-equalizer data-equalize-on='medium' id='$field_id' class='ginput_container bb-click-array-".count($field['choices'])." ".esc_attr($css)."'>"."\n";

        if (is_array($field["choices"])) {
            $choice_id = 0;
            $tabindex = GFCommon::get_tabindex();
            foreach($field["choices"] as $choice){
                $id = $field["id"].'_'.$choice_id;

                $field_value = !empty($choice["value"]) || rgar($field, "enableChoiceValue") ? $choice["value"] : $choice["text"];

                if(rgblank($amount) && RG_CURRENT_VIEW != "entry"){
                    $active = rgar($choice,"isSelected") ? "checked='checked'" : "";
                } else {
                    $active = RGFormsModel::choice_value_match($field, $choice, $amount) ? "checked='checked'" : "";
                }

                if ($active) {
                    $amount = $field_value;
                }
                $field_class = $active ? 's-active' : 's-passive';
                if (rgar($field, 'field_bb_click_array_is_product')) {
                    $field_value = GFCommon::to_money($field_value);
                    $field_class .= ' s-currency';
                }

                $value_style = empty($choice["text"]) ? ' style="margin-top: 1.5rem;"' : '';

                $html .= sprintf('<div data-equalizer-watch data-clickarray-value="%s" data-choice-id="%s" class="s-html-wrapper %s" id="%s">', esc_attr($field_value), $choice_id, $field_class, $id);
                $html .= sprintf('<div class="s-html-value"%s>%s</div>', $value_style, $field_value);
                $html .= sprintf("<label for='choice_%s' id='label_%s'>%s</label>", $id, $id, $choice["text"]);
                $html .= '</div>';
                $choice_id++;
            }

            $onblur = !IS_ADMIN ? 'if(jQuery(this).val().replace(" ", "") == "") { jQuery(this).val("'.$other_default_value.'"); }' : '';
            $onkeyup = (empty($field["conditionalLogicFields"]) || IS_ADMIN) ? '' : "onchange='gf_apply_rules(".$field["formId"].",".GFCommon::json_encode($field["conditionalLogicFields"]).");' onkeyup='clearTimeout(__gf_timeout_handle); __gf_timeout_handle = setTimeout(\"gf_apply_rules(".$field["formId"].",".GFCommon::json_encode($field["conditionalLogicFields"]).")\", 300);'";
            $value_exists = RGFormsModel::choices_value_match($field, $field["choices"], $value);
            $other_label = empty($field['field_bb_click_array_other_label']) ? 'My Best Gift' : $field['field_bb_click_array_other_label'];
            $other_class = rgar($field, 'enableOtherChoice') ? '' : 'hide';

            $html .= "<label for='input_{$field["formId"]}_{$field["id"]}_1' class='ginput_bb_click_array_other_label ".$other_class."'>".$other_label."</label>";
            if (rgar($field, 'field_bb_click_array_is_product')) {
                $other_class .= ' ginput_amount gfield_price gfield_price_'.$field['formId'].'_'.$field['id'].'_1 gfield_product_'.$field['formId'].'_'.$field['id'].'_1';
                $amount = str_replace('.00', '', GFCommon::to_money($amount));
            }
            $html .= "<input id='input_{$field["formId"]}_{$field["id"]}_1' name='input_{$field["id"]}_1' type='text' value='".esc_attr($amount)."' class='ginput_bb ginput_click_array_other ".$other_class." ".$field['size']."' onblur='$onblur' $tabindex $onkeyup $disabled_text>";

            $html .= "<input id='input_{$field["formId"]}_{$field["id"]}_5' name='input_{$field["id"]}_5' type='hidden' value='".esc_attr($clicked)."' class='ginput_bb ginput_click_array_clicked'>";
        }
        $html .= "</div>";
        return $html;
    }

    return $input;
}

// Now we execute some javascript technicalities for the field to load correctly
add_action("gform_editor_js", "bb_click_array_field_gform_editor_js");
function bb_click_array_field_gform_editor_js() {
?>
<script type='text/javascript'>

jQuery(document).ready(function($) {
    fieldSettings["bb_click_array"] = ".label_setting, .description_setting, .rules_setting, .admin_label_setting, .choices_setting, .other_choice_setting, .size_setting, .error_message_setting, .css_class_setting, .visibility_setting, .bb_click_array_setting, .bb_click_array_other_setting, .bb_click_array_is_product_setting, .conditional_logic_field_setting, .prepopulate_field_setting";

    //binding to the load field settings event to initialize the checkbox
    $(document).bind("gform_load_field_settings", function(event, field, form){
        $("#field_other_choice").prop("checked", field["enableOtherChoice"] == true);
        BBToggleOther(field["enableOtherChoice"] == true);
        $("#field_bb_click_array_other_label").val(field["field_bb_click_array_other_label"]);
        $("#field_bb_click_array_is_product").prop("checked", field["field_bb_click_array_is_product"] == true);
    });

    $(".gfield_click_array #field_other_choice").on('click', function() {
        BBToggleOther($(this).prop("checked"));
    });
});

function BBToggleOther(enabled) {
    if (enabled) {
        jQuery('.field_selected .bb_click_array_other_setting').show();
        jQuery('.ginput_click_array_other, .ginput_bb_click_array_other_label').removeClass('hidden');
    } else {
        jQuery('.field_selected .bb_click_array_other_setting').hide();
        jQuery('.ginput_click_array_other, .ginput_bb_click_array_other_label').addClass('hidden');
    }
}

function BBUpdateOtherLabel(label) {
    if (label == '')
        label = 'My Best Gift';
    jQuery('.field_selected label.ginput_bb_click_array_other_label').html(label);
}

</script>
<?php
}

// Add custom settings to the bb_click_array field
add_action("gform_field_standard_settings", "bb_click_array_field_settings", 10, 2);
function bb_click_array_field_settings($position, $form_id) {
    if ($position == 1368) { // Immediately after Other option setting
?>
        <li class="bb_click_array_setting field_setting bb_click_array_other_setting">
            <label for="field_bb_click_array_other_label">
                <?php _e("Custom Value Field Label", "bb_click_array"); ?>
                <?php gform_tooltip("field_bb_click_array_other_label"); ?>
            </label>
            <input type="text" class="fieldwidth-3" size="75" id="field_bb_click_array_other_label" onkeyup="SetFieldProperty('field_bb_click_array_other_label', this.value); BBUpdateOtherLabel(this.value);">
        </li>
        <li class="bb_click_array_setting field_setting bb_click_array_is_product_setting">
            <input type="checkbox" name="field_bb_click_array_is_product" id="field_bb_click_array_is_product" onclick="SetFieldProperty('field_bb_click_array_is_product', this.checked);">
            <label for="field_bb_click_array_is_product" class="inline">
                <?php _e("Treat as Product", "bb_click_array"); ?>
                <?php gform_tooltip("field_bb_click_array_is_product"); ?>
            </label>
        </li>
<?php
    }
}

add_action("gform_editor_js_set_default_values", "bb_click_array_default_values");
function bb_click_array_default_values() {
?>
        case 'bb_click_array':
            field.enableChoiceValue = true;
            if(!field.label)
                field.label = "<?php _e("My Donation", "gravityforms"); ?>";

            if(!field.choices)
                field.choices = new Array(new Choice("<?php _e("Story 1", "bb_click_array"); ?>", "1"),
                                          new Choice("<?php _e("Story 2", "bb_click_array"); ?>", "2"),
                                          new Choice("<?php _e("Story 3", "bb_click_array"); ?>", "3"));

            field.inputs = [new Input(field.id + 0.1, '<?php echo esc_js(__("Value", "bb_click_array")); ?>'),
                            new Input(field.id + 0.5, '<?php echo esc_js(__("Clicked", "bb_click_array")); ?>')];
        break;
<?php
}

//Filter to add a new tooltip
add_filter('gform_tooltips', 'bb_click_array_add_field_tooltips');
function bb_click_array_add_field_tooltips($tooltips) {
    $tooltips["field_bb_click_array_other_label"] = "<h6>Custom Value Field Label</h6> Custom label for the user-defined value.";
    $tooltips["field_bb_click_array_is_product"] = "<h6>Treat as Product</h6> If checked, the submitted value will be treated in the same way as a product field.";
    return $tooltips;
}

// Add a script to the display of the particular form only if bb_click_array field is being used
add_action('gform_enqueue_scripts', 'bb_click_array_field_gform_enqueue_scripts', 10, 2);
function bb_click_array_field_gform_enqueue_scripts($form, $ajax) {
    // Cycle through fields to see if bb_click_array is being used
    foreach ($form['fields'] as $field) {
        if ($field['type'] == 'bb_click_array') {
            $url = plugins_url('js/gform_bb_click_array.js', __FILE__);
            wp_enqueue_script("gform_bb_click_array_script", $url, array("jquery"), '1.0');
            $url = plugins_url('css/gform_bb_click_array.css', __FILE__);
            wp_enqueue_style("gform_bb_click_array_style", $url);
            break;
        }
    }
}

add_action('admin_head', 'bb_click_array_enqueue_form_editor_style');
function bb_click_array_enqueue_form_editor_style() {
    if (RGForms::is_gravity_page()) {
        $url = plugins_url('css/gform_bb_click_array.css', __FILE__);
        wp_enqueue_style('gform_bb_click_array_style', $url);
    }
}

// Add a custom class to the field li
add_action("gform_field_css_class", "bb_click_array_custom_field_class", 10, 3);
function bb_click_array_custom_field_class($classes, $field, $form) {
    if ($field["type"] == "bb_click_array") {
        $classes .= ' gform_bb gfield_click_array';
        if (rgar($field, 'field_bb_click_array_is_product')) {
            $classes .= ' ginput_amount gfield_price gfield_price_'.$field['formId'].'_'.$field['id'].'_1 gfield_product_'.$field['formId'].'_'.$field['id'].'_1';
        }
    }

    return $classes;
}

add_filter("gform_entry_field_value", "bb_click_array_field_entry_output", 10, 4);
function bb_click_array_field_entry_output($value, $field, $lead, $form) {
    if ($field["type"] == "bb_click_array"){
        $value = $lead[$field["id"].'.1'];
    }
    return $value;
}

add_filter( 'gform_entries_field_header', function ( $header, $form, $field ) {

    return 'your new header';
}, 10, 3 );

add_filter('gform_field_validation', 'bb_click_array_field_validation', 1, 4);
function bb_click_array_field_validation($result, $value, $form, $field) {
//     if ($field['type'] == 'bb_click_array') {
//         if ($value[$field['id'].'.5'] == 'recurring') {
//             if (empty($value[$field['id'].'.2'])) {
//                 $result['is_valid'] = false;
//                 $result['message'] = __('Recurring transactions require frequency to be specified', 'bb_click_array');
//             }
//         } else {
//             $value[$field['id'].'.2'] == 'one-off';
//         }
//     }
    return $result;
}

add_filter('gform_product_info', 'bb_click_array_add_to_products', 10, 3);
function bb_click_array_add_to_products($product_info, $form, $lead) {
    foreach ($form['fields'] as $field) {
        if ($field->type == 'bb_click_array' && rgar($field, 'field_bb_click_array_is_product')) {
            $product_info['products'][] = array('name' => $field->label, 'price' => $lead[$field['id']], 'quantity' => 1);
        }
    }

    return $product_info;
}
