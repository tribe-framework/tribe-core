<div class="clearfix form-group mt-5"><?php echo ($module_input_placeholder?$module_input_placeholder:'Select '.$module_input_slug_lang); ?><br><div class="float-left multi_drop w-50"><div class="table multi_drop_filled" id="multi_drop_filled_table_<?php echo $module_input_slug_lang; ?>"><div class="w-100 grid"></div></div></div><div class="float-left multi_drop w-50 pl-2"><table class="table multi_drop_select_table"><thead><th>Options</th></thead><tbody>
                    <?php
                    if ($options=$module_input_options) {
                        $i=0;
                        foreach ($options as $opt) {
                            $i++;
                            if (is_array($opt)) {
                                echo '
                                <tr><td class="grid-item p-3" data-name="'.$module_input_slug_lang.'[]" id="'.$module_input_slug_lang.'_customSwitch_'.$i.'" data-value="'.$opt['slug'].'" '.(in_array($opt['slug'], $post[$module_input_slug_lang])?'data-checked="checked"':'').'><span id="multi_drop_option_text_'.$module_input_slug_lang.'_'.$i.'">'.$opt['title'].'</span> <a href="#" class="float-right select_multi_drop_option" data-multi_drop_option_text="multi_drop_option_text_'.$module_input_slug_lang.'_'.$i.'" data-multi_drop_filled_table="multi_drop_filled_table_'.$module_input_slug_lang.'"><span class="fas fa-chevron-circle-left"></span></a></td></tr>';
                            }
                            else {
                                echo '
                                <tr><td class="grid-item p-3" data-name="'.$module_input_slug_lang.'[]" id="'.$module_input_slug_lang.'_customSwitch_'.$i.'" data-value="'.$opt.'" '.(in_array($opt, $post[$module_input_slug_lang])?'data-checked="checked"':'').'><span id="multi_drop_option_text_'.$module_input_slug_lang.'_'.$i.'">'.$opt.'</span> <a href="#" class="float-right select_multi_drop_option" data-multi_drop_option_text="multi_drop_option_text_'.$module_input_slug_lang.'_'.$i.'" data-multi_drop_filled_table="multi_drop_filled_table_'.$module_input_slug_lang.'"><span class="fas fa-chevron-circle-left"></span></a></td></tr>';
                            }
                        }
                    }
                    else {
                        $options=$dash::get_all_ids($module_input_slug_lang, $types[$module_input_slug_lang]['primary_module'], 'ASC');
                        $i=0;
                        foreach ($options as $opt) {
                            $i++;
                            $option=$dash::get_content($opt['id']);
                            $titler=$dash->get_type_title_data($option);
                            $title_slug=$titler['slug'];
                            echo '
                            <tr><td class="grid-item p-3" data-name="'.$module_input_slug_lang.'[]" id="'.$module_input_slug_lang.'_customSwitch_'.$i.'" data-value="'.$option['slug'].'" '.(in_array($option['slug'], $post[$module_input_slug_lang])?'data-checked="checked"':'').'><span id="multi_drop_option_text_'.$module_input_slug_lang.'_'.$i.'">'.$option[$title_slug].' (ID: '.$opt['id'].')</span> <a href="#" class="float-right select_multi_drop_option" data-multi_drop_option_text="multi_drop_option_text_'.$module_input_slug_lang.'_'.$i.'" data-multi_drop_filled_table="multi_drop_filled_table_'.$module_input_slug_lang.'"><span class="fas fa-chevron-circle-left"></span></a></td></tr>';
                        }
                    }
                    ?>
                </tbody></table></div></div>
