<?php

/**
 * 本页仅供测试
 */


class IndexAction extends CommonAction {

    public function index() {
          if($_GET['fp']!=1 && $_COOKIE['tennis-club'] != null)
            $this->redirect('Index:index_'.$_COOKIE['tennis-club']);
        
        $s = new SaeStorage(); 
        $waiting = "";
        $city_id = $_GET['cityId'];
        // 设置数组
        $currentProvince = array();
        $provinceList = array();
        $cityList = array();
        $clubList = array();
        // 定义数据库查询变量
        $PROVINCE = M('province');
        $CITY = M('city');
        $CLUB = M('club');
        // 查询省市列表
        $provinceList = $PROVINCE->getField("province_id, province_id, province_name");
        
        if($city_id == null){
            // 获取打开页面者所在城市
            $city = $this->getIPCity();
            // 查询数据库获取城市列表
            $province = $CITY->where("city_name like '%".$city."%'")->join("INNER JOIN province ON province.province_id=city.province_id")->getField('city_id, city_id, city_name, city.province_id, province_name');
            $currentProvince = current($province);
            $cityList = $CITY->where('province_id='.$currentProvince['province_id'])->getField('city_id, city_id, city_name');
            
            // 查询俱乐部列表
            $clubList = $CLUB->where("city_id=".$currentProvince['city_id'])->getField("id, id, name");
            if($clubList != null){
                foreach($clubList as $key=>$value){
                    $clubList[$key]['portrait'] = $s->getUrl("imgdomain", 'club_portrait/club'.$value['id'].'.jpg');
                }
                
            }
            else
                $waiting = "等待当地俱乐部入驻。。。";
        }
        else{
            $province = $CITY->where("city_id=".$city_id)->join("INNER JOIN province ON province.province_id=city.province_id")->getField('city_id, city_id, city_name, city.province_id, province_name');
            $currentProvince = current($province);
            $cityList = $CITY->where('province_id='.$currentProvince['province_id'])->getField('city_id, city_id, city_name');
            
            // 查询俱乐部列表
            $clubList = $CLUB->where("city_id=".$currentProvince['city_id'])->getField("id, id, name");
            if($clubList != null){
                foreach($clubList as $key=>$value){
                    $clubList[$key]['portrait'] = $s->getUrl("imgdomain", 'club_portrait/club'.$value['id'].'.jpg');
                }
                
            }
            else
                $waiting = "等待当地俱乐部入驻。。。";
            
        }
        
        $this->assign("currentProvinceId", $currentProvince['province_id']);
        $this->assign("currentProvinceName", $currentProvince['province_name']);
        $this->assign("currentCityId", $currentProvince['city_id']);
        $this->assign("currentCityName", $currentProvince['city_name']);
        $this->assign("provinceList", $provinceList);
        $this->assign("cityList", $cityList);
        $this->assign("clubList", $clubList);
        $this->assign("waiting", $waiting);
        
        $this->display();
       
    }
    
    // 检索城市列表
    public function queryCity(){
        $provinceId = $_GET['id'];
        $CITY = M('city');
        $cityList = $CITY->where('province_id='.$provinceId)->getField('city_id, city_id, city_name');
        
        $html = "";
        // 生成城市下拉列表
        if($cityList != null){
            foreach($cityList as $key=>$value){
                $city_id = $value['city_id'];
                $city_name = $value['city_name'];
                $html  = $html."<option value='".$city_id."'>".$city_name."</option>";
            }
            
            $this->ajaxReturn($html,"城市列表检索成功！",0);
        }else{
            $this->ajaxReturn(-1,"城市列表检索失败！",0);
        }
        
    }
    
    
    public function index_1() {
        cookie("tennis-club", 1, time()+3600000);
        $club_id = 1;
        $activity = M('activity');
        $study_text = M('study_text');
        $study_video = M('study_video');
        $list1 = $activity->where('club_id='.$club_id)->order('update_date desc')->getField('id, title, introduction, update_date', 1); 
        $list2 = $study_text->order('update_date desc')->getField('id, title, content, update_date', 1); 
        $list3 = $study_video->order('update_date desc')->getField('id, title, update_date', 1); 
        
        $item1 = current($list1);
        $item2 = current($list2);
        $item3 = current($list3);
        
        if($item1 != null)
       		$item1['update_date'] = "(".date("Y-m-d", strtotime($item1['update_date'])).")";
        
        $type = 1;
        if(strtotime($item2['update_date']) < strtotime($item3['update_date'])){
            $type = 2;
            $item2 = $item3;
        }
        
        $item2['type'] = $type;
        
        $content = substr($item2['content'], 0, 270);
        $index = strrpos($content, '。');
        $item2['content'] = substr($content, 0, $index+3);
        $this->assign('item1', $item1);
        $this->assign('item2', $item2);
        $this->assign('displayInstruction', $_GET['displayInstruction']);
        $this->assign('clubName', $this->getClubName());
        $this->display();
    }

