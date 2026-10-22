(function(){'use strict';jalaliDatepicker.startWatch({time:true,hasSecond:false,zIndex:5000});
document.addEventListener('yii-store:pjax-loaded',function(){jalaliDatepicker.hide();});
document.addEventListener('input',function(e){if(e.target.matches('[data-jalali-date]'))e.target.setCustomValidity('');});
})();
