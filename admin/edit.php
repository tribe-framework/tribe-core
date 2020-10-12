<?php
    include_once '../init.php';
    include_once 'header.php';

    $role = NULL;

    if (isset($_GET['id'])) {
        $post = $dash::get_content($_GET['id']);
    }

    if (
        !(
            $session_user['role']=='admin' ||
            $post['user_id'] == $session_user['user_id'] ||
            !$_GET['id'] || $userless_install
        )
    ):
        echo 'Not allowed. <a href="/admin">Go back</a>.';
    else:
        if (isset($_GET['role'])) {
            $role = $types['user']['roles'][$_GET[role]];
        }

        if (( isset($_GET['id']) && $post['type'] == $type ) || !isset($_GET['id'])):
            //for testing resticted min and max ids for archive format changes
            if (isset($_GET['id']) && !($pid = $_GET['id']))
                $pid=$dash::get_next_id();
?>

        <link rel="stylesheet" type="text/css" href="/plugins/typeout/typeout.css">

        <div class="container">
            <a name="infos"></a>
            <div id="infos" class="d-none alert alert-success"></div>

            <a name="errors"></a>
            <div id="errors" class="d-none alert alert-danger"></div>
        </div>

        <div class="p-3 container d-flex justify-content-center">

            <form method="post" class="edit_form col-lg-11" action="/admin/json" autocomplete="off">
                <?=
                    get_admin_menu(
                        $types[$type]['disallow_editing'] ?
                            'view' :
                            'edit', $type, $role['slug'] ?? '', $_GET['id'] ?? ''
                    );
                ?>

                <h2 class="form_title"><?php echo ($type=='user'?$role['title'].'&nbsp;<small><span class="fas fa-angle-double-right"></span></small>&nbsp;':'').'Edit '.$types[$type]['name']; ?></h2>

                <div class="form-style">
                    <?php include 'form.php'; ?>
                </div>

                <input type="hidden" name="class" value="dash">
                <?php
                if ($role['slug'])
                    echo '<input type="hidden" name="role_slug" value="'.$role['slug'].'">';
                else if ($post['role_slug'])
                    echo '<input type="hidden" name="role_slug" value="'.$post['role_slug'].'">';

                if (($types['webapp']['allow_type_change']??false) && ($types[$type]['type']=='content')) {
                    echo '
                    <div class="form-group mt-5"><select class="form-control pl-0 border-top-0 border-left-0 border-right-0 rounded-0 mt-1" id="select_type" name="type">';
                    if (!($post_type=$post['type']))
                        $post_type=$_GET['type'];
                    foreach ($types as $key => $value) {
                        if ($types[$key]['type']=='content')
                            echo '<option value="'.$types[$key]['slug'].'" '.(($types[$key]['slug']==$post_type)?'selected="selected"':'').'>'.ucfirst($types[$key]['name']).'</option>';
                    }
                    echo '</select><div class="col-12 row text-muted small m-0"><span class="ml-auto mr-0">Change content type (rarely used, use with caution)</div></div>';
                }
                else
                    echo '<input type="hidden" name="type" value="'.$types[$type]['slug'].'">';
                ?>
                <?php echo ($types[$type]['type']=='content'?'<input type="hidden" name="user_id" value="'.($post['user_id']?$post['user_id']:$session_user['user_id']).'">':''); ?>
                <input type="hidden" name="function" value="push_content">
                <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                <input type="hidden" name="slug" value="<?php echo $post['slug']; ?>">

                <?php if (count($types[$type]['modules'])>3) { echo get_admin_menu(($types[$type]['disallow_editing']?'view':'edit'), $type, $role['slug'], $_GET['id']); } ?>
                <p>&nbsp;</p>
            </form>
        </div>

        <div class="modal fade" id="delete_conf_<?php echo $_GET['id']; ?>" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirm</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <span>Are you sure you wish to delete this content?</span>
            </div>
            <div class="modal-footer">
                <form method="post" class="edit_form" action="/admin/json">
                <input type="hidden" name="class" value="dash">
                <input type="hidden" name="function" value="do_delete">
                <input type="hidden" name="type" value="<?php echo $types[$type]['slug']; ?>">
                <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-danger">Yes, delete it</button>
                </form>
            </div>
            </div>
        </div>
        </div>

        <?php endif; ?>

    <?php endif; ?>

<?php include_once 'footer.php'; ?>
