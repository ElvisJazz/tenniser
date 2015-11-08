<?php

class MatchAction extends AdminAction {
    // 更新赛事的详细页
    public function serialMatch_update_detail(){      
       // 获取系列赛id
        $id = $_GET['id'];   
        $players = array();
        $scale_html = "";
        if($id==null || $id=='')
            $this->error('无有效参数！');
        $s = new SaeStorage();  
        
        // 获取基本情况
        $ACTIVITY = M('activity');
        $activity0 = $ACTIVITY->where('id='.$id)->getField('id, title, content, start_time, end_time, headcount, cover, scale_num, state, result, champion_id');  
        $activity = current($activity0);
        if($activity['cover']!=1 || $s->fileExists("imgdomain", 'post/'.$id.'.jpg')==FALSE)
                    $activity['cover'] = "__IMG__/activity.jpg";
                else
                    $activity['cover'] = $s->getUrl("imgdomain", 'post/'.$id.'.jpg');
        
        // 获取积分榜数据
        $ACTIVITY_USER = M('activity_user');
        $activity_user = $ACTIVITY_USER->join(' INNER JOIN serial_match_user ON serial_match_user.serial_match_id=activity_user.activity_id and serial_match_user.user_id=activity_user.user_id and activity_user.activity_id='.$id.' and serial_match_user.round=1 and type=0')
            ->join('INNER JOIN user ON user.id=activity_user.user_id')
            ->order('serial_match_user.group,score desc,win desc,activity_user.offset desc')->getField('truename,activity_user.round,win,lost,offset,activity_user.score,group,no');
        
        $score_list = null;
        if($activity_user != null){    
            $this->assign('exist1', 1);
            $index = -1;
            $subIndex = 0;
            $group = '';
            foreach($activity_user as $key=>$value) {
                if($group != $value['group']){
                    $index++;
                    $subIndex = 0;
                    $group = $value['group'];
                    $score_list[$index]['group'] = $group;
                }
                $score_list[$index]['player'][$subIndex]['index'] = $subIndex+1;
                $score_list[$index]['player'][$subIndex]['truename'] = $value['truename'];
                $score_list[$index]['player'][$subIndex]['win'] = $value['win'];
                $score_list[$index]['player'][$subIndex]['lost'] = $value['lost'];
                $score_list[$index]['player'][$subIndex]['offset'] = $value['offset'];
                $score_list[$index]['player'][$subIndex]['score'] = $value['score'];
                $score_list[$index]['player'][$subIndex]['round'] = $value['round'];
                $score_list[$index]['player'][$subIndex]['no'] = $group.$value['no'];
                $subIndex++;
            }
        }
        
         // 获取小组赛对阵
        $BASE_MATCH = M('base_match');
        $base_match = $BASE_MATCH->join(' INNER JOIN serial_base_match ON serial_base_match.serial_match_id='.$id.' and serial_base_match.base_match_id=base_match.id and serial_base_match.type=0')
            ->join(' INNER JOIN serial_match_user ON serial_match_user.serial_match_id='.$id.' and serial_base_match.player1_id = serial_match_user.user_id and serial_match_user.type=0')
            ->order('serial_match_user.group, state desc,start_date')->getField('base_match.id, group, state, score, offset, winnerScoreOffset, loserScoreOffset, start_date, end_date, player1_id, player2_id, winner_id, round1, round2');
        $SERIAL_MATCH_USER = M('serial_match_user');
        $user_no = $SERIAL_MATCH_USER->where('serial_match_id='.$id.' and round=1 and type=0')->join('INNER JOIN user ON user.id=serial_match_user.user_id')->getField('user_id, truename, no');
        
        $group_list=null;
        if($base_match != null){ 
            $this->assign('exist2', 1);
            $index = -1;
            $subIndex = 0;
            $group = '';
            foreach($base_match as $key=>$value){
                 if($group != $value['group']){
                     $index++;
                     $subIndex = 0;
                     $group = $value['group'];
                     $group_list[$index]['group'] = $group;
                 }
                if($value['state'] == 0)
                	$group_list[$index]['players'][$subIndex]['state'] = '未开始';
                else
                	$group_list[$index]['players'][$subIndex]['state'] = '已结束';
                $group_list[$index]['players'][$subIndex]['id'] = $value['id'];
                $group_list[$index]['players'][$subIndex]['start_date'] = $value['start_date'];
                $group_list[$index]['players'][$subIndex]['end_date'] = $value['end_date'];
                $group_list[$index]['players'][$subIndex]['player1'] = $user_no[$value['player1_id']]['truename'];
                $group_list[$index]['players'][$subIndex]['player2'] = $user_no[$value['player2_id']]['truename'];
                $group_list[$index]['players'][$subIndex]['player1_id'] = $value['player1_id'];
                $group_list[$index]['players'][$subIndex]['player2_id'] = $value['player2_id'];
                $group_list[$index]['players'][$subIndex]['winner_id'] = $value['winner_id'];
                $group_list[$index]['players'][$subIndex]['score'] = $value['score'];
                $group_list[$index]['players'][$subIndex]['offset'] = $value['offset'];
                $group_list[$index]['players'][$subIndex]['winnerScoreOffset'] = $value['winnerScoreOffset'];                
                $group_list[$index]['players'][$subIndex]['loserScoreOffset'] = $value['loserScoreOffset'];
                $group_list[$index]['players'][$subIndex]['round1'] = $value['round1'];
                $group_list[$index]['players'][$subIndex]['round2'] = $value['round2'];
                $group_list[$index]['players'][$subIndex]['no1'] = $user_no[$value['player1_id']]['no'];
                $group_list[$index]['players'][$subIndex]['no2'] = $user_no[$value['player2_id']]['no'];
                $subIndex++;
            }
        }
        
        // 获得淘汰赛对阵
       $SERIAL_MATCH_USER1 = M('serial_match_user');
       $user_name = $SERIAL_MATCH_USER1->where('serial_match_id='.$id.' and round=1 and type=1')->join('INNER JOIN user ON user.id=serial_match_user.user_id')->getField('user_id, truename, no');
       
         $base_match = $BASE_MATCH->join(' INNER JOIN serial_base_match ON serial_base_match.base_match_id=base_match.id and serial_base_match.type=1 and serial_base_match.serial_match_id='.$id)
            ->order('state desc,start_date')->getField('base_match.id, state, offset, winnerScoreOffset, loserScoreOffset, score, start_date, end_date, winner_id, player1_id, player2_id, round1, round2,group1,group2,no1,no2');
       	if($base_match != null){
         	foreach($base_match as $key=>$value){
             
                 // 人员信息设置
                if($value['player1_id'] > 0)
                    $player1 = $user_name[$value['player1_id']]['truename'];
                else if($value['player1_id'] == 0)
                    $player1 = "轮空";
                else
                    $player1 = "待出线";
                    
                if($value['player2_id'] > 0)
                    $player2 = $user_name[$value['player2_id']]['truename'];
                else if($value['player2_id'] == 0)
                    $player2 = "轮空";
                else
                    $player2 = "待出线";
                
                $group1 = $value['group1'];
                $group2 = $value['group2'];
                $no1 = $value['no1'];
                $no2 = $value['no2'];
                $round1 = $value['round1'];
                $round2 = $value['round2'];
                $index1 = '';
                $index2 = '';
                
                if($round1 == '1')
                    $index1 = $group1.$no1;
                else
                    $index1 = $group1.$round1.$no1;
                $players[$index1] = $player1;
                    
               
                if($round2 == '1')
                    $index2 = $group2.$no2;
                else
                    $index2 = $group2.$round2.$no2;
                $players[$index2] = $player2;
                  
                 // 比赛信息设置
                $players['M'.$group1.$round1.($no2/2)] = $value['score'];
                 
                // 设置编辑按键上的事件
                 $players_vs_item['M'.$group1.$round1.($no2/2)]['group'] = $value['group1'];
                 $players_vs_item['M'.$group1.$round1.($no2/2)]['stateValue'] = $value['state'];
                 if($value['state'] == 0)
                     $players_vs_item['M'.$group1.$round1.($no2/2)]['state'] = '未开始';
                 else
                     $players_vs_item['M'.$group1.$round1.($no2/2)]['state'] = '已结束';
                 $players_vs_item['M'.$group1.$round1.($no2/2)]['id'] = $value['id'];
                 $players_vs_item['M'.$group1.$round1.($no2/2)]['start_date'] = $value['start_date'];
                 $players_vs_item['M'.$group1.$round1.($no2/2)]['end_date'] = $value['end_date'];
                 $players_vs_item['M'.$group1.$round1.($no2/2)]['player1'] = $user_name[$value['player1_id']]['truename'];
                 $players_vs_item['M'.$group1.$round1.($no2/2)]['player2'] = $user_name[$value['player2_id']]['truename'];
                 $players_vs_item['M'.$group1.$round1.($no2/2)]['player1_id'] = $value['player1_id'];
                 $players_vs_item['M'.$group1.$round1.($no2/2)]['player2_id'] = $value['player2_id'];
                 $players_vs_item['M'.$group1.$round1.($no2/2)]['winner_id'] = $value['winner_id'];
                 $players_vs_item['M'.$group1.$round1.($no2/2)]['score'] = $value['score'];
                 $players_vs_item['M'.$group1.$round1.($no2/2)]['offset'] = $value['offset'];
                 $players_vs_item['M'.$group1.$round1.($no2/2)]['winnerScoreOffset'] = $value['winnerScoreOffset'];
                 $players_vs_item['M'.$group1.$round1.($no2/2)]['loserScoreOffset'] = $value['loserScoreOffset'];
                 $players_vs_item['M'.$group1.$round1.($no2/2)]['round1'] = $value['round1'];
                 $players_vs_item['M'.$group1.$round1.($no2/2)]['round2'] = $value['round2'];
                 $players_vs_item['M'.$group1.$round1.($no2/2)]['no1'] = $user_no[$value['player1_id']]['no'];
                 $players_vs_item['M'.$group1.$round1.($no2/2)]['no2'] = $user_no[$value['player2_id']]['no'];
             }  
        }
        // 设置冠军
        $players['W'] = $user_name[$activity['champion_id']]['truename'];
        
        $elimination_list = null;
        $index = -1;
        $subIndex = 0;
        $round = 0;
        $title = '';
        // 设定1/8，1/4，1/2，决赛列表
        $base_match = $BASE_MATCH->join(' INNER JOIN serial_base_match ON serial_base_match.base_match_id=base_match.id and serial_base_match.group1="S" and serial_base_match.type=1 and serial_base_match.serial_match_id='.$id)
            ->order('group1, no1, start_date')->getField('base_match.id, state, score, start_date, end_date, player1_id, player2_id, round1, round2,group1,group2,no1,no2');
        if($base_match != null){
            $this->assign('exist3', 1);
            
        	// 判断淘汰赛显示阵型
           switch($activity['scale_num']) {
               case 8:   $scale_html="Match:scale8";break;
               case 16:  $scale_html="Match:scale16";break;
               case 32:  $scale_html="Match:scale32";break;
               case 64:  $scale_html="Match:scale64";break;
               case 128: $scale_html="Match:scale128";break;
               case 256: $scale_html="Match:scale256";break;
               default: break;
           }
           
            foreach($base_match as $key=>$value){            
                
                if($round != $value['round1']){
                    $index++;
                    $round = $value['round1'];
                    $subIndex = 0;
                }
                if($value['state'] == 0)
                	$elimination_list[$index]['players'][$subIndex]['state'] = '未开始';
                else
                	$elimination_list[$index]['players'][$subIndex]['state'] = '已结束';
                
                if($value['score'] == '')
                    $value['score'] = '0：0';
                
                $elimination_list[$index]['players'][$subIndex]['start_date'] = $value['start_date'];
                $elimination_list[$index]['players'][$subIndex]['end_date'] = $value['end_date'];
                $elimination_list[$index]['players'][$subIndex]['vs'] = $user_name[$value['player1_id']]['truename'].'【'.$value['score'].'】'.$user_name[$value['player2_id']]['truename'];
                $elimination_list[$index]['players'][$subIndex]['round'] = '['.$value['round1'].','.$value['round2'].']';
                $elimination_list[$index]['players'][$subIndex]['no1'] = $value['no1'];
                $elimination_list[$index]['players'][$subIndex]['no2'] = $value['no2'];
                $subIndex++;
                
                if($index >= 0){
                    switch($subIndex){
                        case 8: $elimination_list[$index]['title'] = '1/8决赛'; break;
                        case 4:  $elimination_list[$index]['title'] = '1/4决赛'; break;  
                        case 2:  $elimination_list[$index]['title'] = '半决赛'; break;  
                        case 1:  $elimination_list[$index]['title'] = '决赛'; break;  
                        default: break;                    
                    }
                   
                }
            }
            $elimination_list[$index]['title'] = '决赛';
        }
        
        // 获取参赛人员
        $USER = M('user');
        $userList = $USER->join(' INNER JOIN activity_user ON activity_user.user_id = user.id and activity_user.activity_id='.$id.' and activity_user.sign=1')->getField('user.id,truename, telephone, ntrp, portrait');
        if($userList != null){    
            $this->assign('exist4', 1);
            foreach($userList as $key=>$value){
                $userList[$key]['ntrp'] = number_format($value['ntrp'], 1);
                 // 设置封面
                if($value['portrait'] == 1)
                    $userList[$key]['portrait'] = $s->getUrl("imgdomain", 'portrait/'.$value['id'].'.jpg');
                else
                    $userList[$key]['portrait'] = '__IMG__/portrait.jpg';     
                
             }
        }
        $this->assign('activity', $activity);
        $this->assign('score_list', $score_list);
        $this->assign('group_list', $group_list);
        $this->assign('players_vs_item', $players_vs_item);
        $this->assign('elimination_list', $elimination_list);
        $this->assign('players_list', $userList);
        $this->assign('id', $id);
        
        $this->assign('scale_html', $scale_html);
        $players['enable'] = 1;
        $this->assign('players', $players);
        
        $this->display();
    }
    
    
     public function serialMatch_release_detail(){    
         // 获取系列赛id
        $id0 = $_GET['id'];  
        $type = $_POST['matchType'];
        $groupNum = $_POST['groupNum'];
        
        if($id0==null || $id0=='')
            $this->error('无有效参数！');
        $s = new SaeStorage();  
         
         if($type==0 && ($groupNum == '' || $groupNum == 0))
             $this->error('小组赛分组数不可为空或为0！');
         
         // 检查此类型赛事是否已经安排过了
         $SERIAL_MATCH_USER = M('serial_match_user');
         $id = $SERIAL_MATCH_USER->where("serial_match_id=".$_GET['id'].' and type='.$type)->field('id')->limit(1)->select();
         if($id != null && $id != ''){
             if($type == 0)
             	$this->error('同一赛事最多只能安排小组赛和淘汰赛各一次，该赛事已经安排过小组赛！如要重新安排，请在赛事更新页中进行相关操作！');
             else if($type == 1)
             	$this->error('同一赛事最多只能安排小组赛和淘汰赛各一次，该赛事已经安排过淘汰赛！如要重新安排，请在赛事更新页中进行相关操作！');
         }
         
        
        // 获取基本情况
        $ACTIVITY = M('activity');
        $activity0 = $ACTIVITY->where('id='.$id0)->getField('id, title, content, start_time, end_time, headcount, cover, scale_num, state, result, champion_id');  
        $activity = current($activity0);
        if($activity['cover']!=1 || $s->fileExists("imgdomain", 'post/'.$id0.'.jpg')==FALSE)
            $activity['cover'] = "__IMG__/activity.jpg";
         else
             $activity['cover'] = $s->getUrl("imgdomain", 'post/'.$id0.'.jpg');
         
         // 结束报名,正在安排赛事
         $ACTIVITY->where('id='.$id0)->setField('state', 1);
         
         // 赛事安排类型
         $matchArrangeType = 0; // 0表示小组赛，1表示淘汰赛，2表示超出系统安排范围
         
         // 参赛总人数
         $ACTIVITY = M('activity');
         $headcount = $ACTIVITY->where('id='.$_GET['id'])->GetField('headcount');
         // 如果是淘汰赛，计算系统预设的分组数、每组人数和轮空数
         $groupNum = 0;
         $eachGroupAmount = 0;
         $groupVoidArray = array(); // 轮空数组
         $groupArray = array("1"=>"A","2"=>"B","3"=>"C","4"=>"D","5"=>"E","6"=>"F","7"=>"G","8"=>"H");
         // 淘汰赛
         if($type == 1){
             $matchArrangeType = 1;
             if($headcount <= 8){
                 $groupNum = 2;
                 $diff = 8 - $headcount; // 轮空数
                 $eachGroupAmount = 4;
             }
             else if($headcount <= 16){
                 $groupNum = 2;
                 $diff = 16 - $headcount; // 轮空数
                 $eachGroupAmount = 8;
             }
             else if($headcount <= 32){
                 $groupNum = 4;
                 $diff = 32 - $headcount; // 轮空数
                 $eachGroupAmount = 8;
             }
             else if($headcount <= 64){
                 $groupNum = 4;
                 $diff = 64 - $headcount; // 轮空数
                 $eachGroupAmount = 16;
             }
             else if($headcount <= 128){
                 $groupNum = 8;
                 $diff = 128 - $headcount; // 轮空数
                 $eachGroupAmount = 16;
             }
             else if($headcount <= 256){
                 $groupNum = 8;
                 $diff = 256 - $headcount; // 轮空数
                 $eachGroupAmount = 32;
             }
             else
                 $matchArrangeType = 2;
                
             // 计算每组轮空数
             if($matchArrangeType != 2){
             	 $j = 1;
                 $round = 1;
                 for($i=1; $i<=$diff; $i++){
                     $groupVoidArray[$j][$round*2] = 1;  
                     $j++;
                     if($j > $groupNum){
                         $round++;
                         $j = 1;
                     }
                 } 
                 
                
             }
         }
          // 小组赛
         else{
             $matchArrangeType = 0;
             $groupNum = $_POST['groupNum'];
             $eachGroupAmount = floor($headcount / $groupNum);
             $offset = $groupNum- $headcount % $groupNum;
             if($offset > 0)
                 $eachGroupAmount++;
             // 计算每组轮空情况
             $j = $groupNum;
             for($i=1; $i<=$offset; $i++){
                 $groupVoidArray[$j][$eachGroupAmount] = 1;  
                 $j--;
             }
         }
         
         // 获取参赛选手
         $playerList = null;
         $ACTIVITY_USER = M('activity_user');
         $player = $ACTIVITY_USER->where('activity_id='.$_GET['id'])->join('INNER JOIN user ON user.id=activity_user.user_id')->getField('user.id, user_id, truename');
         foreach($player as $key=>$value){
             $firstChar = getFirstChar($value['truename']);
             $user_name = $value['truename'];
             $user_id = $value['user_id'];
         	 $playerList[$firstChar][$user_id]['id'] = $user_id;  
         	 $playerList[$firstChar][$user_id]['name'] = $user_name;
             echo $firstChar;
             
         }
         // 字母列表
         $letterList = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
         
         // 返回参数
         $this->assign("id", $_GET['id']);
         $this->assign("matchArrangeType", $matchArrangeType);
         $this->assign("groupVoidArray", $groupVoidArray);
         $this->assign("groupNum", $groupNum);
         $this->assign("eachGroupAmount", $eachGroupAmount);
         $this->assign("groupArray", $groupArray);
         $this->assign("activity", $activity);
         $this->assign("playerList", $playerList);
         $this->assign("letterList", $letterList);
         $this->assign("hasSetPlayer", 0);
         $this->assign("groupNum", $groupNum);
         $this->display();
     }
    
