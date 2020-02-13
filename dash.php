<?php
include_once ('config-init.php');
include_once ('header.php');
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
	<a class="navbar-brand" href="/dash.php"><span class="fas fa-tachometer-alt"></span>&nbsp;Dashboard</a>
	<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation"><span class="navbar-toggler-icon"></span></button>
	<div class="collapse navbar-collapse" id="navbarNav">
		<ul class="navbar-nav">
			<?php if ($dash->is_admin($_SESSION['updated_by'])) echo '<li class="nav-item"><a class="nav-link" href="section.php?section_list=1">Sections</a></li>'; ?>
			<?php if ($dash->is_admin($_SESSION['updated_by'])) echo '<li class="nav-item"><a class="nav-link" href="user.php?user_list=1">Users</a></li>'; ?>
			<?php if ($dash->is_moderator($_SESSION['updated_by'], 2)) echo '<li class="nav-item"><a class="nav-link" href="lang.php?lang_list=1">Language</a></li>'; ?>
			<?php if ($dash->is_moderator($_SESSION['updated_by'], 4)) echo '<li class="nav-item"><a class="nav-link" href="sos.php?sos_list=1">SOS</a></li>'; ?>
			<?php if ($dash->is_moderator($_SESSION['updated_by'], 3)) echo '<li class="nav-item"><a class="nav-link" href="calendar.php?calendar_list=1">Calendar</a></li>'; ?>
			<?php if ($dash->is_moderator($_SESSION['updated_by'], 5)) echo '<li class="nav-item"><a class="nav-link" href="single.php?single_list=1">Readings</a></li>'; ?>
		</ul>
	</div>
</nav>

<?php include_once ('footer.php'); ?>