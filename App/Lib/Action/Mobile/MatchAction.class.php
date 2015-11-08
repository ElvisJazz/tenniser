<?php

class MatchAction extends CommonAction {
    public function SerialMatch_detail(){      
        // 获取系列赛id
        $id = $_GET['id'];   
        $players = array();
        $scale_html = "";
        if($id==null || $id=='')
            $this->error('无有效参数！');
        $s = new SaeStorage();  
        
        // 获取基本情况
        $ACTIVITY = M('activity');
        $activity0 = $ACTIVITY->where('id='.$id)->getField('title, content, start_time, end_time, headcount, cover, scale_num, result, champion_id');  
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
        $base_match = $BASE_MATCH->join(' INNER JOIN serial_base_match ON serial_base_match.serial_match_id='.$id.' and serial_base_match.base_match_id=base_match.id and serial_base_match.type=0 and serial_base_match.serial_match_id='.$id)
            ->join(' INNER JOIN serial_match_user ON serial_match_user.serial_match_id='.$id.' and serial_base_match.player1_id = serial_match_user.user_id and serial_match_user.type=0')
            ->order('serial_match_user.group, state desc,start_date')->getField('base_match.id, group, state, score, start_date, end_date, player1_id, player2_id, round1, round2');
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
                $group_list[$index]['players'][$subIndex]['start_date'] = $value['start_date'];
                $group_list[$index]['players'][$subIndex]['end_date'] = $value['end_date'];
                $group_list[$index]['players'][$subIndex]['vs'] = $user_no[$value['player1_id']]['truename'].'【'.$value['score'].'】'.$user_no[$value['player2_id']]['truename'];
                $group_list[$index]['players'][$subIndex]['round'] = '['.$value['round1'].','.$value['round2'].']';
                $group_list[$index]['players'][$subIndex]['no1'] = $user_no[$value['player1_id']]['no'];
                $group_list[$index]['players'][$subIndex]['no2'] = $user_no[$value['player2_id']]['no'];
                $subIndex++;
            }
        }
        
        // 获得淘汰赛对阵
       $SERIAL_MATCH_USER1 = M('serial_match_user');
       $user_name = $SERIAL_MATCH_USER1->where('serial_match_id='.$id.' and round=1 and type=1')->join('INNER JOIN user ON user.id=serial_match_user.user_id')->getField('user_id, truename, no');
       
         $base_match = $BASE_MATCH->join(' INNER JOIN serial_base_match ON serial_base_match.base_match_id=base_match.id and serial_base_match.type=1 and serial_base_match.serial_match_id='.$id)
            ->order('state desc,start_date')->getField('base_match.id, state, score, start_date, end_date, player1_id, player2_id, round1, round2,group1,group2,no1,no2');
       
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
             // 设置冠军
        	$players['W'] = $user_name[$activity['champion_id']]['truename'];
         }
        
        $elimination_list = null;
        $index = -1;
        $subIndex = 0;
        $round = 0;
        $title = '';
        // 设定1/8，1/4，1/2，决赛列表
        $base_match = $BASE_MATCH->join(' INNER JOIN serial_base_match ON serial_base_match.base_match_id=base_match.id and serial_base_match.group1="S" and serial_base_match.type=1 and serial_base_match.serial_match_id='.$id)
            ->order('state desc,start_date')->getField('base_match.id, state, score, start_date, end_date, player1_id, player2_id, round1, round2,group1,group2,no1,no2');
        if($base_match != null){
            $this->assign('exist3', 1);
            
        	// 判断淘汰赛显示阵型
           switch($activity['scale_num']) {
               case 8:   $players = scale8Compute($base_match, $user_name); $scale_html="Match:scale8";break;
               case 16:    $scale_html="Match:scale16";break;
               case 32:   $players = scale32Compute($base_match, $user_name); $scale_html="Match:scale32";break;
               case 64:   $players = scale64Compute($base_match, $user_name); $scale_html="Match:scale64";break;
               case 128:   $players = scale128Compute($base_match, $user_name); $scale_html="Match:scale128";break;
               case 256:   $players = scale256Compute($base_match, $user_name); $scale_html="Match:scale256";break;
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
        $this->assign('elimination_list', $elimination_list);
        $this->assign('players_list', $userList);
        
        $this->assign('scale_html', $scale_html);
        $players['enable'] = 0;
        $this->assign('players', $players);
        
        $this->display();
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