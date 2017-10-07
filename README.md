$userinfo_arr = $this -> db ->select("userinfo.userid,userinfo_detail.avatar,userinfo.username,userinfo_detail.sex,userinfo_detail.birth,userinfo_detail.signature,userinfo.is_simulation,userinfo.dateline")
->from("userinfo")
->join("userinfo_detail","userinfo_detail.userid = userinfo.userid")
->where("userinfo.is_simulation = 0")
->order_by("userinfo.dateline","DESC")
->limit(5,($num-1)*5)
->get()->result_array();
