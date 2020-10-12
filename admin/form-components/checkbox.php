<div class="input-group mt-5">
                    <div class="custom-control custom-checkbox">
                    <input type="checkbox" name="<?php echo $module_input_slug_lang; ?>" class="custom-control-input" id="customCheck_<?php echo $module_input_slug_lang; ?>" value="1" <?php echo ($post[$module_input_slug_lang]?'checked="checked"':''); ?>>
                    <label class="custom-control-label" for="customCheck_<?php echo $module_input_slug_lang; ?>"><?php echo ($module_input_placeholder??''); ?></label>
                    </div>
                </div>
