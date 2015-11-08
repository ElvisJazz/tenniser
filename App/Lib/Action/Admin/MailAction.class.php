<?php

class MailAction extends AdminAction {
    
    public function mail(){
        $mailbox = $_GET['mailbox'];
        
        $this->assign('mailbox', $mailbox);
    	$this->display();    
    }
    
    public function sendMail(){
    	$mailbox = $_POST['mailbox'];
        $title = $_POST['title'];
        $content = $_POST['content'];
        
        $mail = new SaeMail();
        //$mail->setAttach( array("my_photo.jpg" => "照片的二进制数据" ));
        $ret = $mail->quickSend( 
            $mailbox ,
            $title,
            $content ,
            "tenniser2014@gmail.com" ,
            "jjaazz901222" 
            );
        
        $mail->clean();	
       
        if ($ret === false)
            var_dump($mail->errno(), $mail->errmsg());
        else{
            // 更新日志
            //=================
            $data['module'] = '后台管理-邮件';
            $data['operation'] = '发送邮件：mailbox='.$mailbox." title=".$title;
            $data['update_user'] = $_SESSION['tennis-user']['id'];
            $data['ip'] = $this->getIP();
            $log = M('operation_log');
            $log->add($data);            
            //=================
            
            $this->success('发送成功！','/');
        }
    }
}

?>