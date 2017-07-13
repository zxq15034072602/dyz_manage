var region = new Object();
region.loadRegions = function(parent,target,type,selected)
{
  $.ajax({
	  url:'http://app.duyiwang.cn/index.php?action=region&dir=admin&do=ajax_region',
      type:'post',
      dataType:'json',
      data:{type:type,id:parent},
      success:function(res){
    	  if(res.code=="200"&&res.cities){
    		  var select=$('#'+target);
    		  select.empty();
    		  var option=$("<option value='0'>请选择</option>");
    		  select.append(option);
    		  $.each(res.cities,function(i,v){
    			  if(selected==v.cityid){
    				  var option=$('<option value='+v.cityid+' selected>'+v.city+'</option>');
    			  }else{
    				  var option=$('<option value='+v.cityid+'>'+v.city+'</option>');
    			  }
    			  select.append(option);
    		  });
    	  }else if(res.code=="200"&&res.areas){
    		  var select=$('#'+target);
    		  select.empty();
    		  var option=$("<option value='0'>请选择</option>");
    		  select.append(option);
    		  $.each(res.areas,function(i,v){
    			  if(selected == v.areaid){
    				  var option=$('<option value='+v.areaid+' selected>'+v.area+'</option>');
    			  }else{
    				  var option=$('<option value='+v.areaid+'>'+v.area+'</option>');
    			  }
    			  
    			  select.append(option);
    		  });
    	  }
    	  
      }
  });
}
/* *
 * 载入指定的省份下所有的城市
 *
 * @province    integer 省份的编号
 * @selName     string  列表框的名称
 */
region.loadCities = function(province, selName,selected)
{
  var objName = (typeof selName == "undefined") ? "selCities" : selName;
 
  region.loadRegions(province,objName,2,selected);
}
/* *
 * 载入指定的城市下的区 / 县
 *
 * @city    integer     城市的编号
 * @selName string      列表框的名称
 */
region.loadarea = function(city, selName,selected)
{
  var objName = (typeof selName == "undefined") ? "selarea" : selName;

  region.loadRegions(city,objName,3,selected);
}
/* *
 * 处理下拉列表改变的函数
 *
 * @obj     object  下拉列表
 * @type    2市级 3地区
 * @selName string  目标列表框的名称
 */
region.changed = function(obj,selName,type)
{
  var parent = obj.options[obj.selectedIndex].value;

  region.loadRegions(parent,selName,type,0);
}


