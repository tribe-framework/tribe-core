<?php
include_once ('config-init.php');
include_once ('header.php');
?>

<div class="jumbotron text-white rounded-0 bg-dark px-0">
	<div class="col-md-9 px-2 m-auto mt-0"><h1 class="display-4"><span class="fas fa-video"></span>&nbsp;Title of a longer featured blog post</h1></div>
</div>
<div class="col-md-9 px-2 m-auto mt-0"><h1 class="display-4"><span class="fas fa-newspaper"></span>&nbsp;Title of a longer featured blog post</h1></div>
<div class="col-md-9 px-2 m-auto mt-0">
<p class="blog-post-meta my-4">January 1, 2014</p>
<p class="lead my-4">Multiple lines of text that form the lede, informing new readers quickly and efficiently about what’s most interesting in this post’s contents.</p>

<div class="card my-4">
    <div id="ytplayer" class="w-100"></div>
</div>

<script>
	var element = document.getElementById('ytplayer');
	var positionInfo = element.getBoundingClientRect();
	var width = positionInfo.width;
	var height = (width/1.77);
  // Load the IFrame Player API code asynchronously.
  var tag = document.createElement('script');
  tag.src = "https://www.youtube.com/player_api";
  var firstScriptTag = document.getElementsByTagName('script')[0];
  firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);

  // Replace the 'ytplayer' element with an <iframe> and
  // YouTube player after the API code downloads.
  var player;
  function onYouTubePlayerAPIReady() {
    player = new YT.Player('ytplayer', {
      height: height,
      width: width,
      videoId: 'IQ6UWs0lod0'
    });
  }
</script>

<p class="my-4">There are times when reading data from the DOM is simply too slow or unwieldy, particularly when dealing with many thousands or millions of data rows. To address this DataTables' server-side processing feature provides a method to let all the "heavy lifting" be done by a database engine on the server-side (they are after all highly optimised for exactly this use case!), and then have that information drawn in the user's web-browser. Consequently, you can display tables consisting of millions of rows with ease. There are times when reading data from the DOM is simply too slow or unwieldy, particularly when dealing with many thousands or millions of data rows. To address this DataTables' server-side processing feature provides a method to let all the "heavy lifting" be done by a database engine on the server-side (they are after all highly optimised for exactly this use case!), and then have that information drawn in the user's web-browser. Consequently, you can display tables consisting of millions of rows with ease.</p>
<p class="lead mb-0"><a href="#" class="text-white font-weight-bold">Continue reading...</a></p>
</div>

<?php include_once ('footer.php'); ?>