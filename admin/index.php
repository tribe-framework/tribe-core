<?php
include_once '../init.php';
include_once 'header.php';
?>

<div class="p-3">
    <?= get_admin_menu('dash'); ?>

    <div class="card-group m-0">
    <div class="card my-2">
        <div class="card-header">Analytics</div>
            <div class="card-body">
                <?php
                    include_once ABSOLUTE_PATH.'/plugins/prism/trac.class.php';
                    $trac = new Trac();
                ?>
                <div class='stat-table'>
                    <h5 class="border-bottom">Stats for last 24 hours</h5>
                    <div class="pl-4 pr-4 pt-2 pb-2">
                        <div>
                            <?php $visit_day = $trac->get_unique_visits(); ?>
                            <div class='stat-row'>
                                <span>Total Unique visits</span>
                                <span><?= $visit_day['visit_count'] ?></span>
                            </div>
                        </div>

                        <div>
                            <?php $vdata = $trac->get_page_visits(); ?>
                            <div class='stat-row'>
                                <span>Total Visits</span>
                                <span><?= $vdata['visit_count'] ?></span>
                            </div>
                        </div>

                        <div>
                            <h6 class="border-bottom mt-4">Average time per page</h6>
                            <div class="pl-4 pr-4 pt-2 pb-2">
                                <div class="stat-row p-1 border-bottom border-success">
                                    <span>Page</span>
                                    <span>Time</span>
                                </div>
                                <?php
                                    $vdat = $trac->get_avg_time_per_page();
                                    foreach($vdat as $vd):
                                ?>
                                    <div class='stat-row p-1 border-bottom border-light'>
                                        <span><?= $vd['page'] ?></span>
                                        <span><?= $vd['avg_time'] ?>s</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div>
                            <h6 class="mt-4 border-bottom">Average time per visit</h6>
                            <div class="pl-4 pr-4 pt-2 pb-2">
                                <div class="stat-row p-1 border-bottom border-success">
                                    <span>Page</span>
                                    <span>Time</span>
                                </div>
                                <?php
                                    $vdat = $trac->get_avg_time_per_visit();
                                    foreach ($vdat as $k => $vd):
                                ?>
                                    <div class='stat-row p-1 border-bottom border-light'>
                                        <span><?= $k+1 ?></span>
                                        <span><?=$vd['avg_time']?>s</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class='stat-table'>
                    <h5 class="border-bottom">Stats for lifetime</h5>
                    <div class="pl-4 pr-4 pt-2 pb-2">
                        <div>
                            <?php $visit_day = $trac->get_unique_visits('lifetime'); ?>
                            <div class='stat-row'>
                                <span>Total Unique visits</span>
                                <span><?= $visit_day['visit_count'] ?></span>
                            </div>
                        </div>

                        <div>
                            <?php $vdata = $trac->get_page_visits('lifetime'); ?>
                            <div class='stat-row'>
                                <span>Total Visits</span>
                                <span><?= $vdata['visit_count'] ?></span>
                            </div>
                        </div>

                        <div>
                            <h6 class="border-bottom mt-4">Average time per page</h6>
                            <div class="pl-4 pr-4 pt-2 pb-2">
                                <div class="stat-row p-1 border-bottom border-success">
                                    <span>Page</span>
                                    <span>Time</span>
                                </div>
                                <?php
                                    $vdat = $trac->get_avg_time_per_page('lifetime');
                                    foreach($vdat as $vd):
                                ?>
                                    <div class='stat-row p-1 border-bottom border-light'>
                                        <span><?= $vd['page'] ?></span>
                                        <span><?= $vd['avg_time'] ?>s</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div>
                            <h6 class="mt-4 border-bottom">Average time per visit</h6>
                            <div class="pl-4 pr-4 pt-2 pb-2">
                                <div class="stat-row p-1 border-bottom border-success">
                                    <span>Page</span>
                                    <span>Time</span>
                                </div>
                                <?php
                                    $vdat = $trac->get_avg_time_per_visit('lifetime');
                                    foreach ($vdat as $k => $vd):
                                ?>
                                    <div class='stat-row p-1 border-bottom border-light'>
                                        <span><?= $k+1 ?></span>
                                        <span><?=$vd['avg_time']?>s</span>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card my-2">
            <div class="card-header">Latest</div>
            <div class="card-body">
                <p class="card-text">Eos et ut voluptas ad. Vero quis nihil quis impedit omnis ut. Quod non nesciunt illum qui in quidem repellendus libero. Odio molestiae voluptate neque vero architecto esse sunt quae. Quod molestiae est ut tenetur autem esse voluptas itaque. Et similique sunt ipsa libero numquam blanditiis.</p>
            </div>
        </div>
    </div>
</div>

<?php include_once 'footer.php'; ?>
