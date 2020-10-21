<div class="text-group" id="text-group-<?= $module_input_slug_lang; ?>">
    <?php
    $i=0;
    $type_name_values = array();
    if (is_array($post[$module_input_slug_lang])) {
        $type_name_values=$post[$module_input_slug_lang];
    } elseif ($post[$module_input_slug_lang]) {
        $type_name_values[0]=$post[$module_input_slug_lang];
    } else {
        $type_name_values[0]=$module_input_default_value;
    }

    foreach ($type_name_values as $type_name_value):
        if ($i<1 || trim($type_name_value)):
    ?>
            <div class="input-group mt-5">
                <div class="input-group-prepend">
                    <span
                        class="input-group-text border-top-0 border-left-0 border-right-0 rounded-0"
                        id="basic-addon1"
                    >
                        <span class="fas fa-align-justify"></span>
                    </span>
                </div>
                <input
                    type="text"
                    name="<?= $module_input_slug_lang.($module_input_type=='multi-text'?'[]':''); ?>"
                    class="form-control border-top-0 border-left-0 border-right-0 rounded-0 m-0"
                    placeholder="<?= $module_input_placeholder ?
                        $module_input_placeholder :
                        ucfirst($types[$type]['name']).' '.$module_input_slug_lang;
                    ?>"
                    value="<?= $type_name_value; ?>"
                >
                <?php if ($module_input_type == 'multi-text'): ?>
                    <div
                        class="input-group-append multi_add_btn"
                        data-group-class="text-group"
                        data-input-slug="<?= $module_input_slug_lang ?>"
                    >
                        <button class="btn btn-outline-primary" type="button">
                            <span class="fas fa-plus"></span>
                        </button>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($module_input_placeholder): ?>
                <div class="col-12 row text-muted small m-0">
                    <span class="ml-auto mr-0"><?= $module_input_placeholder ?></span>
                </div>
            <?php endif; ?>

            <?php
            if ($module_input_primary && $module_input_type!='multi-text' && !$slug_displayed):
                $slug_displayed=1;
            ?>
                <div class="input-group">
                    <div
                        id="slug_update_div"
                        class="custom-control custom-switch <?= $_GET['id'] ? 'd-block' : 'd-none'?>"
                    >
                        <input type="checkbox" class="custom-control-input" name="slug_update" id="slug_update" value="1">
                        <label class="custom-control-label" for="slug_update">
                            Update the URL slug based on title (will change the link)
                            <span id="title-slug" class="text-muted ml-4"><em><?= '/'.$post['slug'] ?></em></span>
                        </label>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        <?php $i++; ?>
    <?php endforeach; ?>
</div>
