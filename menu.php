<?php
$items=json_decode(file_get_contents('menu.json', true));
?>

<nav class="navbar navbar-expand-lg navbar-light bg-light">
	<a class="navbar-brand" href="<?php echo $items->logo->href; ?>" title="<?php echo $items->logo->title; ?>"><?php echo ($items->logo->src?'<img height="'.$items->logo->height.'" src="'.$items->logo->src.'">':$items->logo->name); ?></a>
		<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent">
		<span class="navbar-toggler-icon"></span>
	</button>

	<div class="collapse navbar-collapse" id="navbarSupportedContent">
		<ul class="navbar-nav ml-auto mr-0">
			<?php 
			foreach ($items->menu as $item) {
				if (empty($item->submenu))
					echo '<li class="nav-item active"><a class="nav-link" href="'.$item->href.'" title="'.$item->title.'">'.$item->name.'</a></li>';
				else {
					echo '<li class="nav-item dropdown"><a class="nav-link dropdown-toggle" href="#" title="'.$item->title.'" role="button" data-toggle="dropdown">'.$item->name.'
						</a><div class="dropdown-menu dropdown-menu-right">';
					foreach ($item->submenu as $subitem)
						echo '<a class="dropdown-item" href="'.$subitem->href.'" title="'.($subitem->title?$subitem->title:'').'">'.$subitem->name.'</a>';
					echo '</div></li>';
				}
			} ?>
		</ul>
	</div>
</nav>