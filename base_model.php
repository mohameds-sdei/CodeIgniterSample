<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Base_model extends CI_Model
{
    /* load constructors */
    public function __construct()
    {
        parent::__construct();
        error_reporting(E_ALL ^ E_NOTICE);
    }
    /* check data existence in tables */
    public function check_existent($table,$where)
    {
        $query = $this->db->get_where($table,$where);
        if($query->num_rows()>0){
            return TRUE;
        } else {
            return FALSE;
        }
    }

    /*  get recrod count */
    public function record_count($table)
    {
        return $this->db->count_all($table);
    }

    /*  get recrod count */
    public function record_count2($table, $category )
    {
        if($category!=0){
            $this->db->where('category_id_fk', $category);
        }
        return $this->db->count_all($table);
    }


    /* insert data into table */
    public function insert_one_row($table,$data)
    {
        return $this->db->insert($table,$data);
    }
    /* insert multiple row in one time */
    public function insert_multiple_row($table,$data)
    {
        return $this->db->insert_batch($table,$data);
    }
    /* get max record with alias */
    public function get_max_record_withalias($table,$columname,$alias)
    {
        $this->db->select_max($columname,$alias);
        $query=$this->db->get($table);
        return $query->row();
    }
    /*  retrun only one row */
    public function get_record_by_id($table,$data)
    {
        $query = $this->db->get_where($table,$data);
        return $query->row();
    }
    /* get all record by condition; returns only one row */
    public function get_all_record_by_condition($table,$data)
    {
        $query = $this->db->get_where($table,$data);
        return $query->result();
    }
    // result array
    public function get_record_result_array($table,$data)
    {
        $query = $this->db->get_where($table,$data);
        return $query->result_array();
    }
    // result array
    public function get_record_result_array_by($table,$data,$column,$order="asc")
    {
        $this->db->from($table);
        $this->db->where($data);
        $this->db->order_by($column, $order);
        $query = $this->db->get();
        return $query->result_array();
    }
    /* get only email from nsf_user table */
    public function getUserEmail($id = null)
    {
        $this->db->select('user_name,primary_email');
        $this->db->from('nsf_user');
        $this->db->where('user_id_pk', $id);
        return $this->db->get()->result();
    }
    /* LOGIN WITH USERNAME OR EMAIL AND PASSWORD; retruns only one row */
    public function get_login_data($table,$login,$password)
    {
            /* get data from user table */
            $res = $this->db->query("SELECT * FROM $table WHERE email='".$login."'");
            if(password_verify($password, $res->row()->password)) {
            	return $res->row();
	    }
	    return null;
    }

    public function is_expired($table,$login,$password)
    {
        $expiry_date = date('Y-m-d H:i:s', time());
        $res = $this->db->query("SELECT * FROM $table WHERE primary_email='".$login."' and user_password='".$password."' and expiry_date >= '".$expiry_date."' ");
        $getRec = $res->num_rows();
        if($getRec < 1){
            $where = array(
                'primary_email' => $login,
                'user_password' => $password
            );
            $this->update_record_by_id($table, array('is_active'=>'N'), $where);
            return 1;
        }else{
            return 0;
        }
    }
    /* retrun only one or many record  */
    public function get_all_record_by_id($table,$where,$column_name=null,$ordery_by=null)
    {
        $query=$this->db->get_where($table,$where);
        if(!empty($column_name)&&!empty($ordery_by)){
               $this->db->order_by($column_name,$ordery_by);
            }
            return $query->result();
    }
    /* get last inserted id */
    public function get_last_insert_id()
    {
        return $this->db->insert_id();
    }
    /* update record by id */
    public function update_record_by_id($table,$data,$where)
    {
        $query = $this->db->update($table,$data,$where);
        return $this->db->affected_rows();
//        return $query;
    }
    /* update record by id with conditions */
    public function update_record_by_id1($table,$data,$where)
    {
        $this->db->update($table,$data,$where);
        return $this->db->affected_rows();
    }
    /* count all rows from table */
    public function countrow($table)
    {
        //$this->db->where_in('usertype','user');
        //$this->db->where_in('usertype','c_admin');
        return $this->db->count_all($table);
    }
    /* count all rows from table with conditions */
    public function countrows($table)
    {
        $this->db->where('usertype','user');
        $this->db->or_where('usertype','c_admin');
        return $this->db->count_all($table);
    }
    /* function for count only users */
    public function countusers($column, $userType,$roleId,$adminId,$user_role_id)
    {
        $this->load->library('role_lib');
        $sql = "SELECT COUNT(user_id_pk) AS count_user FROM ff_user_mst WHERE $column = '".$userType."' and role_id_fk={$user_role_id}";
        if($this->role_lib->is_sales_rep($roleId)) {
            $sql.=" and parent_id = ".$adminId;
        } else if($this->role_lib->is_manager_l1($roleId)){
            $sql = "SELECT COUNT(main.user_id_pk) AS count_user 
                    FROM ff_user_mst as main
                    join ff_user_mst as s on s.user_id_pk=main.parent_id
                    WHERE main.$column = '".$userType."' and main.role_id_fk = '".$user_role_id."' and s.parent_id=".$adminId." and main.role_id_fk=4";
        }
        $countResult = $this->db->query($sql);
        return $countResult->row();
    }
    public function countproducts($roleId,$adminId,$product_type)
    {
        $sql = "SELECT COUNT(main.product_id_pk) AS count_products FROM ff_product_mst as main where main.product_type_fk='{$product_type}' ";
        $countResult = $this->db->query($sql);
        return $countResult->row();
    }
    public function countprojects($roleId,$adminId)
    {
        $this->load->library('role_lib');
        $sql = "SELECT COUNT(main.project_id_pk) AS count_projects FROM ff_my_flyers as main ";
        if($this->role_lib->is_sales_rep($roleId)) {
            $sql.=" join ff_user_mst as e on e.user_id_pk=main.user_id_fk WHERE e.parent_id=".$adminId;
        } else if($this->role_lib->is_manager_l1($roleId)){
            $sql.=" join ff_user_mst as e on e.user_id_pk=main.user_id_fk
                    join ff_user_mst as s on s.user_id_pk=e.parent_id
                    WHERE s.parent_id=".$adminId;
        }
        $countResult = $this->db->query($sql);
        return $countResult->row();
    }
    public function count_new_users($table, $column, $userType,$adminId = null)
    {
        if(!is_null($adminId)) {
            $countResult = $this->db->query("SELECT COUNT( * ) AS count_user FROM $table WHERE $column >= '".$userType." AND parent_id = ".$adminId);
        } else {
            $countResult = $this->db->query("SELECT COUNT( * ) AS count_user FROM $table WHERE $column >= '".$userType."'");
        }
        // echo $this->db->last_query(); die();
        return $countResult->row();
    }
    public function count_new_signup($adminId=null,$roleId=null){
        $this->load->library('role_lib');
        $sql = "SELECT COUNT(*) AS count_user FROM ff_user_mst WHERE registered_date > DATE_SUB(NOW(), INTERVAL 30 DAY) and role_id_fk=4 ";
        if($this->role_lib->is_sales_rep($roleId)) {
            $sql.=" and parent_id = ".$adminId;
        } else if($this->role_lib->is_manager_l1($roleId)){
            $sql = "SELECT COUNT(main.user_id_pk) AS count_user 
                    FROM ff_user_mst as main
                    join ff_user_mst as s on s.user_id_pk=main.parent_id
                    WHERE main.registered_date > DATE_SUB(NOW(), INTERVAL 30 DAY) and s.parent_id=".$adminId." and main.role_id_fk=4";
        }
    	$countResult = $this->db->query($sql);
    	return $countResult->row();
    }
    
    public function count_online_users($roleId=null,$adminId=null){
        $loggedIn = $this->db->query("SELECT user_data FROM ci_sessions where user_data!=''");
        $userIds = array();
        foreach ($loggedIn->result() as $info) {
            $userData = unserialize($info->user_data);
            if(isset($userData['userid'])) {
                $userIds[] = $userData['userid'];
            }
        }
        if(is_null($roleId)) {
            return count($userIds);
        }
        else if(!empty($userIds)){
            $this->load->library('role_lib');
            $sql = "SELECT COUNT(*) AS count_user FROM ff_user_mst WHERE user_id_pk in (".implode(",", $userIds).") ";
            if($this->role_lib->is_sales_rep($roleId)) {
                $sql.=" and parent_id = ".$adminId;
            } else if($this->role_lib->is_manager_l1($roleId)){
                $sql = "SELECT COUNT(main.user_id_pk) AS count_user 
                        FROM ff_user_mst as main
                        join ff_user_mst as s on s.user_id_pk=main.parent_id
                        WHERE main.user_id_pk in (".implode(",", $userIds).")  and s.parent_id=".$adminId;
            }
            $countResult = $this->db->query($sql);
            return $countResult->row()->count_user;
        }
        else {
            return 0;
        }
    }
    
    /* count row by ids */
    public function count_row_by_ids($table,$param)
    {
        $query = $this->db->count_all($table,$param);
        // echo $this->db->last_query(); die();
        return $query;
    }
    /* count rows by id with conditions */
    public function count_row_by_id($table=null,$where_column=null,$user_id=null)
    {
        $res = $this->db->query("SELECT COUNT( * ) AS count_task FROM  $table WHERE $where_column = $user_id and is_active = 'Y'");
        return $res->result();
    }
    /* count join rows by id */
    public function count_join_row_by_id($user_id=null)
    {
        $res = $this->db->query("SELECT COUNT( * ) AS replied_count FROM nsf_support_ticket WHERE user_id_fk = $user_id AND is_replied = 'Y' AND is_read = 'N' ");
        if($res){
            return $res->result();
            } else {
                return false;
                }
    }
    /* count all parent member */
    public function count_rows_by_id($uid)
    {
        $res = $this->db->query("SELECT COUNT( * ) AS member_count FROM ff_user_mst WHERE parent_id = $uid");
        if($res){
            return $res->result();
            } else {
                return false;
                }
    }
    // get new flyers
    public function getNewFlyer($table,$limit,$offset)
    {
        $this->db->where("is_active", "Y");
        $this->db->order_by("active_from", "desc");
        $query = $this->db->get($table, $limit, $offset);
        return $query->result();
    }
    // get most flyers
    public function getNewFlyer2($table,$limit,$offset)
    {
        $this->db->where("is_active", "Y");
        $this->db->order_by("total_favs", "desc");
        $query = $this->db->get($table,$limit,$offset);
        return $query->result();
    }
    // get best buy flyers
    public function getNewFlyer3($table,$limit,$offset)
    {
        $this->db->where("is_active", "Y");
        $this->db->order_by("total_purchase", "desc");
        $query = $this->db->get($table,$limit,$offset);
        return $query->result();
    }
    // get order inoive and cart detail
    public function my_order($inv)
    {
        $result = $this->db->query("SELECT
                                        f . *,
                                        date_format(f.invoice_date,'%M %d, %Y') as inv_date,
                                        'Y' as is_success
                                    FROM
                                        ff_invoices f
                                    where
                                        invoice_id_pk = $inv;");
        // echo $this->db->last_query();
        return $result->result();
    }
    // my category

    public function my_category($pid)
    {
        $result = $this->db->query("select
                                        category_name
                                    from
                                        ff_category_mst
                                    where
                                        category_id_pk = (select
                                                category_id_fk
                                            from
                                                ff_product_mst
                                            where
                                                product_id_pk = '$pid')");
        // echo $this->db->last_query();
        return $result->row();
    }
    // get my fav flyer
     public function getFavFlyer($uid)
    {
        $this->db->select('*');
        $this->db->from('ff_product_mst');
        $this->db->join('ff_my_favourites','ff_product_mst.product_id_pk = ff_my_favourites.product_id_ck','left');
        $where=array('ff_product_mst.is_active'=>'Y','ff_my_favourites.user_id_ck'=>$uid);
        $this->db->where($where);
        // $this->db->group_by("user_id_pk");
        $query = $this->db->get();
        // echo $this->db->last_query();
        return $query->result();
    }


    /* pagination data */
    public function get_pagination_data($table,$limit='10',$offset='0')
    {
        return $this->db->get($table,$limit,$offset);
        //return $res->result();
    }
    /* get pagination data for all users */
    public function get_pagination_datas_for_all_users($table,$limit='10',$offset='0')
    {
        $this->db->where('usertype','user');
        $this->db->or_where('usertype','c_admin');
        return $this->db->get($table,$limit,$offset);
        //return $res->result();
    }
    /* get all records from table */
    public function all_records($mytable)
    {
        $query = $this->db->get($mytable);
        return $query->result();
    }

    /* get all records from table */
    public function all_records_order_by($mytable,$column,$by='ASC')
    {
        $sqlQuery = " SELECT ffi.*, ffmf.flyer_pdf FROM ff_invoices ffi,ff_my_cart ffmc,ff_my_flyers ffmf where ffmf.project_id_pk = ffmc.project_id_fk and ffmc.cart_id_pk = ffi.cart_id_fk order by ffi.invoice_id_pk; ";
        $query = $this->db->query($sqlQuery);
        return $query->result();

    }
	
	public function get_recent_products(){
		$sqlquery = "select product_id_pk, product_type_fk, product_name, product_desc, category_name, active_from 
				from ff_product_mst a, ff_category_mst b 
				where a.category_id_fk = b.category_id_pk 
				order by active_from desc 
				limit 50";
		$query = $this->db->query($sqlquery);
		return $query->result();
	}
    
    /* get all records by in query */
    public function get_all_record_by_in($table,$colum,$wherein)
    {
        $this->db->where_in($colum,$wherein);
        $res=$this->db->get($table);
        return $res->result();
    }
    /* delete all records by id */
    public function delete_record_by_id($table,$where)
    {
        $query = $this->db->delete($table,$where);
        // techo $this->db->las_query(); die();
        return $query;
    }
    /* delete all records by conditions */
    public function delete_record_by_id1($table,$where,$managerid1)
    {
        $this->db->where_in('userid',$where);
        $this->db->where('managerid',$managerid1);
        $this->db->delete($table);
    }
    /* get users record from three tables using join*/
    public function getUserRecords()
    {
        $this->db->select('*');
        $this->db->from('nsf_user');
        $this->db->join('nsf_user_subscription','nsf_user.user_id_pk = nsf_user_subscription.user_id_fk','left');
        $this->db->join('nsf_subscription_transaction','nsf_user.user_id_pk = nsf_subscription_transaction.user_id_fk','left');
        $this->db->join('nsf_subscription_mst','nsf_user_subscription.subscription_type_id_fk = nsf_subscription_mst.subscription_type_id_pk','left');
        $where=array('nsf_user.user_role'=>'subscriber','nsf_user.is_active'=>'Y');
        $this->db->where($where);
        $this->db->group_by("user_id_pk");
        $query = $this->db->get();
        return $query->result();
    }
    /* get records of user by id */
    public function get_user_details($uid = NULL)
    {
        $query = $this->db->query("select
            um.user_id_pk as uid,
            um.username as uname,
            um.first_name as fname,
            um.last_name as lname,
            um.primary_email as pemial,
            sm.school_id_pk as sid,
            sm.school_name as sname,
            sc.class_id_pk as cid,
            sc.class_name as cname,
            ss . *
        from
            att_user_mst as um,
            att_school_mst as sm,
            att_school_class as sc,
            att_school_student as ss
        where
            um.user_id_pk = ss.student_id_ck
                and sm.school_id_pk = ss.school_id_ck
                and sc.class_id_pk = ss.class_id_fk
        and um.user_id_pk = $uid ");
        return $query->row();
    }
    // user model
    // get user password
    public function get_password($data)
    {
        $this->db->select('password');
        $this->db->where('user_id_pk',$data);
        $query = $this->db->get('ff_user_mst');
        return $query->result_array();

    }
    // update user password
    public function update_password($data,$userId)
    {
        $data = array(
            "password" => password_hash($data, PASSWORD_BCRYPT)
            );
        $this->db->where('user_id_pk',$userId);
        $query = $this->db->update('ff_user_mst',$data);
        return $query;
    }

    // update all password
    public function update_to_hashPassword(){
		$this->db->select('user_id_pk, password');
        $query = $this->db->get('ff_user_mst');

		foreach ($query->result() as $user){
			$data = array(
            	"password" => password_hash($user->password, PASSWORD_BCRYPT)
            );
        	$this->db->where('user_id_pk',$user->user_id_pk);
        	$update_query = $this->db->update('ff_user_mst',$data);
		}
		return true;
    }

    // get flyer records from flyer table and my cart table
    public function get_flyers($uid)
    {
        $query = $this->db->query("
                            SELECT * FROM ff_my_flyers f left join ff_products_in_invoices pi on f.project_id_pk = pi.product_id  where f.user_id_fk = $uid order by project_id_pk desc
                            ");
        // echo $this->db->last_query(); die();

        return $query->result();
    }
   //
    public function flyers_list()
    {
        $query = $this->db->query("
                            SELECT * FROM ff_product_mst f order by active_from desc limit 0,18
                            ");
        // echo $this->db->last_query(); die();

        return $query->result();
    }

    //
    public function flyers_list2($limit, $start,$category=0, $producy_type = 0)
    {
        /* @TODO: there is another method availabel at category_model */
        //pt.label,
        $sqlQuery = "select group_concat(c.category_id_pk SEPARATOR ' ') as category_ids, p.*
                from ff_product_mst as p 
                join ff_product_types as pt on pt.product_type_pk = p.product_type_fk
                join ff_product_category as pc on pc.product_id_ck=p.product_id_pk
                join ff_category_mst as c on c.category_id_pk = pc.category_id_ck
                where p.is_active='Y' ";
        if($category){
            $sqlQuery .= "   and c.category_id_pk='".$category."' ";
        }
        if($producy_type){
            $sqlQuery .= "   and p.product_type_fk='".$producy_type."' ";
        }
        $sqlQuery .= " GROUP BY p.product_id_pk ";
        $sqlQuery .= " order by active_from desc limit ".$start.",".$limit;

        $query = $this->db->query($sqlQuery);
        // echo $this->db->last_query(); die();

        return $query->result();
    }
    public function flyers_list2_count($limit, $start,$category=0, $product_type=0)
    {
        //pt.label,
        $sqlQuery = "select group_concat(c.category_id_pk SEPARATOR ' ') as category_ids, p.*
                from ff_product_mst as p 
                join ff_product_types as pt on pt.product_type_pk = p.product_type_fk
                join ff_product_category as pc on pc.product_id_ck=p.product_id_pk
                join ff_category_mst as c on c.category_id_pk = pc.category_id_ck
                where p.is_active='Y' ";
        if($category){
            $sqlQuery .= "   and c.category_id_pk='".$category."' ";
        }
        if($producy_type){
            $sqlQuery .= "   and p.product_type_fk='".$producy_type."' ";
        }
        $sqlQuery .= " GROUP BY p.product_id_pk ";
        $query = $this->db->query($sqlQuery);
        // echo $this->db->last_query(); die();

        return $query->num_rows();
    }

    public function user_invoice($roleId,$adminId)
    {
        $this->db->select("inv.*"); //,flyer.flyer_pdf
        $this->db->join('ff_user_mst user', 'user.user_id_pk=inv.user_id_fk');
        $this->load->library('role_lib');
        if($this->role_lib->is_admin($roleId)) {
            $this->db->select("s.first_name as s_first_name,s.last_name as s_last_name");
            $this->db->join('ff_products_in_invoices pro', 'pro.invoice_id=inv.invoice_num','left');
            $this->db->join('ff_my_flyers flyer', 'pro.product_id=flyer.project_id_pk','left');
            $this->db->join('ff_user_mst s', 's.user_id_pk=user.parent_id and s.role_id_fk=3','left');
        } else if($this->role_lib->is_manager_l1($roleId)){
            $this->db->select("s.first_name as s_first_name,s.last_name as s_last_name");
            $this->db->join('ff_user_mst s', 's.user_id_pk=user.parent_id and s.role_id_fk=3');
            $this->db->where('s.parent_id',$adminId);
        } else if($this->role_lib->is_sales_rep($roleId)){
            $this->db->join('ff_user_mst s', 's.user_id_pk=user.parent_id');
            $this->db->join('ff_products_in_invoices pro', 'pro.invoice_id=inv.invoice_num');
            $this->db->join('ff_my_flyers flyer', 'pro.product_id=flyer.project_id_pk');
            $this->db->where('s.user_id_pk',$adminId);
        }
        
        /*
        if($this->role_lib->is_sales_rep($roleId)) {
            $sql.=" join ff_user_mst as e on e.user_id_pk=main.user_id_fk WHERE e.parent_id=".$adminId;
        } else if($this->role_lib->is_manager_l1($roleId)){*/

        $this->db->order_by('inv.invoice_date','DESC');
        $this->db->group_by('inv.invoice_num');
        $query = $this->db->get('ff_invoices inv');
        return $query->result();
    }
	
    public function getWidthHeightOfFlyerId($flyerId) {
    	$result = $this->db->query("select width, height, t.product_type_pk from ff_my_flyers prj, ff_product_types t, ff_product_mst p where t.product_type_pk = p.product_type_fk and prj.product_id_fk = p.product_id_pk and prj.project_id_pk = $flyerId");
    	return $result->row();
    }
    
    public function getWidthHeightOfProductId($productId) {
    	$result = $this->db->query("select b.width, b.height,b.width_inch,b.height_inch,b.product_type_pk from ff_product_mst a, ff_product_types b where a.product_type_fk = b.product_type_pk and a.product_id_pk = $productId");
    	return $result->row();
    }
    public function online_users_list($roleId,$adminId){
        $loggedIn = $this->db->query("SELECT * FROM ci_sessions where user_data!=''");
        $userIds = array();
        foreach ($loggedIn->result() as $info) {
            $userData = unserialize($info->user_data);
            if(isset($userData['userid'])) {
                $userIds[] = $userData['userid'];
            }
        }
        //var_dump($userIds);
        if(!empty($userIds)){
            $this->load->library('role_lib');
            $sql = "SELECT user_id_pk FROM ff_user_mst WHERE user_id_pk in (".implode(",", $userIds).") ";
            if($this->role_lib->is_admin($roleId)){
                return array('session_data'=>$loggedIn->result(),'email'=>true);    
            } else if($this->role_lib->is_sales_rep($roleId)) {
                $sql.=" and parent_id = ".$adminId;
            } else if($this->role_lib->is_manager_l1($roleId)){
                $sql = "SELECT main.email 
                        FROM ff_user_mst as main
                        join ff_user_mst as s on s.user_id_pk=main.parent_id
                        WHERE main.user_id_pk in (".implode(",", $userIds).")  and s.parent_id=".$adminId;
            }
            $query = $this->db->query($sql);
            //echo $this->db->last_query();
            //var_dump($query->result_array());
            $users = array_column($query->result_array(),'email');
            //var_dump($users);
            return array('session_data'=>$loggedIn->result(),'emails'=>$users);
        }
        else {
            return false;
        }
    }
    public function lastest_online_users_list($roleId,$adminId){
        $sql = "SELECT user_id_pk FROM ff_user_mst";
        $lastloggedIn = $this->db->query("SELECT * 
                                            FROM ff_user_mst as u,ff_userlogin_log as log 
                                            WHERE u.user_id_pk = log.user_id_pk  ORDER BY log.last_activity DESC");
        if(!empty($lastloggedIn)) {
            if ($this->role_lib->is_admin($roleId)) {
                return array('user' => $lastloggedIn->result(), 'email' => true);
            } else if ($this->role_lib->is_sales_rep($roleId)) {
                $sql .= " where parent_id = " . $adminId;
            } else if ($this->role_lib->is_manager_l1($roleId)) {
                $sql = "SELECT main.email 
                            FROM ff_user_mst as main
                            join ff_user_mst as s on s.user_id_pk=main.parent_id
                            WHERE s.parent_id=" . $adminId;
            }

            $query = $this->db->query($sql);
            $users = array_column($query->result_array(), 'email');
            return array('user' => $lastloggedIn->result(), 'emails' => $users);
        }
        else {
                return false;
            }
    }
    public function update_session_user($session,$user_id)
    {
        $data = array(
            "user_id_pk"=> $user_id,
            "user_agent" => $session->userdata("user_agent"),
            "ip_address" => $session->userdata("ip_address"),
            "last_activity" => $session->userdata("last_activity")
        );
        //$this->db->where('user_id_pk',$user_id);
        //$update_query = $this->db->update('ff_user_mst',$data);
        $this->db->insert("ff_userlogin_log",$data);
    }
}
?>