     // 赛事安排预提交函数（可写入数据库的预览） 
    public function serialMatch_release_presubmit(){ 
        // 检查提交的比赛是否符合提交条件
         if($_POST['ensureArrange'] == 1) {
            for($i=1; $i<=$groupNum; $i++){ // 循环组
                for($j=1; $j<$eachGroupAmount;){ // 组内循环成员
                    if($_POST[$groupArray[$i].$j] == null || $_POST[$groupArray[$i].$j] == '')
                        $this->error('有部分球员未安排赛事，请重新安排！');
                }
           }
         }
                            
         // 获取系列赛id
        $id0 = $_GET['id']; 
        $groupNum = $_POST['groupNum']; 
        $type = $_GET['type'];         
        if($id0==null || $id0=='')
            $this->error('无有效参数！');
        
        $s = new SaeStorage();  
        
        
        // 获取基本情况
        $ACTIVITY = M('activity');
        $activity0 = $ACTIVITY->where('id='.$id0)->getField('id, title, content, start_time, end_time, headcount, cover, scale_num, state, result, champion_id');  
        $activity = current($activity0);
        if($activity['cover']!=1 || $s->fileExists("imgdomain", 'post/'.$id0.'.jpg')==FALSE)
            $activity['cover'] = "__IMG__/activity.jpg";
        else
            $activity['cover'] = $s->getUrl("imgdomain", 'post/'.$id0.'.jpg');
        
         // 赛事安排类型
         $matchArrangeType = 0; // 0表示小组赛，1表示淘汰赛，2表示超出系统安排范围
        // 参赛总人数
        $ACTIVITY = M('activity');
        $headcount = $ACTIVITY->where('id='.$_GET['id'])->GetField('headcount');
         // 如果是淘汰赛，计算系统预设的分组数、每组人数和轮空数
         $groupNum = 0;
         $eachGroupAmount = 0;
         $groupVoidArray = array(); // 轮空数组
         $groupArray = array("1"=>"A","2"=>"B","3"=>"C","4"=>"D","5"=>"E","6"=>"F","7"=>"G","8"=>"H");
         $groupRoundNum = 0;
         // 淘汰赛
         if($type == 1){
             $matchArrangeType = 1;
             
             if($headcount <= 8){
                 $groupNum = 2;
                 $diff = 8 - $headcount; // 轮空数
                 $eachGroupAmount = 4;
                 $groupRoundNum = 2;
                 $scaleNum = 8;
             }
             else if($headcount <= 16){
                 $groupNum = 2;
                 $diff = 16 - $headcount; // 轮空数
                 $eachGroupAmount = 8;
                 $groupRoundNum = 3;
                 $scaleNum = 16;
             }
             else if($headcount <= 32){
                 $groupNum = 4;
                 $diff = 32 - $headcount; // 轮空数
                 $eachGroupAmount = 8;
                 $groupRoundNum = 3;
                 $scaleNum = 32;
             }
             else if($headcount <= 64){
                 $groupNum = 4;
                 $diff = 64 - $headcount; // 轮空数
                 $eachGroupAmount = 16;
                 $groupRoundNum = 4;
                 $scaleNum = 64;
             }
             else if($headcount <= 128){
                 $groupNum = 8;
                 $diff = 128 - $headcount; // 轮空数
                 $eachGroupAmount = 16;
                 $groupRoundNum = 4;
                 $scaleNum = 128;
             }
             else if($headcount <= 256){
                 $groupNum = 8;
                 $diff = 256 - $headcount; // 轮空数
                 $eachGroupAmount = 32;
                 $groupRoundNum = 5;
                 $scaleNum = 256;
             }
             else
                 $matchArrangeType = 2;
                
             // 计算每组轮空数
             if($matchArrangeType != 2){
             	 $j = 1;
                 $round = 1;
                 for($i=1; $i<=$diff; $i++){
                     $groupVoidArray[$j][$round*2] = 1;  
                     $j++;
                     if($j > $groupNum){
                         $round++;
                         $j = 1;
                     }
                 } 
                 
                
             }
         }
         // 小组赛
         else{
             $matchArrangeType = 0;
             $groupNum = $_POST['groupNum'];
             $eachGroupAmount = floor($headcount / $groupNum);
             $offset = $groupNum - $headcount % $groupNum;
             if($offset > 0)
                 $eachGroupAmount++;
             // 计算每组轮空情况
             $j = $groupNum;
             for($i=1; $i<=$offset; $i++){
                 $groupVoidArray[$j][$eachGroupAmount] = 1;  
                 $j--;
             }
         }
         
        // --------------确认安排赛事---------------
        if($_POST['ensureArrange'] == 1) {
            // 更新activity
            $ACTIVITY = M('activity');
            $ACTIVITY->id = $id0;
            $ACTIVITY->scale_num = $scaleNum;
            $ACTIVITY->group_num = $groupNum;
            
            $SERIAL_MATCH_USER = M('serial_match_user');
            $SERIAL_BASE_MATCH = M('serial_base_match');
            $BASE_MATCH = M('base_match');
            // serialMatchUser表提交数据变量
            $serialMatchUserItemArray = array();
            $serialMatchUserItemArray['serial_match_id'] = $id0;
            $serialMatchUserItemArray['round'] = 1;
            $serialMatchUserItemArray['type'] = $type;
            $serialMatchUserArray = array();
            // serialBaseMatch表提交数据变量
            $serialBaseMatchItemArray = array();
            $serialBaseMatchItemArray['serial_match_id'] = $id0;
            $serialBaseMatchItemArray['type'] = $type;
            $serialBaseMatchArray = array();
            // baseMatch表提交数据变量
            $baseMatchArray = array();
            $baseMatchArray['serial_match_id'] = $id0;
            $baseMatchArray['state'] = 0;
            $baseMatchArray['update_user'] = $_SESSION['tennis-user']['id'];
            $d = date('Y-m-d H:i:s');
            $baseMatchArray['update_date'] = $d;
            $baseMatchArray['start_date'] = $activity['start_time'];
            $baseMatchArray['end_date'] = $activity['end_time'];
            
            $sIndex = 0;
            // 存储到serial_match_user变量中
            for($i=1; $i<=$groupNum; $i++){
                for($j=1; $j<=$eachGroupAmount; $j++){
                    if($_POST[$groupArray[$i].$j] != 0) {
                        $serialMatchUserItemArray['user_id'] = $_POST[$groupArray[$i].$j];
                        $serialMatchUserItemArray['group'] = $groupArray[$i];
                        $serialMatchUserItemArray['no'] = $j;
                        $serialMatchUserArray[$sIndex] = $serialMatchUserItemArray;
                        $sIndex++;
                    }
                }
            }
            // 更新serial_match_user表
            $result = $SERIAL_MATCH_USER->addAll($serialMatchUserArray);
            if($result === false)
            	$this->error("保存serial_match_user出问题了，请联系管理员！");
            
            $sIndex = 0;
            // 分类型更新base_match，serial_base_match
            // 小组赛
            if($type == 0){
                for($i=1; $i<=$groupNum; $i++){ // 循环组
                    for($j=1; $j<$eachGroupAmount; $j++){ // 组内循环成员
                        for($k=$j+1; $k<=$eachGroupAmount; $k++){ // 组内成员全排列
                            if($_POST[$groupArray[$i].$j]!=0 && $_POST[$groupArray[$i].$k]!=0) {
                                $tmp_id = $BASE_MATCH->data($baseMatchArray)->add();
                                if($tmp_id === false)
                                    $this->error("保存base_match出问题了，请联系管理员！");
                                else{ // 存储到serial_base_match变量中
                                    $serialBaseMatchItemArray['base_match_id'] = $tmp_id;
                                    $serialBaseMatchItemArray['player1_id'] = $_POST[$groupArray[$i].$j];
                                    $serialBaseMatchItemArray['player2_id'] = $_POST[$groupArray[$i].$k];                                    
                                    $serialBaseMatchItemArray['group1'] = $groupArray[$i];
                                    $serialBaseMatchItemArray['group2'] = $groupArray[$i];                                   
                                    $serialBaseMatchItemArray['no1'] = $j;
                                    $serialBaseMatchItemArray['no2'] = $k;
                                    $serialBaseMatchArray[$sIndex] = $serialBaseMatchItemArray;
                       				$sIndex++;
                                }
                            }
                        }
                    }
                }
                // 更新serial_base_match 
                $result = $SERIAL_BASE_MATCH->addAll($serialBaseMatchArray);
                if($result === false)
            		$this->error("保存serial_base_match出问题了，请联系管理员！");
            }
            // 淘汰赛
            else if($type == 1){
                // 排列小组内淘汰赛                
                $tmp_each_group_amount = $eachGroupAmount;
                $r=1;
                for(; $r<=$groupRoundNum; $r++){
                    for($i=1; $i<=$groupNum; $i++){ // 循环组
                        for($j=1; $j<$tmp_each_group_amount;){ // 组内循环成员
                            $tmp_id = $BASE_MATCH->data($baseMatchArray)->add();
                            if($tmp_id === false)
                                $this->error("保存base_match出问题了，请联系管理员！");
                            else{ // 存储到serial_base_match变量中
                                $serialBaseMatchItemArray['base_match_id'] = $tmp_id;
                                if($r == 1){
                                    $serialBaseMatchItemArray['player1_id'] = $_POST[$groupArray[$i].$j];
                                    $serialBaseMatchItemArray['player2_id'] = $_POST[$groupArray[$i].($j+1)]; 
                                }
                                else{
                                    $serialBaseMatchItemArray['player1_id'] = -1;
                                    $serialBaseMatchItemArray['player2_id'] = -1; 
                                }
                                $serialBaseMatchItemArray['group1'] = $groupArray[$i];
                                $serialBaseMatchItemArray['group2'] = $groupArray[$i]; 
                                $serialBaseMatchItemArray['round1'] = $r;
                                $serialBaseMatchItemArray['round2'] = $r;
                                $serialBaseMatchItemArray['no1'] = $j;
                                $serialBaseMatchItemArray['no2'] = $j+1;
                                $serialBaseMatchArray[$sIndex] = $serialBaseMatchItemArray;
                                $sIndex++;
                          	}
                            $j += 2;
                        }
                        
                    }
                    $tmp_each_group_amount /= 2;
                }
                
                // 排列小组出线后淘汰赛  
                $tmp_each_group_amount *= $groupNum;
                while($tmp_each_group_amount>=1){
                    $j=1; 
                    while($j<$tmp_each_group_amount){ // 组内循环成员
                        $tmp_id = $BASE_MATCH->data($baseMatchArray)->add();
                        if($tmp_id === false)
                            $this->error("保存base_match出问题了，请联系管理员！");
                        else{ // 存储到serial_base_match变量中
                            $serialBaseMatchItemArray['base_match_id'] = $tmp_id;
                            $serialBaseMatchItemArray['player1_id'] = -1;
                            $serialBaseMatchItemArray['player2_id'] = -1;                                    
                            $serialBaseMatchItemArray['group1'] = 'S';
                            $serialBaseMatchItemArray['group2'] = 'S'; 
                            $serialBaseMatchItemArray['round1'] = $r;
                            $serialBaseMatchItemArray['round2'] = $r;
                            $serialBaseMatchItemArray['no1'] = $j;
                            $serialBaseMatchItemArray['no2'] = $j+1;
                            $serialBaseMatchArray[$sIndex] = $serialBaseMatchItemArray;
                            $sIndex++;
                        }
                        $j += 2;
                    }
                	$r++;
                    $tmp_each_group_amount /= 2;
                }
            
                // 更新serial_base_match 
                $result = $SERIAL_BASE_MATCH->addAll($serialBaseMatchArray);
                if($result === false)
                    $this->error("保存serial_base_match出问题了，请联系管理员！");
            }
            // 完成安排，跳转到赛事页面
            $this->success('安排成功！','__APP__/Admin/Match/serialMatch_update_detail?id='.$id0);
        }
        
        
        // 获取参赛选手列表
         $playerList = array();
         $all_players_id = array();
         $ACTIVITY_USER = M('activity_user');
         $player = $ACTIVITY_USER->where('activity_id='.$_GET['id'])->join('INNER JOIN user ON user.id=activity_user.user_id')->getField('user.id, user_id, truename');
         foreach($player as $key=>$value){
             $firstChar = getFirstChar($value['truename']);
             $user_name = $value['truename'];
             $user_id = $value['user_id'];
         	 $playerList[$user_id]['id'] = $user_id;  
         	 $playerList[$user_id]['name'] = $user_name; 
         	 $playerList[$user_id]['letter'] = $firstChar;
             array_push($all_players_id,$value['user_id']);
         }
         
         // 获取已安排参赛选手及对应位置
        // 已安排选手列表
        $has_set_players = array();
        // 等待随机插入的选手列表
        $rand_players = array();
        // 安排好用于返回的选手
        $players = array();
        
        // 获取已安排选手列表
        for($i=1; $i<=$groupNum; $i++){
            for($j=1; $j<=$eachGroupAmount; $j++){
                $index = $groupArray[$i].$j;
                if($_POST[$index] > 0){ // 已安排
                	array_push($has_set_players, $_POST[$index]);  
                }
            }
        }
        // 获取未安排选手列表
        $rand_players = array_diff($all_players_id,$has_set_players);
        shuffle($rand_players);
        
        // 安排这些未安排选手
        for($i=1; $i<=$groupNum; $i++){
            for($j=1; $j<=$eachGroupAmount; $j++){
                $index = $groupArray[$i].$j;
                if($_POST[$index]==null || $_POST[$index]==''){ // 待安排
                    $tmp_id = array_shift($rand_players);
                    if($tmp_id==null || $tmp_id=='')
                        break;
                	$players[$groupArray[$i]][$j][id] = $tmp_id;
                    $players[$groupArray[$i]][$j][name] = $playerList[$tmp_id]['name'];
                    $players[$groupArray[$i]][$j][letter] = $playerList[$tmp_id]['letter']; 
                }
                else if($_POST[$index] > 0){
                    $tmp_id = $_POST[$index];
                    $players[$groupArray[$i]][$j][id] = $tmp_id;
                    $players[$groupArray[$i]][$j][name] = $playerList[$tmp_id]['name'];
                    $players[$groupArray[$i]][$j][letter] = $playerList[$tmp_id]['letter'];                    
                }
            }
        }
        
         // 字母列表
         $letterList = array("A","B","C","D","E","F","G","H","I","J","K","L","M","N","O","P","Q","R","S","T","U","V","W","X","Y","Z");
         
         // 返回参数
         $this->assign("id", $_GET['id']);
         $this->assign("matchArrangeType", $matchArrangeType);
         $this->assign("groupVoidArray", $groupVoidArray);
         $this->assign("groupNum", $groupNum);
         $this->assign("eachGroupAmount", $eachGroupAmount);
         $this->assign("groupArray", $groupArray);
         $this->assign("activity", $activity);
         $this->assign("players", $players);
         $this->assign("letterList", $letterList);
         $this->assign("hasSetPlayer", 1);
         $this->assign("groupNum", $groupNum);
         $this->display();
    }
    
    
    // 赛事更新提交函数 
    public function groupSerialMatch_update_submit(){ 
        
        $serialMatchId = $_GET['serialMatchId']; 
        if($_GET['result'] == 1){
            $ACTIVITY = M('activity');
            $data0['result'] = $_POST['matchResult'];
            $data0['state'] = $_POST['matchState'];
            $result = $ACTIVITY->where("id=".$serialMatchId)->save($data0);
            if($result === FALSE) {
             $this->error('赛事结果更新失败！');
            }
    		$this->success('操作成功！','__APP__/Admin/Match/serialMatch_update_detail?id='.$serialMatchId);
        
        }
        else{
        
            $id = $_GET['id'];
            $startDate = $_POST['groupMatchStartDate'];
            $endDate = $_POST['groupMatchEndDate'];
            $winnerId = $_POST['groupMatchWinner'];
            $state = $_POST['groupMatchState'];
            $score = $_POST['groupMatchScore'];
            $offset = $_POST['groupMatchOffset'];  
            $winnerScoreOffset = $_POST['winnerScoreOffset']; 
            $loserScoreOffset = $_POST['loserScoreOffset']; 
            $round1 = $_POST['groupMatchRound1'];
            $round2 = $_POST['groupMatchRound2'];
            $player1_id = $_POST['groupMatchPlayer1_id'];
            $player2_id = $_POST['groupMatchPlayer2_id'];            
            $player1_no = $_POST['groupMatchPlayer1_no'];
            $player2_no = $_POST['groupMatchPlayer2_no'];
            $group = $_POST['groupMatchPlayer_group'];
                
            // 更新base_match数据表
            $BASE_MATCH = M('base_match');
            $BASE_MATCH->winner_id = $winnerId;
            $BASE_MATCH->score = $score;
            $BASE_MATCH->offset = $offset;
            $BASE_MATCH->winnerScoreOffset = $winnerScoreOffset;
            $BASE_MATCH->loserScoreOffset = $loserScoreOffset;
            $BASE_MATCH->start_date = $startDate;
            $BASE_MATCH->end_date = $endDate;
            $BASE_MATCH->state = $state;
            $d = date('Y-m-d H:i:s');
            $BASE_MATCH->update_date = $d;
            $BASE_MATCH->update_user = $_SESSION['tennis-user']['id'];
            $result = $BASE_MATCH->where("id=".$id)->save();
            if($result === FALSE) {
                 $this->error('base_match保存失败！');
            }
            
            // 更新serial_base_match数据表
            if($_POST['matchType'] == 0){
            	$SERIAL_BASE_MATCH = M('serial_base_match');
                $SERIAL_BASE_MATCH->round1 = $round1;
                $SERIAL_BASE_MATCH->round2 = $round2;
                
                $result = $SERIAL_BASE_MATCH->where("base_match_id=".$id)->save();
                if($result === FALSE) {
                    $this->error('serial_base_match操作失败！');
                }
            }
            // 淘汰赛需更新下一场赛事的serial_base_match数据表
            else if($_POST['matchType'] == 1 && $_POST['next_id']!=null && $_POST['next_id']!=''){
                $SERIAL_BASE_MATCH = M('serial_base_match');
                if($_POST['noPos'] == 0){
                    $tmp_no = $SERIAL_BASE_MATCH->where('base_match_id='.$id)->getField('no2');
                    $SERIAL_BASE_MATCH = M('serial_base_match');
                    if($tmp_no % 4 == 0)
                        $SERIAL_BASE_MATCH->player2_id = $winnerId;
                    else
                        $SERIAL_BASE_MATCH->player1_id = $winnerId;
                }
                else{
                    $SERIAL_BASE_MATCH = M('serial_base_match');
                    if($_POST['noPos'] == 1)
                        $SERIAL_BASE_MATCH->player1_id = $winnerId;
                    else if($_POST['noPos'] == 2)
                        $SERIAL_BASE_MATCH->player2_id = $winnerId;
                }
                    
                $result = $SERIAL_BASE_MATCH->where("base_match_id=".$_POST['next_id'])->save();
                if($result === FALSE) {
                     $this->error('serial_base_match操作失败！');
                }
                    
            }
            
            // 更新activity数据表
            $ACTIVITY = M('activity');
            $championRound =  '';
            $scaleNum = $ACTIVITY->where('id='.$serialMatchId)->getField('scale_num'); 
            switch($scaleNum) {
                   case 8:   $championRound = 3;break;
                   case 16:  $championRound = 4;break;
                   case 32:  $championRound = 5;break;
                   case 64:  $championRound = 6;break;
                   case 128: $championRound = 7;break;
                   case 256: $championRound = 8;break;
                   default: break;
            }
            if($round1 == $championRound){
                $data['state'] = '2';
                $data['champion_id'] = $winnerId;
                $result = $ACTIVITY->where('id='.$serialMatchId)->save($data);
                if($result === FALSE) {
                     $this->error('activity操作失败！');
                }
            }
            
            if($_POST['matchType'] == 0){
                // 获取每个选手最大轮次
                $maxRound11 = $SERIAL_BASE_MATCH->where('serial_match_id='.$serialMatchId.' and type=0 and player1_id='.$player1_id)->max('round1');
                $maxRound12 = $SERIAL_BASE_MATCH->where('serial_match_id='.$serialMatchId.' and type=0 and player2_id='.$player1_id)->max('round2');
                $maxRound21 = $SERIAL_BASE_MATCH->where('serial_match_id='.$serialMatchId.' and type=0 and player1_id='.$player2_id)->max('round1');
                $maxRound22 = $SERIAL_BASE_MATCH->where('serial_match_id='.$serialMatchId.' and type=0 and player2_id='.$player2_id)->max('round2');
                
                $maxRound1 = $maxRound11;
                $maxRound2 = $maxRound21;
                
                if($maxRound11 < $maxRound12)
                    $maxRound1 = $maxRound12;
                if($maxRound21 < $maxRound22)
                    $maxRound2 = $maxRound22;
            
                // 获得每个选手胜负场、净胜局数、积分
                $BASE_MATCH1 = M('base_match');
                $winCount1 = $BASE_MATCH1
                    ->join(" INNER JOIN serial_base_match ON serial_base_match.base_match_id=base_match.id and serial_base_match.type=0 and state=1 and serial_base_match.serial_match_id=".$serialMatchId." and winner_id=".$player1_id)
                    ->count();
                $winCount2 = $BASE_MATCH1
                    ->join(" INNER JOIN serial_base_match ON serial_base_match.base_match_id=base_match.id and serial_base_match.type=0 and state=1 and serial_base_match.serial_match_id=".$serialMatchId." and winner_id!=".$player2_id)
                    ->count();
                $scoreCount1_1 = $BASE_MATCH1
                    ->join(" INNER JOIN serial_base_match ON serial_base_match.base_match_id=base_match.id and serial_base_match.type=0 and state=1 and serial_base_match.serial_match_id=".$serialMatchId." and winner_id=".$player1_id)
                    ->sum('winnerScoreOffset');
                $scoreCount2_1 = $BASE_MATCH1
                    ->join(" INNER JOIN serial_base_match ON serial_base_match.base_match_id=base_match.id and serial_base_match.type=0 and state=1 and serial_base_match.serial_match_id=".$serialMatchId." and winner_id=".$player2_id)
                    ->sum('winnerScoreOffset');
                $scoreCount1_2 = $BASE_MATCH1
                    ->join(" INNER JOIN serial_base_match ON serial_base_match.base_match_id=base_match.id and serial_base_match.type=0 and state=1 and serial_base_match.serial_match_id=".$serialMatchId." and winner_id!=".$player1_id)
                    ->sum('loserScoreOffset');
                $scoreCount2_2 = $BASE_MATCH1
                    ->join(" INNER JOIN serial_base_match ON serial_base_match.base_match_id=base_match.id and serial_base_match.type=0 and state=1 and serial_base_match.serial_match_id=".$serialMatchId." and winner_id!=".$player2_id)
                    ->sum('loserScoreOffset');
                $offset1 = $BASE_MATCH1
                    ->join(" INNER JOIN serial_base_match ON serial_base_match.base_match_id=base_match.id and serial_base_match.type=0 and state=1 and serial_base_match.serial_match_id=".$serialMatchId." and winner_id=".$player1_id)
                   ->sum('offset');
                $offset2 = $BASE_MATCH1
                   ->join(" INNER JOIN serial_base_match ON serial_base_match.base_match_id=base_match.id and serial_base_match.type=0 and state=1 and serial_base_match.serial_match_id=".$serialMatchId." and winner_id=".$player2_id)
                    ->sum('offset');
                $score1 = $scoreCount1_1+$scoreCount1_2;
                $score2 = $scoreCount2_1+$scoreCount2_2;
                $lostCount1 = $maxRound1 - $winCount1;
                $lostCount2 = $maxRound2 - $winCount2;
                // 更新activity_user数据表
                $ACTIVITY_USER = M('activity_user');
                // 选手1
                $ACTIVITY_USER->score = $score1;
                $ACTIVITY_USER->win = $winCount1;
                $ACTIVITY_USER->lost = $lostCount1;
                $ACTIVITY_USER->offset = $offset1;
                $ACTIVITY_USER->round = $maxRound1;
                $result = $ACTIVITY_USER->where('activity_id='.$serialMatchId.' and user_id='.$player1_id)->save();
                if($result === FALSE) {
                     $this->error('activity_user操作失败！');
                }
                // 选手2
                $ACTIVITY_USER->score = $score2;
                $ACTIVITY_USER->win = $winCount2;
                $ACTIVITY_USER->lost = $lostCount2;
                $ACTIVITY_USER->offset = $offset2;
                $ACTIVITY_USER->round = $maxRound2;
                $result = $ACTIVITY_USER->where('activity_id='.$serialMatchId.' and user_id='.$player2_id)->save();
                if($result === FALSE) {
                     $this->error('activity_user操作失败！');
                }
            }
            
            $this->success('操作成功！','__APP__/Admin/Match/serialMatch_update_detail?id='.$serialMatchId);
         }
        
    }
    
