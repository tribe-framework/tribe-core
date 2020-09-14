<div class="input-group mt-5">
                <div class="input-group-prepend">
                    <span class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0" id="basic-addon1"><span class="fas fa-sort-numeric-up"></span></span>
                </div>
                <input type="number" name="<?php echo $module_input_slug_lang; ?>" class="form-control border-top-0 border-left-0 border-right-0 rounded-0 m-0" min="<?php echo (isset($module['input_min'])?$module['input_min']:''); ?>" max="<?php echo (isset($module['input_max'])?$module['input_max']:''); ?>"  placeholder="<?php echo ($module_input_placeholder?$module_input_placeholder:ucfirst($types[$type]['name']).' '.$module_input_slug_lang); ?>" value="<?php echo ($post[$module_input_slug_lang]?$post[$module_input_slug_lang]:$module_input_default_value); ?>">
                <?php echo ($module_input_placeholder?'<div class="col-12 row text-muted small m-0"><span class="ml-auto mr-0">'.$module_input_placeholder.'</span></div>':''); ?>
                </div>
                <?php echo ($module_input_placeholder?'<div class="col-12 row text-muted small m-0"><span class="ml-auto mr-0">'.$module_input_placeholder.'</span></div>':''); ?>
