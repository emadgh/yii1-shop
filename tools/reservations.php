<?php
require __DIR__.'/bootstrap.php';
$watch=in_array('--watch',$argv,true);
do { try { $count=OrderService::expire();Notifications::warnReservations(); if($count) echo date('c')." Released $count expired reservations\n"; } catch(Exception $e) { fwrite(STDERR,$e->getMessage()."\n"); if(!$watch)exit(1); } if($watch)sleep(10); } while($watch);
