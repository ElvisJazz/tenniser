<?php
// 用户模型
class UserModel extends Model {
    // 定义自动验证
    protected $_validate    =   array(
        array('username','require','用户名必须'),
        array('password','require','密码必须'),
        array('sex','require','性别必须'),
        array('mailbox','require','邮箱必须'),
        );
    
}
?>