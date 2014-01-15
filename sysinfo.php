<?php header( 'Content-Type: text/javascript', true ); include "sysinfolib.php" ?>
$('#loadinfo').html( '<center><?php

hostinfo2html();
cpu2html();
system2html();
networkconnections2html();

memory2html();
diskspace2html ("", "/tmp", "Temp (/tmp)", 10, 5, "MB");
diskspace2html ("", "/", "Compact Flash (SDA1)", 1, 0.5, "GB");
diskspace2html ("/dev/sdb", "/mnt/hd1", "Harddisk 1 (SDB1)", 2, 1, "GB");
diskspace2html ("/dev/sdc", "/mnt/hd2", "Harddisk 2 (SDC1)", 3, 1, "GB");

networkinfo2html();
processstatus2html();


?>
</center>' );
