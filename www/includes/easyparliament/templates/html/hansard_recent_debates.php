<ul>
    <?php

    $count = 0;

    foreach ($data as $date => $arr) {
        print '<li><strong>' . $date . '</strong> <ul>';
        foreach ($arr as $debate) {
            print '<li><a href="' . $debate['url'] . '">' . $debate['bill'] . '</a>, ' . $debate['sitting'];
        }
        print '</ul>';
    }
    ?>
</ul>
