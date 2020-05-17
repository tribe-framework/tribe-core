	<footer class="pt-4 my-5 pt-md-5 bg-white">
		<hr class="hr">
		<div class="container pb-5">
		    <div class="row">
		      <div class="col-md">
		        <img class="my-2 w-20" src="/admin/img/logo-flame.png"><br>
		        <p class="text-muted">&copy; Wildfire <?php echo (date('Y')=='2020'?date('Y'):'2020 - '.date('Y')); ?></p>
		      </div>
		      <div class="col-md">
		        <h5>Resources</h5>
		        <?php echo $theme->get_menu('footer', array('ul'=>'list-unstyled text-small', 'li'=>'', 'a'=>'')); ?>
		      </div>
		      <div class="col-md">
		        <h5>About</h5>
		        <?php echo $theme->get_menu('footer', array('ul'=>'list-unstyled text-small', 'li'=>'', 'a'=>'')); ?>
		      </div>
		    </div>
		</div>
	</footer>

	<script src="/plugins/jquery.min.js"></script>
	<script src="/plugins/popper/popper.min.js"></script>
	<script src="/plugins/moment.js"></script>
	<script src="/plugins/bootstrap/dist/js/bootstrap.min.js"></script>
	<script src="/plugins/typeout/typeout.js?v=<?php echo time(); ?>"></script>
	<script src="/plugins/datatables/datatables.min.js"></script>
	<script src="/plugins/clipboard.min.js"></script>
	<script src="/plugins/keymaster.js"></script>
	<script src="<?php echo BASE_URL; ?>/admin/js/custom.js?v=<?php echo time(); ?>"></script>

	<script src="https://blueimp.github.io/jQuery-File-Upload/js/vendor/jquery.ui.widget.js"></script>
	<script src="https://blueimp.github.io/jQuery-File-Upload/js/jquery.iframe-transport.js"></script>
	<script src="https://blueimp.github.io/jQuery-File-Upload/js/jquery.fileupload.js"></script>
    <?php if (!empty(GOOGLE_MAP_API_KEY_1))	echo '<script src="https://maps.googleapis.com/maps/api/js?key='.GOOGLE_MAP_API_KEY_1.'&libraries=places&callback=initMap" async defer></script>'; ?>
</body>
</html>