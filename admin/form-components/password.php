<div class="input-group mt-5">
                <div class="input-group-prepend">
                    <span class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0" id="basic-addon1"><span class="fas fa-key"></span></span>
                </div>
                <input autocomplete="off" type="password" name="<?php echo $module_input_slug_lang; ?>" class="form-control border-top-0 border-left-0 border-right-0 rounded-0 m-0" placeholder="<?php echo ($module_input_placeholder?$module_input_placeholder:ucfirst($types[$type]['name']).' '.$module_input_slug_lang); ?>">
                <?php
                if ($post[$module_input_slug_lang])
                    echo '<small class="col-12 row form-text text-muted">To keep the password unchanged, leave this field empty</small>'; ?>
                <?php //important step for password_md5, connected with $dash->push_content ?>
                <input type="hidden" name="<?php echo $module_input_slug_lang; ?>_md5" value="<?php echo ($post[$module_input_slug_lang]?$post[$module_input_slug_lang]:$module_input_default_value); ?>">
                </div>
                <?php echo ($module_input_placeholder?'<div class="col-12 row text-muted small m-0"><span class="ml-auto mr-0">'.$module_input_placeholder.'</span></div>':''); ?>
