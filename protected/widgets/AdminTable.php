<?php
class AdminTable extends CWidget {
 public $type,$scope=null,$pageSize=20,$only=null,$context=array();
 public function run(){
  $scope=$this->scope?:$this->type;$definition=AdminTables::definition($this->type,$this->context);$fields=$definition['fields'];if($this->only)$fields=array_intersect_key($fields,array_flip($this->only));
  foreach(array($scope.'_sort',$scope.'_page') as $parameter)if(isset($_GET[$parameter])&&!is_scalar($_GET[$parameter]))throw new CHttpException(400,'پارامتر جدول معتبر نیست.');
  $search=new AdminTableSearch($scope,$fields);$provider=$search->provider($definition,$this->pageSize);$columns=array();foreach($fields as $key=>$field){$column=array('name'=>$key,'header'=>$field['header'],'filter'=>$search->control($key));if(isset($field['value']))$column['value']=$field['value'];if(isset($field['type']))$column['type']=$field['type'];$columns[]=$column;}
  if(isset($definition['action']))$columns[]=array('header'=>'عملیات','type'=>'raw','filter'=>false,'value'=>$definition['action']);
  echo '<div id="'.CHtml::encode($scope).'-region" data-pjax-region="1" data-pjax-scope="'.CHtml::encode($scope).'">';
  echo CHtml::beginForm(Yii::app()->createUrl($this->controller->route),'get',array('class'=>'admin-grid-form','data-pjax-form'=>'1','data-grid-scope'=>$scope));
  $query=Yii::app()->request->getQueryString();parse_str($query,$params);unset($params[$scope.'_page']);if(isset($params['Grid'])&&is_array($params['Grid']))unset($params['Grid'][$scope]);
  $hidden=function($values,$prefix='')use(&$hidden){foreach($values as $key=>$value){$name=$prefix?$prefix.'['.$key.']':$key;if(is_array($value))$hidden($value,$name);elseif(is_scalar($value))echo CHtml::hiddenField($name,$value,array("id"=>false));}};$hidden($params);
  echo '<div class="grid-tools"><span>جستجوی هر ستون؛ کلیک روی عنوان برای مرتب‌سازی</span><div><button type="submit" class="btn btn-outline btn-sm">اعمال فیلتر</button> '.CHtml::link('پاک‌کردن فیلترها',Yii::app()->createUrl($this->controller->route,$this->resetParams($params,$scope)),array('class'=>'text-link','data-pjax'=>'1')).'</div></div><div class="table-wrap">';
  $this->widget('ManagementGrid',array('id'=>$scope.'-grid','dataProvider'=>$provider,'filter'=>$search,'ajaxUpdate'=>false,'cssFile'=>false,'itemsCssClass'=>'data-table','summaryText'=>'نمایش {start} تا {end} از {count}','emptyText'=>'نتیجه‌ای مطابق فیلترها پیدا نشد.','columns'=>$columns,'pager'=>array('header'=>'','htmlOptions'=>array('class'=>'pagination'))));
  echo '</div>'.CHtml::endForm();
  if(isset($definition['forms']))call_user_func($definition['forms'],$provider->getData());
  echo '</div>';
 }
 private function resetParams($params,$scope){unset($params[$scope.'_sort'],$params[$scope.'_page']);if($this->type==='orders')unset($params['status']);return $params;}
}
