<?php

class CoachAction extends CommonAction {
    
    // 显示预约
    public function set_appointment(){
        $type = $_GET['type'];
        $id = $_GET['id'];
        
        // 显示列表
        $APPOINTMENT = M("appointment");
        $appointmentList = $APPOINTMENT->order('id desc')->select();
        $this->assign("list", $appointmentList);
        
        // 进行编辑或删除操作
        if($type!=null && $type!='' && $id!=null && $id!=''){
            $APPOINTMENT = M("appointment");
            if($type != 2){
                $appointment = $APPOINTMENT->find($id);
                $this->assign("appointment", $appointment);
                // 获取选课情况
                $APPOINTMENT_RESULT = M("appointment_result");
                $appointmentResultList = $APPOINTMENT_RESULT->select();
                if($appointmentResultList != null){
                    $this->assign("result", 1);
                }
        		$this->assign("select_list", $appointmentResultList);
                
                $this->assign("type", $type);
                $this->display();
            }else if($type == 2){
                $result = $APPOINTMENT->where("id=".$id)->delete();
                if($result) {
                    $this->success('删除成功！', '__APP__/Coach/set_appointment');                    
                }else {
                    $this->error('删除失败！');
                }
            }
            
        }
        $this->display();
    }
    
    // 提交预约
    public function submit_appointment(){
        $result = false;
        $type = $_POST['type'];
        $id = $_POST['id'];
        if($type!=null && $type!='' && $id!=null && $id!=''){
            $appointment = M("appointment"); // 实例化User对象  
            $data[id] = $_POST['id']; 
            $data[date] = $_POST['date'];
            $data[time_field] = $_POST['time_field'];
            $data[tip] = $_POST['tip'];
            
            $result = $appointment->save($data);
        }
        else{
            $appointment = M("appointment"); // 实例化User对象  
            $data[date] = $_POST['date'];
            $data[time_field] = $_POST['time_field'];
            $data[tip] = $_POST['tip'];
            
            $result = $appointment->add($data);
        }
        if($result) {
            $this->success('提交成功！', '__APP__/Coach/set_appointment');                    
        }else {
            $this->error('提交失败！');
        }
    }
    
    // 显示预约
    public function appointment_list(){
        // 显示列表
        $APPOINTMENT = M("appointment");
        $appointmentList = $APPOINTMENT->order('id desc')->select();
        $this->assign("list", $appointmentList);
       
        $this->display();
    }
    
    // 选课页面
    public function appointment(){
        $id = $_GET['id'];
        // 显示列表
        $APPOINTMENT = M("appointment");
        $appointment = $APPOINTMENT->find($id);
        $this->assign("appointment", $appointment);
       
        // 获取选课情况
        $APPOINTMENT_RESULT = M("appointment_result");
        $appointmentResultList = $APPOINTMENT_RESULT->where("appointment_id=".$id)->select();
        if($appointmentResultList != null){
            $this->assign("result", 1);
        }
        $this->assign("select_list", $appointmentResultList);
        
        $type = 0;
        if($_GET['type'] != 0)
            $type = 1;
        $this->assign("type", $type);
        $this->display();
    }
    
    // 提交选课信息
    public function submit_appointment_info(){
        $appointment_result = M("appointment_result"); // 实例化User对象  
        $data[appointment_id] = $_POST['appointment_id'];
        $data[time_field] = $_POST['time_field'];
        $data[name] = $_POST['name'];
        $data[tip] = $_POST['tip'];
        
        $result = $appointment_result->add($data);
        
        if($result) {
            $this->success('提交成功！', '__APP__/Coach/appointment?id='.$_POST['appointment_id']."&type=0");                    
        }else {
            $this->error('提交失败！');
        }
        
    }
}

?>