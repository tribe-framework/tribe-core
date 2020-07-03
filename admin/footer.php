	<footer class="pt-4 pt-md-5 bg-white">
		<hr class="bg-primary" style="background-image: none;">
		<div class="container my-5">
		    <div class="row">
		      <div class="col-md">
		        <a href="https://wildfirego.com"><img class="w-40" src="/admin/img/logo.png"></a>
		        <p class="text-muted small mb-3 mt-4 pr-5">Made with <span class="fas fa-heart"></span><?php echo ($types['webapp']['headmeta_title']?'<br><em>for '.$types['webapp']['headmeta_title'].'</em>':''); ?></p><p class="text-muted small my-3 pr-5">Wildfire is a technology consultancy based in New Delhi, India</p><p class="text-muted small my-3 pr-5">&copy; <?php echo (date('Y')=='2020'?date('Y'):'2020 - '.date('Y')); ?></p>
		      </div>
		      <div class="col-md">
		        <?php echo $theme->get_menu($admin_menus['admin_footer_1'], array('ul'=>'list-unstyled mt-5 pt-2 pl-md-5', 'li'=>'', 'a'=>'small')); ?>
		      </div>
		      <div class="col-md">
		        <?php echo $theme->get_menu($admin_menus['admin_footer_2'], array('ul'=>'list-unstyled mt-5 pt-2 pl-md-5', 'li'=>'', 'a'=>'small')); ?>
		      </div>
		    </div>
		</div>
	</footer>

	<script src="/plugins/jquery.min.js"></script>
	<script src="/plugins/popper/popper.min.js"></script>
	<script src="/plugins/moment.js"></script>
	<script src="/plugins/bootstrap/dist/js/bootstrap.min.js"></script>
	<script src="/plugins/typeout/typeout.js"></script>
	<script src="/plugins/datatables/datatables.min.js"></script>
	<script src="/plugins/clipboard.min.js"></script>
	<script src="/plugins/keymaster.js"></script>
	<script src="<?php echo BASE_URL; ?>/admin/js/custom.js"></script>

	<script src="https://unpkg.com/packery@2/dist/packery.pkgd.min.js"></script>

	<script src="https://cdn.datatables.net/buttons/1.6.2/js/dataTables.buttons.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/1.6.2/js/buttons.flash.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
	<script src="https://cdn.datatables.net/buttons/1.6.2/js/buttons.html5.min.js"></script>
	<script src="https://cdn.datatables.net/buttons/1.6.2/js/buttons.print.min.js"></script>

	<script src="https://blueimp.github.io/jQuery-File-Upload/js/vendor/jquery.ui.widget.js"></script>
	<script src="https://blueimp.github.io/jQuery-File-Upload/js/jquery.iframe-transport.js"></script>
	<script src="https://blueimp.github.io/jQuery-File-Upload/js/jquery.fileupload.js"></script>
    <?php if (!empty(GOOGLE_MAP_API_KEY_1))	echo '<script src="https://maps.googleapis.com/maps/api/js?key='.GOOGLE_MAP_API_KEY_1.'&libraries=places&callback=initMap" async defer></script>'; ?>

    <script src="<?php echo BASE_URL; ?>/admin/js/list.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo BASE_URL; ?>/admin/js/edit.js?v=<?php echo time(); ?>"></script>
</body>
</html>