    public function index_2() {
        cookie("tennis-club", 2, time()+3600000);
        $club_id = 2;
        $activity = M('activity');
        $study_text = M('study_text');
        $study_video = M('study_video');
        $list1 = $activity->where('club_id='.$club_id)->order('update_date desc')->getField('id, title, introduction, update_date', 1); 
        $list2 = $study_text->order('update_date desc')->getField('id, title, content, update_date', 1); 
        $list3 = $study_video->order('update_date desc')->getField('id, title, update_date', 1); 
        
        $item1 = current($list1);
        $item2 = current($list2);
        $item3 = current($list3);
        
        if($item1 != null)
       		$item1['update_date'] = "(".date("Y-m-d", strtotime($item1['update_date'])).")";
        
        $type = 1;
        if(strtotime($item2['update_date']) < strtotime($item3['update_date'])){
            $type = 2;
            $item2 = $item3;
        }
        
        $item2['type'] = $type;
        
        $content = substr($item2['content'], 0, 270);
        $index = strrpos($content, '。');
        $item2['content'] = substr($content, 0, $index+3);
        $this->assign('item1', $item1);
        $this->assign('item2', $item2);
        $this->assign('displayInstruction', $_GET['displayInstruction']);
        $this->assign('clubName', $this->getClubName());
        $this->display();
    }
  
    public function index_3() {
        cookie("tennis-club", 3, time()+3600000);
        $club_id = 3;
        $activity = M('activity');
        $study_text = M('study_text');
        $study_video = M('study_video');
        $list1 = $activity->where('club_id='.$club_id)->order('update_date desc')->getField('id, title, introduction, update_date', 1); 
        $list2 = $study_text->order('update_date desc')->getField('id, title, content, update_date', 1); 
        $list3 = $study_video->order('update_date desc')->getField('id, title, update_date', 1); 
        
        $item1 = current($list1);
        $item2 = current($list2);
        $item3 = current($list3);
        
        if($item1 != null)
       		$item1['update_date'] = "(".date("Y-m-d", strtotime($item1['update_date'])).")";
        
        $type = 1;
        if(strtotime($item2['update_date']) < strtotime($item3['update_date'])){
            $type = 2;
            $item2 = $item3;
        }
        
        $item2['type'] = $type;
        
        $content = substr($item2['content'], 0, 270);
        $index = strrpos($content, '。');
        $item2['content'] = substr($content, 0, $index+3);
        $this->assign('item1', $item1);
        $this->assign('item2', $item2);
        $this->assign('displayInstruction', $_GET['displayInstruction']);
        $this->assign('clubName', $this->getClubName());
        $this->display();
    }
    
    public function index_4() {
        cookie("tennis-club", 4, time()+3600000);
        $club_id = 4;
        $activity = M('activity');
        $study_text = M('study_text');
        $study_video = M('study_video');
        $list1 = $activity->where('club_id='.$club_id)->order('update_date desc')->getField('id, title, introduction, update_date', 1); 
        $list2 = $study_text->order('update_date desc')->getField('id, title, content, update_date', 1); 
        $list3 = $study_video->order('update_date desc')->getField('id, title, update_date', 1); 
        
        $item1 = current($list1);
        $item2 = current($list2);
        $item3 = current($list3);
        
        if($item1 != null)
       		$item1['update_date'] = "(".date("Y-m-d", strtotime($item1['update_date'])).")";
        
        $type = 1;
        if(strtotime($item2['update_date']) < strtotime($item3['update_date'])){
            $type = 2;
            $item2 = $item3;
        }
        
        $item2['type'] = $type;
        
        $content = substr($item2['content'], 0, 270);
        $index = strrpos($content, '。');
        $item2['content'] = substr($content, 0, $index+3);
        $this->assign('item1', $item1);
        $this->assign('item2', $item2);
        $this->assign('displayInstruction', $_GET['displayInstruction']);
        $this->assign('clubName', $this->getClubName());
        $this->display();
    }
    
