<?php
/*
    The login/account part of the top menu bar: either "Log in / Join" for a
    visitor, or the account links ("Log out", "Edit details", their name) for
    a logged-in user.

    Included from PAGE::user_bar(), in whose scope this runs - all the
    variables below are already built there, for whichever branch applies.
*/
?>
<?php if ($THEUSER->isloggedin()): ?>
                            <ul id="user" class="!m-0 !box-border flex list-none items-center gap-1 !p-0">
                                <li><a href="<?php echo $LOGOUTURL->generate(); ?>" title="<?php echo $logouttitle; ?>" <?php echo $logoutclass; ?>><?php echo $logouttext; ?></a></li>
                                <li><a href="<?php echo $EDITURL->generate(); ?>" title="<?php echo $edittitle; ?>" <?php echo $editclass; ?>><?php echo $edittext; ?></a></li>
                                <li><span class="name text-sm text-slate-300"><?php echo htmlentities($username); ?></span></li>
                                <!--            <li><a href="<?php echo $GETINVURL->generate(); ?>" title="<?php echo $getinvolvedtitle; ?>"<?php echo $getinvolvedclass; ?>><?php echo $getinvolvedtext; ?></a></li> -->
                            </ul>
<?php else: ?>
                            <ul id="user" class="!m-0 !box-border flex list-none items-center gap-1 !p-0">
                                <li><a href="<?php echo $LOGINURL->generate(); ?>" title="<?php echo $logintitle; ?>" <?php echo $loginclass; ?>><?php echo $logintext; ?></a></li>
                                <li><a href="<?php echo $JOINURL->generate(); ?>" title="<?php echo $jointitle; ?>" <?php echo $joinclass; ?>><?php echo $jointext; ?></a></li>
                                <!--            <li><a href="<?php echo $GETINVURL->generate(); ?>" title="<?php echo $getinvolvedtitle; ?>"<?php echo $getinvolvedclass; ?>><?php echo $getinvolvedtext; ?></a></li> -->
                            </ul>
<?php endif; ?>
