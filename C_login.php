<?php 
if (!defined('BASEPATH')) exit('No direct script access allowed');


class C_login extends CI_Controller {

    //官方给的写法,构造函数
    public function __construct()
    {
        parent::__construct();

        //调用自己的全局方法
        $this->load->library('best_exit/My_global_class');

        //加载数据模型
        $this->load->model('best_exit/M_memberinfo');

    }


    
    public function index()
    {
        //载入验证码辅助函数
        $this->load->helper('captcha'); 

        $speed = 'abcdefghijklmnopqrstuvwxyz1234567890';
        $word = '';
        for($i = 0; $i < 4; $i++){
            $word .= $speed[mt_rand(0,strlen($speed) - 1)];
        } 

        //配置项
        $vals = array(
            'word' => $word,
            'img_path' =>'./assets/best_exit/captcha/',
            'img_url' => base_url().'assets/best_exit/captcha/',
            'img_width' => 93,
            'img_height' =>32,
            'expiration' =>60
            );
        //创建验证码
        $cap = create_captcha($vals);
        //存储到session中
        $this->session->word = strtoupper($cap['word']);
        $data['captcha'] = $cap['image'];

        $this->load->view('best_exit/V_login',$data);
    }

    

    public function captcha(){
        //载入验证码辅助函数
        $this->load->helper('captcha');

        $speed = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIGKLMNOPQRSTUVWXYZ1234567890';
        $word = '';
        for($i = 0; $i < 4; $i++){
            $word .= $speed[mt_rand(0,strlen($speed) - 1)];
        } 
        //配置项
        $vals = array(
            'word' => $word,
            'img_path' => './assets/best_exit/captcha/',
            'img_url' => base_url().'assets/best_exit/captcha/',
            'img_width' => 93,
            'img_height' =>32,
            'expiration' =>60
            );
        //创建验证码
        $cap = create_captcha($vals);
        //存储到session中
        $this->session->word = strtoupper($cap['word']);

        echo  $cap['image'].'&&&'.$cap['word'] ;
    }

     

    public function check(){
        
        //获取username数据
        $username = $this->input->post('username');
        //
  
            $result = $this->M_memberinfo->get_memberinfo($username);

          if(!$result){ 
             echo 1; //代表该账号不存在！
             //return false;
         }else{
            if(($result['status']) == 0){
              echo 2;  //代表有此账号，但账号异常
              //return false;
            }
         }
                 
    }


    

    public function login() 
    {
        
        $username = $this->input->post('username');  
        $password = $this->input->post('password');
       
        $result = $this->M_memberinfo->get_memberinfo($username);
        //获取角色做判断
        $rolesinfo = $this->db->get_where("rolesinfo",array('rolesid'=>$result['rolesid']))->row_array();
        if(!$rolesinfo){
            redirect('best_exit/C_illegal_login/no_roles');
            die;
        }
        


        if( !$result) 
        {
           redirect('best_exit/C_illegal_login');
        }else{
            
            if ( password_verify ( $password , $result['password'] )) {
                //生成 SESSION 如同 $_SESSION['username'] = $username
                $this -> session -> username = $result['username'];
                $this -> session -> memberid = $result['memberid'];
                $this -> session -> realname = $result['realname'];
                $this -> session -> rolesid  = $result['rolesid'];

                
                //进入主界面
                redirect('index.php/best_exit/admin/C_frame_main');

             } else {
               
                redirect('best_exit/C_login');
             }
        }
    }
    
    
    public function login_out(){
            //销毁session信息
            session_destroy();
            //echo "<script type='text/javascript'>parent.layer.msg('你已安全退出！');history.back(); </script>";
            redirect('best_exit/C_login/index');
            //$this->load->view("best_exit/v_login");       
    } 
    
    
    public function rem_password($memberid){
         //获取当前管理员的memberid
        //$memberid = int($memberid);
        $data['memberid'] = $memberid;
        $this -> load ->view("best_exit/V_change_pass",$data);
    }

    //检验管理员输入的密码是否正确
    public function check_password(){
        //获取管理员id
        $memberid = $this -> input ->post("memberid");
        $result = $this -> db ->get_where("memberinfo",array("memberid"=>$memberid))->row_array();
        //获取当前管理员输入的原密码
        $password = $this->input->post('password');
        if(password_verify ( $password , $result['password'] )){
            echo 1;
        }
    }

    //修改密码
    public function change_pass(){
        //获取管理员id
        $memberid = $this -> input ->post("memberid");
        //获取密码
        $data['password'] = password_hash($this->input->post('password'), PASSWORD_BCRYPT, $this->config->item('password_cost', 'best_exit/config'));
        $result = $this->db->update('memberinfo',$data,array("memberid" => $memberid));

        //redirect($_SERVER['HTTP_REFERER']);
        //redirect('best_exit/admin/c_frame_main');
        $this->load->view("best_exit/V_change_pass_login");
        
    }

}






/* End of file c_login.php */
/* Location: .${FILE_PATH} */
