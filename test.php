<?php if (!defined('BASEPATH')) exit('No direct script access allowed');


class A_exit_shop extends CI_Controller {
    public function __construct()
    {
        parent::__construct();

        $this -> load ->library('best_exit/My_global_class');
            
           
    }  

    /**
         * @api {post} /a_exit_shop/index/ 出口周边店铺
         * @apiVersion 0.2.0
         * @apiName index
         * @apiGroup a_exit_shop
         * @apiParam {string} exit_position  出口位置
         * @apiParam {number} address_x    出口坐标经度，x坐标.
         * @apiParam {number} address_y    出口坐标纬度，y坐标.
         * @apiParam {string} shop_type    商户类型，空值代表全部，选取查询条件拼接"一级类型-二级类型":例"餐饮-韩国菜".
         * @apiDescription 主界面中搜索出口附近店铺
         * @apiSuccess {number}   code                  返回码
         * @apiSuccess {string}   message               返回信息
         * @apiSuccess {json}     result                正确的结果
         * @apiErrorExample Response (example):
         *     返回示例
         *     {
         *       "code": "300"
         *       "message": "未知错误"
         *       "result": "json"
         *     }
         *    注释：店铺链接地址为：http://www.ihouser.cn/best_exit/assets/best_exit/XXX/XXX/图片名称
         */
    public function index(){
        //获取出口位置
        $exit_position = $this -> input ->get_post("exit_position");
        //获取出口x坐标
        $address_x = $this -> input ->get_post("address_x");
        //获取出口y坐标
        $address_y = $this -> input ->get_post("address_y");
        //获取商户分类查询
        $shop_type = $this -> input ->get_post("shop_type");

        if(empty($exit_position) || empty($address_x) || empty($address_y)){
           resjson(300,'参数不完整',false);
        }

        if($shop_type == ''){ //查询全部
         $this -> db ->where("merchantinfo.examined = 1");
       }else{   //按条件查询
         $this -> db ->where("merchantinfo.examined = 1");
         $this->db->like('merchantinfo.merchant_type', $shop_type, 'both');  
       }

        //获取全部商户信息
        $data = $this -> db ->select("merchant_id,name,business_pic,address_x,address_y")
                                ->from("merchantinfo")
                                //->where("examined = 1")
                                ->get()->result_array();
        if($data){
            for($i=0;$i<count($data);$i++){   
               //列表用户的x轴坐标
               $user_address_x = $data[$i]['address_x'];
               //列表用户的y轴坐标
               $user_address_y = $data[$i]['address_y'];
               //获取当前用户与列表中$re[$i]用户的距离
               $distance = GetDistance($address_y, $address_x, $user_address_y, $user_address_x);
              
               $data[$i]['distance'] = (string)$distance;  
            }

            $sort = array(
                    'direction' => 'SORT_ASC', //排序顺序标志 SORT_DESC 降序；SORT_ASC 升序
                    'field'     => 'distance',       //排序字段
            ); 

            $arrSort = array();
            foreach($data AS $uniqid => $row){  
                     foreach($row AS $key=>$value){
                         $arrSort[$key][$uniqid] = $value;
                     }
            }
        
            if($sort['direction']){
             array_multisort($arrSort[$sort['field']], constant($sort['direction']), $data);
            }

            $datas = array();
            foreach ($data as  $value) {
                // if($value['distance'] < "2"){ //距离小于2KM代表出口附近商户
                     $datas[] = array(
                            'merchant_id' => $value['merchant_id'],
                            'name' => $value['name'],
                            'business_pic' => $value['business_pic'],
                            'sparklets' => "3", //几颗星
                            'consumption' => "53",//人均消费
                            'title' => "粉&米线",//小标题
                            'exit_position' => $exit_position,
                            'distance' => $value['distance'],
                        );
                // }
            }
        }else{
            $datas = array();
        }
        

        /*===================================================================================================*/
        //获取分类类型
        $data = array();

           $yiji = $this->db->from('businessinfo')->where("business_pid = 0")->get()->result_array();

            foreach ($yiji as $key => $val) {

                 $data[$key]["title"] = $val['business_name'];
                 
                 $ss = $this->db->from('businessinfo')->where("business_pid = ".$val['business_id'])->get()->result_array();
                  
                 $sss = array();
                 foreach ($ss as $k => $v) {
                    $sss[] = $v['business_name']; 
                      
                 }

                if(empty($ss)){
                        $data[$key]["second"]=array(); 
                    }else{
                        $data[$key]["second"]=$sss; 
                    }
                 
            }

            $result = array(
                     'bussiness' => $datas,
                     'type' => $data,
                );
        
        resjson(303,'获取周边商户成功',$result);

    }
/*==============================================================================================================*/
    /**
         * @api {post} /a_exit_shop/look_merchant/ 查看店铺详情
         * @apiVersion 0.2.0
         * @apiName look_merchant
         * @apiGroup a_exit_shop
         * @apiParam {string} exit_position  出口位置
         * @apiParam {number} userid    当前用户的ID
         * @apiParam {number} merchant_id  商户的ID
         * @apiParam {number} address    出口距离商户的距离(从商户列表中获取).
         * @apiParam {number} num    分页值：1,2,3...
         * @apiDescription 当前用户的查看店铺的信息
         * @apiSuccess {number}   code                  返回码
         * @apiSuccess {string}   message               返回信息
         * @apiSuccess {json}     result                正确的结果
         * @apiErrorExample Response (example):
         *     返回示例
         *     {
         *       "code": "300"
         *       "message": "未知错误"
         *       "result": "json"
         *     }
         */
    public function look_merchant(){
        //获取出口位置
        $exit_position = $this -> input ->get_post("exit_position");
        //获取当前用户ID
        $userid = $this -> input ->get_post("userid");
        //获取当前用户查看的商户ID
        $merchant_id = $this -> input ->get_post("merchant_id");
        //获取出口距离商户的位置
        $address = $this -> input ->get_post("address");
        //获取分页值
        $num = $this -> input ->get_post("num");
        
        if(empty($exit_position)||empty($userid)||empty($merchant_id)||empty($address)){
            resjson(300,'参数不完整',false);
        }

        //判断当前用户是否存在
        $userinfo = $this->db->get_where("userinfo",array('userid'=>$userid))->row_array();
        if(!$userinfo){
          resjson(301,'用户不存在',false); 
        }
        //判断当前商户是否存在
        $merchantinfo = $this->db->get_where("merchantinfo",array('merchant_id'=>$merchant_id))->row_array();
        if(!$merchantinfo){
          resjson(301,'商户不存在',false); 
        }
        //查看当前此用户是否查看了此商户
        $merchantinfo_look = $this -> db ->get_where("merchantinfo_look",array("merchant_id"=>$merchant_id,"userid"=>$userid))->row_array();
        if(!$merchantinfo_look){
            //获取数据
            $datas = array(
                      "userid" => $userid,
                      "merchant_id" => $merchant_id,
                      "dateline" => time(),
                );
            $this -> db ->insert("merchantinfo_look",$datas);
        }else{
            $this -> db ->update("merchantinfo_look",array("dateline"=>time()),array("merchant_id"=>$merchant_id,"userid"=>$userid));
        }
        
        //获取当前商户数据
        /*//获取当前用户与列表中用户的距离
        $distance = GetDistance($address_y, $address_x, $merchantinfo['address_y'], $merchantinfo['address_x']);
        if(!(round($merchantinfo['address_x']))){
            $distances = "error";
        }else{
            $distances = (string)$distance;
        }*/
        $data = array();
        $data['merchant_data'] = array(
                'name' => $merchantinfo['name'], 
                'sparklets' => "3", //几颗星
                'consumption' => "53",//人均消费 
                'exit_position' => $exit_position,
                'distance' =>  $address,  //$distances, //出口距离商户的距离
                'business_pic' => $merchantinfo['business_pic'], //商户图片
                'address' => $merchantinfo['address'], //商户地址
                'address_x' => $merchantinfo['address_x'],
                'address_y' => $merchantinfo['address_y'],
                'business_hours' => $merchantinfo['business_day']." ".$merchantinfo['business_start_time']."-".$merchantinfo['business_end_time'], //商户营业时间
                'discribe' => "环境优美，价格便宜。",
            );
        //谁查看了本店
        $look_merchant_arr = $this -> db ->select("userinfo.userid,userinfo_detail.avatar,userinfo.username,userinfo_detail.sex,userinfo_detail.birth,userinfo_detail.signature,merchantinfo_look.dateline")
                                  ->from("merchantinfo_look")
                                  ->join("userinfo","userinfo.userid = merchantinfo_look.userid")
                                  ->join("userinfo_detail","userinfo_detail.userid = merchantinfo_look.userid")
                                  ->where("merchant_id = $merchant_id and merchantinfo_look.userid != $userid")
                                  ->order_by("merchantinfo_look.dateline","DESC")
                                  ->limit(5,($num-1)*5)
                                  ->get()->result_array();

        $userinfo_arr = $this -> db ->select("userinfo.userid,userinfo_detail.avatar,userinfo.username,userinfo_detail.sex,userinfo_detail.birth,userinfo_detail.signature,userinfo.is_simulation,userinfo.dateline")
                                    ->from("userinfo")
                                    ->join("userinfo_detail","userinfo_detail.userid = userinfo.userid")
                                    ->where("userinfo.is_simulation = 0")
                                    ->order_by("userinfo.dateline","DESC")
                                    ->limit(5,($num-1)*5)
                                    ->get()->result_array();

        /*$user_arrs = array();
        for($i=0;$i<count($userinfo_arr);$i++){
            $user = array_rand($userinfo_arr); 
            $user_arrs[] = $user;
            if($i==6){
                break;
            }
        }
        
        $user = array_unique($user_arrs);

        $user_arr = array();

        foreach ($user as  $value) {
            $user_arr[] = $userinfo_arr[$value];
        }*/
        
        $look_merchant = array_merge($look_merchant_arr,$userinfo_arr);

        for($i=0;$i<count($look_merchant);$i++){        
            $look_merchant[$i]['username'] = base64_decode($look_merchant[$i]['username']);
            $look_merchant[$i]['signature'] = base64_decode($look_merchant[$i]['signature']);    
        }

        $data['look_merchant'] = $look_merchant;

        //获取总的人数
        $look_merchant_arrs = $this -> db ->from("merchantinfo_look")
                                          ->where("merchant_id = $merchant_id and merchantinfo_look.userid != $userid")
                                          ->get()->result_array();
        $userinfo_arrs = $this -> db ->select("userinfo.userid")
                                    ->from("userinfo")
                                    ->join("userinfo_detail","userinfo_detail.userid = userinfo.userid")
                                    ->where("userinfo.is_simulation = 0")
                                    ->get()->result_array();
        $data['look_merchant_count'] = count($look_merchant_arrs) + count($userinfo_arrs);

        $data['look_merchant_share'] = "http://www.ihouser.cn/best_exit/best_exit/admin/c_share/merchant_share/$exit_position/$userid/$merchant_id/$address";

        resjson(303,"查看商户成功",$data);
    }

    
}
