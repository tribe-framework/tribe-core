<div class="form-group mt-5"><?php echo ($module_input_placeholder?$module_input_placeholder:'Select '.$module_input_slug_lang); ?>
                    <?php
                    if ($options=$module_input_options) {
                        $i=0;
                        foreach ($options as $opt) {
                            $i++;
                            if (is_array($opt)) {
                                echo '
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" name="'.$module_input_slug_lang.'[]" id="'.$module_input_slug_lang.'_customSwitch_'.$i.'" value="'.$opt['slug'].'" '.(in_array($opt['slug'], $post[$module_input_slug_lang])?'checked="checked"':'').'>
                                    <label class="custom-control-label" for="'.$module_input_slug_lang.'_customSwitch_'.$i.'">'.$opt['title'].'</label>
                                </div>';
                            }
                            else {
                                echo '
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" name="'.$module_input_slug_lang.'[]" id="'.$module_input_slug_lang.'_customSwitch_'.$i.'" value="'.$opt.'" '.(in_array($opt, $post[$module_input_slug_lang])?'checked="checked"':'').'>
                                    <label class="custom-control-label" for="'.$module_input_slug_lang.'_customSwitch_'.$i.'">'.$opt.'</label>
                                </div>';
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
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" name="'.$module_input_slug_lang.'[]" id="'.$module_input_slug_lang.'_customSwitch_'.$i.'" value="'.$option['slug'].'" '.(in_array($option['slug'], $post[$module_input_slug_lang])?'checked="checked"':'').'>
                                <label class="custom-control-label" for="'.$module_input_slug_lang.'_customSwitch_'.$i.'">'.$option[$title_slug].' (ID: '.$opt['id'].')</label>
                            </div>';
                        }
                    }
                    ?>
                </div>
