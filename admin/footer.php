
	<script src="/plugins/jquery.min.js"></script>
	<script src="/plugins/popper/popper.min.js"></script>
	<script src="/plugins/moment.js"></script>
	<script src="/plugins/bootstrap/js/bootstrap.min.js"></script>
	<script src="/plugins/typeout/typeout.js"></script>
	<script src="/plugins/datatables/datatables.min.js"></script>
	<script src="/plugins/clipboard.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-confirm/3.3.2/jquery-confirm.min.js"></script>
	<script src="<?php echo BASE_URL; ?>/admin/js/custom.js?v=<?php echo time(); ?>"></script>

	<script src="https://blueimp.github.io/jQuery-File-Upload/js/vendor/jquery.ui.widget.js"></script>
	<script src="https://blueimp.github.io/jQuery-File-Upload/js/jquery.iframe-transport.js"></script>
	<script src="https://blueimp.github.io/jQuery-File-Upload/js/jquery.fileupload.js"></script>
    <?php if (!empty(GOOGLE_MAP_API_KEY_1))	echo '<script src="https://maps.googleapis.com/maps/api/js?key='.GOOGLE_MAP_API_KEY_1.'&libraries=places&callback=initMap" async defer></script>'; ?>
</body>
</html>