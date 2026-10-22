<?php
class VisitTracker extends CWidget {
 public function run(){if(!VisitService::enabled() || AccessService::isAdmin() || ThemeService::preview())return;
  $matomo=ShopSettings::get('matomo_url','');$site=ShopSettings::get('matomo_site_id','');
  echo '<div hidden id="visit-config" data-endpoint="'.CHtml::encode(Yii::app()->createUrl('visit/track')).'" data-matomo="'.CHtml::encode($matomo).'" data-site="'.CHtml::encode($site).'"></div>';
  Yii::app()->clientScript->registerScriptFile(Yii::app()->baseUrl.'/assets/js/analytics.js',CClientScript::POS_END);
 }
}