    public function index_5() {
        cookie("tennis-club", 5, time()+3600000);
        $club_id = 5;
        $activity = M('activity');
        $study_text = M('study_text');
        $study_video = M('study_video');
        $list1 = $activity->where('club_id='.$club_id)->order('update_date desc')->getField('id, title, introduction, update_date', 1); 
        $list2 = $study_text->order('update_date desc')->getField('id, title, content, update_date', 1); 
        $list3 = $study_video->order('update_date desc')->getField('id, title, update_date', 1); 
        
        $item1 = current($list1);
        $item2 = current($list2);
        $item3 = current($list3);
        
        if($item1 != null)
       		$item1['update_date'] = "(".date("Y-m-d", strtotime($item1['update_date'])).")";
        
        $type = 1;
        if(strtotime($item2['update_date']) < strtotime($item3['update_date'])){
            $type = 2;
            $item2 = $item3;
        }
        
        $item2['type'] = $type;
        
        $content = substr($item2['content'], 0, 270);
        $index = strrpos($content, '。');
        $item2['content'] = substr($content, 0, $index+3);
        $this->assign('item1', $item1);
        $this->assign('item2', $item2);
        $this->assign('displayInstruction', $_GET['displayInstruction']);
        $this->assign('clubName', $this->getClubName());
        $this->display();
    }
    
    public function index_6() {
        cookie("tennis-club", 6, time()+3600000);
        $club_id = 6;
        $activity = M('activity');
        $study_text = M('study_text');
        $study_video = M('study_video');
        $list1 = $activity->where('club_id='.$club_id)->order('update_date desc')->getField('id, title, introduction, update_date', 1); 
        $list2 = $study_text->order('update_date desc')->getField('id, title, content, update_date', 1); 
        $list3 = $study_video->order('update_date desc')->getField('id, title, update_date', 1); 
        
        $item1 = current($list1);
        $item2 = current($list2);
        $item3 = current($list3);
        
        if($item1 != null)
       		$item1['update_date'] = "(".date("Y-m-d", strtotime($item1['update_date'])).")";
        
        $type = 1;
        if(strtotime($item2['update_date']) < strtotime($item3['update_date'])){
            $type = 2;
            $item2 = $item3;
        }
        
        $item2['type'] = $type;
        
        $content = substr($item2['content'], 0, 270);
        $index = strrpos($content, '。');
        $item2['content'] = substr($content, 0, $index+3);
        $this->assign('item1', $item1);
        $this->assign('item2', $item2);
        $this->assign('displayInstruction', $_GET['displayInstruction']);
        $this->assign('clubName', $this->getClubName());
        $this->display();
    }
    
    public function index_7() {
        cookie("tennis-club", 7, time()+3600000);
        $club_id = 7;
        $activity = M('activity');
        $study_text = M('study_text');
        $study_video = M('study_video');
        $list1 = $activity->where('club_id='.$club_id)->order('update_date desc')->getField('id, title, introduction, update_date', 1); 
        $list2 = $study_text->order('update_date desc')->getField('id, title, content, update_date', 1); 
        $list3 = $study_video->order('update_date desc')->getField('id, title, update_date', 1); 
        
        $item1 = current($list1);
        $item2 = current($list2);
        $item3 = current($list3);
        
        if($item1 != null)
       		$item1['update_date'] = "(".date("Y-m-d", strtotime($item1['update_date'])).")";
        
        $type = 1;
        if(strtotime($item2['update_date']) < strtotime($item3['update_date'])){
            $type = 2;
            $item2 = $item3;
        }
        
        $item2['type'] = $type;
        
        $content = substr($item2['content'], 0, 270);
        $index = strrpos($content, '。');
        $item2['content'] = substr($content, 0, $index+3);
        $this->assign('item1', $item1);
        $this->assign('item2', $item2);
        $this->assign('displayInstruction', $_GET['displayInstruction']);
        $this->assign('clubName', $this->getClubName());
        $this->display();
    }
    
    public function index_8() {
        cookie("tennis-club", 8, time()+3600000);
        $club_id = 8;
        $activity = M('activity');
        $study_text = M('study_text');
        $study_video = M('study_video');
        $list1 = $activity->where('club_id='.$club_id)->order('update_date desc')->getField('id, title, introduction, update_date', 1); 
        $list2 = $study_text->order('update_date desc')->getField('id, title, content, update_date', 1); 
        $list3 = $study_video->order('update_date desc')->getField('id, title, update_date', 1); 
        
        $item1 = current($list1);
        $item2 = current($list2);
        $item3 = current($list3);
        
        if($item1 != null)
       		$item1['update_date'] = "(".date("Y-m-d", strtotime($item1['update_date'])).")";
        
        $type = 1;
        if(strtotime($item2['update_date']) < strtotime($item3['update_date'])){
            $type = 2;
            $item2 = $item3;
        }
        
        $item2['type'] = $type;
        
        $content = substr($item2['content'], 0, 270);
        $index = strrpos($content, '。');
        $item2['content'] = substr($content, 0, $index+3);
        $this->assign('item1', $item1);
        $this->assign('item2', $item2);
        $this->assign('displayInstruction', $_GET['displayInstruction']);
        $this->assign('clubName', $this->getClubName());
        $this->display();
    }
}

?>
