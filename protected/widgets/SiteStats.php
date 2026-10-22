<?php
/** Reusable visit summary for dashboards and the dedicated analytics screen. */
class SiteStats extends CWidget {
 public $days=7;
 public function run(){$this->render('site-stats',array('stats'=>VisitService::summary($this->days)));}
}