    public function groupSerialMatch_delete(){
        $type = $_GET['type'];
        $id = $_GET['serialMatchId'];
        
        $SERIAL_MATCH_USER = M('serial_match_user');
        $SERIAL_BASE_MATCH = M('serial_base_match');
        $BASE_MATCH = M('base_match');
        
        $baseMatchIdArray = $SERIAL_BASE_MATCH->where('serial_match_id='.$id.' and type='.$type)->getField('id, base_match_id');
        $result = $SERIAL_BASE_MATCH->where('serial_match_id='.$id.' and type='.$type)->delete();
        if($result === false)
            $this->error('serial_base_match删除操作失败，请联系管理员！');
        foreach($baseMatchIdArray as $key=>$value){
            $BASE_MATCH->where('id='.$value)->delete();
        }
        $SERIAL_MATCH_USER->where('serial_match_id='.$id.' and type='.$type)->delete();
        if($result === false)
            $this->error('serial_match_user删除操作失败，请联系管理员！');
        
        $this->success('删除成功！','__APP__/Admin/Match/serialMatch_update_detail?id='.$id);
    }
        
    
    public function scale8()
    {
        
    }
    
    public function scale16()
    {
        
    }
    
    public function scale32()
    {
        
    }
    
    public function scale64()
    {
        
    }
    
    public function scale128()
    {
        
    }
    
    public function scale256()
    {
        
    }
}
?>