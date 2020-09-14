<?php
$post = $post ?? NULL; // set $post to NULL if it doesn't exist

function formComponent ($v) {
    return 'form-components/'.$v.'.php';
}

$components = [
    'text' => 'text',
    'multi-text' => 'text',
    'textarea' => 'textarea',
    'typeout' => 'typeout',
    'date' => 'date',
    'url' => 'url',
    'multi_url' => 'url',
    'number' => 'number',
    'multi_number' => 'number',
    'checkbox' => 'checkbox',
    'tel' => 'tel',
    'hidden' => 'hidden',
    'priority' => 'priority',
    'email' => 'email',
    'password' => 'password',
    'select' => 'select',
    'multi_drop' => 'multi-drop',
    'multi_select' => 'multi-select',
    'file_uploader' => 'file-uploader',
    'google_map_marker' => 'google-map-marker'
];

foreach ($types[$type]['modules'] as $module) {
    if (
        (isset($module['restrict_id_max']) ? $pid<=$module['restrict_id_max'] : true) &&
        (isset($module['restrict_id_min']) ? $pid>=$module['restrict_id_min'] : true)
    ) {

        if (isset($module['restrict_to_roles']) && !in_array($role['slug'], $module['restrict_to_roles'])) {
            continue;
        }

        $module_input_slug = $module['input_slug'] ?? NULL;
        $module_input_type = $module['input_type'] ?? NULL;
        $module_input_lang = $module['input_lang'] ?? NULL;
        $module_input_primary = $module['input_primary'] ?? NULL;
        $module_input_options = $module['input_options'] ?? NULL;
        $module_input_placeholder = $module['input_placeholder'] ?? NULL;
        $slug_displayed = 0;

        $module_input_slug_arr = array();

        if (is_array($module_input_lang)):
            $module_input_slug_arr = $module_input_lang;
        else:
            $module_input_slug_arr[0]['slug'] = '';
        endif;

        foreach ($module_input_slug_arr as $input_lang) {
            $module_input_slug_lang = $module_input_slug.($input_lang['slug']?'_'.$input_lang['slug']:'');
            $module_input_default_value = '';
            $module_autofill = $module['autofill'] ?? NULL;

            if ($module_autofill == 'user_id') {
                $module_input_default_value = $dash->get_unique_user_id();
            }

            if (array_key_exists($module_input_type, $components)) {
                include formComponent($components[$module_input_type]);
            } else {
                echo '<em style="color:red; border-left:2px solid red; padding: 2px 8px;">"'. $module_input_type . '"' . ': form-component not found</em><br/>';
            }
        }
    }
}
?>
