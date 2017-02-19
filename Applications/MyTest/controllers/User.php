<?php

class UserController extends BaseController {

    public function init()
    {
        parent::init();
    }
    
    public function indexAction() {
        $info = array(
            'uid'       => 1024,
            'age'       => 100,
            'roleName'  => 'xxxxx',
        );
        $data = array('code'=>1, 'info'=>$info);
        echo json_encode($data);
    }

}