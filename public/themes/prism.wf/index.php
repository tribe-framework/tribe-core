<?php include_once 'header.php'; ?>

<header>
  <div class="collapse bg-dark" id="navbarHeader">
    <div class="container">
      <div class="row">
        <div class="col-12 py-4">
          <h4 class="text-white">About Prism</h4>
          <p class="text-muted">Add some information about the tool below, the author, or any other background context. Make it a few sentences long so folks can pick up some informative tidbits. Then, link them off to some social networking sites or contact information.</p>
        </div>
      </div>
    </div>
  </div>
  <div class="navbar navbar-dark bg-dark shadow-sm">
    <div class="container d-flex justify-content-between">
      <a href="/" class="navbar-brand d-flex align-items-center">
        <span class="fas fa-chart-area"></span>&nbsp;Prism</strong>
      </a>
      <button class="navbar-toggler border-0" type="button" data-toggle="collapse" data-target="#navbarHeader" aria-controls="navbarHeader" aria-expanded="false" aria-label="Toggle navigation">
        <span class="fas fa-info-circle"></span>
      </button>
    </div>
  </div>
</header>

<main role="main">

  <section class="jumbotron bg-light">
    <div class="container">
      <h1><?php echo $types['webapp']['headmeta_description']; ?></h1>
      <p class="lead text-muted">Something short and leading about the collection below—its contents, the creator, etc. Make it short and sweet, but not too short so folks don’t simply skip over it entirely.</p>

      <div class="col-12 mt-5 mx-auto p-0 m-0">
          <?php
            if ($_POST) {
              $_POST['content_privacy']='public';
              $dash->push_content($_POST);
              echo '
              <div class="alert alert-success" role="alert">
                <h4 class="alert-heading">Well done!</h4>
                <p>Use this code at the end of your HTML\'s closing <code>&lt;/head&gt;</code> tag.</p>
                <hr>
                <p class="mb-0"><code>&lt;script&gt;var app_key = \''.$_POST['app_key'].'\';&lt;/script&gt;&lt;script src=\'https://cdn.prism.wf/trac.v1.js\'&gt;&lt;/script&gt;</code><br><button class="copy_btn btn btn-sm" data-clipboard-text="&lt;script&gt;var app_key = \''.$_POST['app_key'].'\';&lt;/script&gt;&lt;script src=\'https://cdn.prism.wf/trac.v1.js\'&gt;&lt;/script&gt;" type="button"><span class="fas fa-copy"></span>&nbsp; Copy script</button></p>
              </div>';
            } else {
              $app_key=uniqid(); $app_secret=time().uniqid();
          ?>
          <form method="post" action="/">
              <input name="app_key" type="hidden" value="<?php echo $app_key; ?>">
              <input name="app_secret" type="hidden" value="<?php echo $app_secret; ?>">
              <input name="type" type="hidden" value="app">
            <div class="form-group">
              <input type="text" class="form-control" name="title" id="title">
              <small class="form-text text-muted">Enter your app's name</small>
            </div>
            <button type="submit" class="btn btn-primary">Get analytics script</button>
          </form>
          <?php } ?>
      </div>
    </div>
  </section>

</main>

<footer class="text-muted mt-auto">
  <div class="container">
    <p>Built by <a href="https://wildfire.wf">wildfire</a>.</p>
  </div>
</footer>

<?php include_once (THEME_PATH.'/footer.php'); ?>
