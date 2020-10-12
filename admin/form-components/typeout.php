<div class="typeout-menu mt-5">
                    <?php if (in_array('fullScreen', $module_input_options)) { ?>
                    <button type="button" data-expanded="0" class="btn btn-outline-primary border-0 rounded-0 mt-1 typeout typeout-fullscreen" data-toggle="tooltip" data-placement="top" title="full screen"><span class="fas fa-compress"></span></button>
                    <?php } ?>

                    <?php if (in_array('undo', $module_input_options)) { ?>
                    <button type="button" class="btn btn-outline-primary border-0 rounded-0 mt-1 typeout typeout-exec typeout-undo" data-typeout-command="undo" data-toggle="tooltip" data-placement="top" title="undo"><span class="fas fa-undo"></span></button>
                    <?php } ?>

                    <?php if (in_array('insertParagraph', $module_input_options)) { ?>
                    <button type="button" class="btn btn-outline-primary border-0 rounded-0 mt-1 typeout typeout-exec typeout-insertParagraph" data-typeout-command="insertParagraph" data-toggle="tooltip" data-placement="top" title="insert paragraph break"><span class="fas fa-paragraph"></span></button>
                    <?php } ?>

                    <?php if (in_array('heading', $module_input_options)) { ?>
                    <button type="button" class="btn btn-outline-primary border-0 rounded-0 mt-1 typeout typeout-input-exec typeout-heading" data-typeout-command="heading" data-typeout-info="h4" data-toggle="tooltip" data-placement="top" title="heading"><span class="fas fa-heading"></span></button>
                    <?php } ?>

                    <?php if (in_array('bold', $module_input_options)) { ?>
                    <button type="button" class="btn btn-outline-primary border-0 rounded-0 mt-1 typeout typeout-exec typeout-bold" data-typeout-command="bold" data-toggle="tooltip" data-placement="top" title="bold"><span class="fas fa-bold"></span></button>
                    <?php } ?>

                    <?php if (in_array('italic', $module_input_options)) { ?>
                    <button type="button" class="btn btn-outline-primary border-0 rounded-0 mt-1 typeout typeout-exec typeout-italic" data-typeout-command="italic" data-toggle="tooltip" data-placement="top" title="italic"><span class="fas fa-italic"></span></button>
                    <?php } ?>

                    <?php if (in_array('createLink', $module_input_options)) { ?>
                    <button type="button" class="btn btn-outline-primary border-0 rounded-0 mt-1 typeout typeout-input" data-typeout-command="createLink" data-typeout-info="Enter link URL" data-toggle="tooltip" data-placement="top" title="create link"><span class="fas fa-link"></span></button>
                    <?php } ?>

                    <?php if (in_array('unlink', $module_input_options)) { ?>
                    <button type="button" class="btn btn-outline-primary border-0 rounded-0 mt-1 typeout typeout-exec typeout-unlink" data-typeout-command="unlink" data-toggle="tooltip" data-placement="top" title="un-link"><span class="fas fa-unlink"></span></button>
                    <?php } ?>

                    <?php if (in_array('insertImage', $module_input_options)) { ?>
                    <button type="button" class="btn btn-outline-primary border-0 rounded-0 mt-1 typeout typeout-input" data-typeout-command="insertImage" data-typeout-info="Enter image URL" data-toggle="tooltip" data-placement="top" title="insert image"><span class="fas fa-image"></span></button>
                    <?php } ?>

                    <?php if (in_array('insertPDF', $module_input_options)) { ?>
                    <button type="button" class="btn btn-outline-primary border-0 rounded-0 mt-1 typeout typeout-input" data-typeout-command="insertPDF" data-typeout-info="Enter PDF URL" data-toggle="tooltip" data-placement="top" title="insert PDF"><span class="fas fa-file-pdf"></span></button>
                    <?php } ?>

                    <?php if (in_array('insertHTML', $module_input_options)) { ?>
                    <button type="button" class="btn btn-outline-primary border-0 rounded-0 mt-1 typeout typeout-input" data-typeout-command="insertHTML" data-typeout-info="Enter HTML" data-toggle="tooltip" data-placement="top" title="insert HTML"><span class="fas fa-code"></span></button>
                    <?php } ?>

                    <?php if (in_array('attach', $module_input_options)) { ?>
                    <button type="button" class="btn btn-outline-primary border-0 rounded-0 mt-1 typeout typeout-input" data-typeout-command="attach" data-typeout-info="" data-toggle="tooltip" data-placement="top" title="add attachment"><span class="fas fa-paperclip"></span></button>
                    <?php } ?>

                    <?php if (in_array('removeFormat', $module_input_options)) { ?>
                    <button type="button" class="btn btn-outline-primary border-0 rounded-0 mt-1 typeout typeout-exec" data-typeout-command="removeFormat" data-toggle="tooltip" data-placement="top" title="remove formatting"><span class="fas fa-remove-format"></span></button>
                    <?php } ?>
                </div>

                <div class="typeout-content mt-5 border-bottom" id="typeout-<?php echo $module_input_slug_lang; ?>" data-input-slug="<?php echo $module_input_slug_lang; ?>" contenteditable="true" style="overflow: auto;" placeholder="<?php echo ($module_input_placeholder?$module_input_placeholder:ucfirst($types[$type]['name']).' '.$module_input_slug_lang); ?>"><?php echo ($post[$module_input_slug_lang]?$post[$module_input_slug_lang]:$module_input_default_value); ?></div>
                <input type="hidden" name="<?php echo $module_input_slug_lang; ?>">

                <?php echo ($module_input_placeholder?'<div class="col-12 row text-muted small m-0"><span class="ml-auto mr-0">'.$module_input_placeholder.'</span></div>':''); ?>
