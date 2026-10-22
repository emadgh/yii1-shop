<?php
/** Whitelisted, parameterized column filters shared by Yii data providers. */
class AdminTableSearch extends CFormModel {
 public $scope,$fields,$values=array();
 public function __construct($scope,$fields){
  $this->scope=$scope;$this->fields=$fields;parent::__construct();
  $input=Yii::app()->request->getQuery('Grid',array());$input=is_array($input)&&isset($input[$scope])&&is_array($input[$scope])?$input[$scope]:array();
  foreach($fields as $key=>$field)$this->values[$key]=isset($input[$key])&&is_scalar($input[$key])?mb_substr(trim((string)$input[$key]),0,190):'';
  if($scope==='orders' && isset($fields['status']) && !array_key_exists('status',$input))$this->values['status']=AdminTables::orderStatus();
 }
 public function control($key){$f=$this->fields[$key];$name='Grid['.$this->scope.']['.$key.']';$options=array('aria-label'=>'فیلتر '.$f['header'],'id'=>$this->scope.'-filter-'.$key);
  if(($f['kind']??'')==='date')return JalaliDate::field($name,$this->values[$key],false,$options);
  if(isset($f['choices']))return CHtml::dropDownList($name,$this->values[$key],$f['choices'],array_merge($options,array('prompt'=>'همه')));
  $numeric=in_array($f['kind']??'text',array('number','money','mixed-money'),true);$options['placeholder']=$numeric?'>=100':'جستجو…';if($numeric)$options['dir']='ltr';if($numeric)$options['title']='عدد دقیق یا مقایسه با >، <، >=، <= و !='.(($f['kind']??'')==='money'?'؛ به '.Money::unit():'');return CHtml::textField($name,$this->values[$key],$options);
 }
 public function apply($criteria){foreach($this->fields as $key=>$f){$value=$this->values[$key];if($value==='')continue;$column=$f['sql']??'t.'.$key;$kind=$f['kind']??'text';
   if(isset($f['choices'])){if(!array_key_exists($value,$f['choices'])){$criteria->addCondition('1=0');continue;}$parameter=':grid_'.count($criteria->params);$criteria->addCondition($column.' = '.$parameter);$criteria->params[$parameter]=ctype_digit($value)?(int)$value:$value;continue;}
   if(in_array($kind,array('number','money','mixed-money'),true)){
    $value=strtr($value,array_combine(preg_split('//u','۰۱۲۳۴۵۶۷۸۹٠١٢٣٤٥٦٧٨٩',-1,PREG_SPLIT_NO_EMPTY),str_split('01234567890123456789')));
    if(!preg_match('/^(>=|<=|!=|>|<|=)?\s*(-?[0-9,٬ ]+)$/uD',$value,$m)){$criteria->addCondition('1=0');continue;}
    $operator=$m[1]?:'=';$negative=strpos($m[2],'-')===0;$digits=str_replace(array(',','٬',' '),'',ltrim($m[2],'-'));
    if(strlen($digits)>14){$criteria->addCondition('1=0');continue;}
    try{$amount=($kind==='money'?Money::parse($digits):(int)$digits)*($negative?-1:1);}catch(Exception $e){$criteria->addCondition('1=0');continue;}
    if($kind==='mixed-money')$column="(CASE WHEN t.kind='fixed' THEN t.amount*".Money::factor().' ELSE t.amount END)';
    $parameter=':grid_'.count($criteria->params);$criteria->addCondition($column.' '.$operator.' '.$parameter);$criteria->params[$parameter]=$amount;
   }elseif($kind==='date'){try{$day=JalaliDate::parse($value,false);$next=(new DateTimeImmutable($day))->modify('+1 day')->format('Y-m-d');$p=':grid_'.count($criteria->params);$criteria->addCondition($column.' >= '.$p.' AND '.$column.' < '.$p.'_end');$criteria->params[$p]=$day.' 00:00:00';$criteria->params[$p.'_end']=$next.' 00:00:00';}catch(Exception $e){$criteria->addCondition('1=0');}}else $criteria->addSearchCondition($column,$value);
  }return $criteria;}
 public function provider($definition,$pageSize=20){
  $criteria=$this->apply(new CDbCriteria);$attributes=array();foreach($this->fields as $key=>$field){$sql=$field['sql']??'t.'.$key;$attributes[$key]=array('asc'=>$sql.' ASC','desc'=>$sql.' DESC','label'=>$field['header']);}
  if(isset($definition['condition'])){$criteria->addCondition($definition['condition']);$criteria->params=array_merge($criteria->params,$definition['params']??array());}
  if(($definition['model']??'')==='Product'){$q=Yii::app()->request->getQuery('q','');if(is_string($q)&&trim($q)!=='')SearchService::products($criteria,$q);}
  $sort=array('sortVar'=>$this->scope.'_sort','attributes'=>$attributes,'defaultOrder'=>$definition['order']??'t.id DESC');$pagination=array('pageSize'=>$pageSize,'pageVar'=>$this->scope.'_page');
  if(isset($definition['model'])){if(!empty($definition['with']))$criteria->with=$definition['with'];return new CActiveDataProvider($definition['model'],array('id'=>$this->scope,'criteria'=>$criteria,'sort'=>$sort,'pagination'=>$pagination));}
  $sql='SELECT * FROM ('.$definition['query'].') t';if($criteria->condition)$sql.=' WHERE '.$criteria->condition;$params=array_merge($definition['params']??array(),$criteria->params);$count=Yii::app()->db->createCommand('SELECT COUNT(*) FROM ('.$sql.') counted')->bindValues($params)->queryScalar();
  return new CSqlDataProvider($sql,array('id'=>$this->scope,'keyField'=>$definition['keyField']??'id','params'=>$params,'totalItemCount'=>(int)$count,'sort'=>$sort,'pagination'=>$pagination));
 }
}
