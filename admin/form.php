<?php
$post = $post ?? NULL; // set $post to NULL if it doesn't exist

function fc ($v) {
    return 'form-components/'.$v.'.php';
}

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

            switch ($module_input_type) {
                case 'text':
                case 'multi-text':
                    include_once fc('text');
                    break;

                case 'textarea':
                    include_once fc('textarea');
                    break;

                case 'typeout':
                    include_once fc('typeout');
                    break;

                case 'date':
                    include_once fc('date');
                    break;

                case 'url':
                case 'multi_url':
                    include_once fc('url');
                    break;

                case 'number':
                case 'multi_number':
                    include_once fc('number');
                    break;

                case 'checkbox':
                    include_once fc('checkbox');
                    break;

                case 'tel':
                    include_once fc('tel');
                    break;

                case 'hidden':
                    include_once fc('hidden');
                    break;

                case 'priority':
                    include_once fc('priority');
                    break;

                case 'email':
                    include_once fc('email');
                    break;

                case 'password':
                    include_once fc('password');
                    break;

                case 'select':
                    include_once fc('select');
                    break;

                case 'multi_drop':
                    include_once fc('multi-drop');
                    break;

                case 'multi_select':
                    include_once fc('multi-select');
                    break;

                case 'file_uploader':
                    include_once fc('file-uploader');
                    break;

                case 'google_map_marker':
                    include_once fc('google-map-marker');
                    break;

                default:
                    echo $module_input_type . " isn't valid.";
                    break;
            }
        }
    }
}
?>
