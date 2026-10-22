<?php
Yii::import('zii.widgets.grid.CGridView');
/** Yii sorting/pagination with GET filters that also work after PJAX navigation. */
class ManagementGrid extends CGridView {
 public function registerClientScript(){/* Filtering is delegated once by admin-tables.js. */}
}
