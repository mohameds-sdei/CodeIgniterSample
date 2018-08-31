<?php defined('BASEPATH') OR exit('No direct script access allowed');
// This can be removed if you use __autoload() in config.php OR use Modular Extensions
require APPPATH.'/libraries/REST_Controller.php';

class Auth extends REST_Controller
{
    function __construct()
    {
        // Construct our parent class
        parent::__construct();
        // Configure limits on our controller methods. Ensure
        // you have created the 'limits' table and enabled 'limits'
        // within application/config/rest.php
        $this->methods['user_get']['limit'] = 500; //500 requests per hour per user/key
        $this->methods['user_post']['limit'] = 100; //100 requests per hour per user/key
        $this->methods['user_delete']['limit'] = 50; //50 requests per hour per user/key
        // remember me code
        if ( $this->input->post( 'rememberme' ) )
            $this->config->set_item('sess_expire_on_close', '0');
    }
    // Login rest api
    public function userlogin_get()
    {
        error_reporting(E_ALL);
        // if don't input username or password, reponse null error code 400
        if(!$this->get('username') && !$this->get('password')){
            $this->response(NULL, 400);
        }

        $tableName = "ff_user_mst";
        $user = $this->base_model->get_login_data($tableName,$this->get('username'),$this->get('password'));
        if($user){
            // user login
            if ($user->is_active == 'Y') {
                $newdata = array(
                    'userid'    => $user->user_id_pk,
                    'username'  => ucfirst($user->first_name).' '.ucfirst($user->last_name),
                    'email'     => $user->email,
                    'logged_in' => TRUE
                    );
                $sessionData = $this->session->set_userdata($newdata);
                $this->base_model-> update_session_user($this->session,$user->user_id_pk); // VHP
                $resp = array(
                    "status"=>"user_success",
                    "msg"=>"login successful",
                    "data"=>$newdata
                    );
                    if($this->get('callback')){
                        echo $this->get('callback')."(".json_encode($resp).")";
                    }else{
                        $this->response($resp, 200);
                    }
                }else{
                    $resp = array(
                        "status"=>"error",
                        "msg"=>"User is inactive"
                        );
                    if($this->get('callback')){
                        echo $this->get('callback')."(".json_encode($resp).")";
                    }else{
                        $this->response($resp, 200);
                    }
                }
        } else {
            $resp = array(
                "status"=>"error",
                "msg"=>"Wrong user Name or Password"
                );
            if($this->get('callback')){
                echo $this->get('callback')."(".json_encode($resp).")";
            }else{
                $this->response($resp, 200);
            }
        }
    }
    
    // user register api
    public function userregister_get()
    {
        //error_reporting(E_ALL);
        $table = "ff_user_mst";
        $where = array('email'=> $this->get('uemail'));
        $resultCheck = $this->base_model->check_existent($table,$where);
        if(!$resultCheck){
            $ref_code = $this->get('ref_code');
            $roleId = $this->get('role_id');
            if(!isset($roleId) || !is_numeric($roleId) ) {
                $roleId = 4;
            }
            $user_credits = 0;
            if($ref_code!=''){
                $getParentId = $this->base_model->get_all_record_by_id($table,array('ref_code'=>$ref_code,'role_id_fk'=>3));
                $parentId = (int)$getParentId[0]->user_id_pk;
                $resultCheck = $this->base_model->check_existent($table,array('ref_code'=>$ref_code,'role_id_fk'=>3));
                if(!$resultCheck) {
                    $resp = array(
                        'status'=>'error',
                        'msg'=>'Invalid Referral Code.'
                        );
                    if($this->get('callback')){
                        echo $this->get('callback')."(".json_encode($resp).")";
                    }else{
                        $this->response($resp, 200);
                    }
                }
            } else {
                if(!empty($this->get('parent_id'))){
                    $parentId= $this->get('parent_id');
                }else{
                    $parentId = 0;
                }
            }
            if(isset($parentId) && $parentId)
                $user_credits = 6;
            $username = $this->get('uname');
            if($username!=''){
                $resultCheck = $this->base_model->check_existent($table,array('user_name'=>$username));
                if($resultCheck) {
                    $resp = array(
                        'status'=>'error',
                        'msg'=>'Username taken.'
                        );
                    if($this->get('callback')){
                        echo $this->get('callback')."(".json_encode($resp).")";
                    }else{
                        $this->response($resp, 200);
                    }
                }
            }
            $data = array(
                'password' => password_hash($this->get('upass'),PASSWORD_BCRYPT),
                'first_name' => $this->get('fname'),
                'last_name' => $this->get('lname'),
                'email'=>$this->get('uemail'),
                'mobile'=>  $this->get('uphone'),
                'license_no' => $this->get('ulicence'),
                /*Add by Arpit*/
                'ref_code' => $ref_code,
                /*End by Arpit*/
                'company_name' => $this->get('cname'),
                'company_add' => $this->get('caddress'),
                'parent_id' => $parentId,
                'user_credits' => $user_credits,
                'role_id_fk' => $roleId,
            );
            if($username!=''){
                $data['user_name']=$this->get('uname');
            }
            $resp = $this->base_model->insert_one_row( $table, $data );
            if($resp){
                // get last inserted user id
                $lastId = $this->base_model->get_last_insert_id();
                // get data from user table
                $newUser = $this->base_model->get_record_result_array( 'ff_user_mst', array('user_id_pk' => $lastId) );
                // session
                $backend = $this->get('backend');
                if(!$backend) {
                    $newdata = array(
                        'userid'    => $newUser[0]['user_id_pk'],
                        'username'  => ucfirst( $newUser[0]['first_name'] ).' '.ucfirst( $newUser[0]['last_name'] ),
                        'email'     => $newUser[0]['email'],
                        'logged_in' => TRUE
                        );
                    $sessionData = $this->session->set_userdata($newdata);
                }

                $userName = $this->get('fname').' '.$this->get('lname');
                $name = 'Administrator';
                $message = '<table cellpadding="0" cellspacing="0" border="0"  width="100%" style="background: url(http://cardbanana.net/demo/farmingflyers/assets/img/bg/1.png); background-size:cover; margin: 0; padding: 0; border:1px solid #ccc;" >
                              <tr>
                                <td style="padding:0 20px; box-shadow: 0 2px 2px rgba(0, 0, 0, 0.05), 0 1px 0 rgba(0, 0, 0, 0.05); font-family:Helvetica;">
                                    <table width="100%" cellpadding="5">
                                        <tr>
                                            <!--<td style="text-align:center;"><img src="http://cardbanana.net/demo/farmingflyers/assets/img/logo.png" width="200px" style="padding:10px;" /></td>-->
                                            <td style="text-align:center;"><img src='. site_url().'assets/img/loglogo.png" width="200px" style="padding:10px;" /></td>

                                        </tr>

                                    </table>
                                </td>
                              </tr>

                              <tr>
                                    <td style=" padding:5px 20px;">
                                        <table width="90%" cellpadding="10" cellspacing="0" style="font-size:13px; margin:0 auto; text-align:center; font-family:Helvetica; color:#666666; background:#ffffff;">
                                                <tr><td style="color: #666; font-weight: bold; margin-bottom:0px;"><span style="font-size: 26px;">Welcome to AgentPro http://www.agentpro.io</span></td></tr>
                                                <tr>
                                                <td>
                                                <table cellpadding="10" cellspacing="0" width="100%" style="font-size:14px; text-align:center; font-family:Helvetica; color:#666666; margin-top:-15px;">
                                                   <tr>
                                                       <td><strong>Congratulations! You have successfully registered.</strong></td>
                                                   </tr>
                                                <tr>
                                                  <td style="font-size:12px; text-align:center;">Thank you for taking the time to register for an account on http://www.agentpro.io.<br> We look forward to helping you find the right flyer that can help you achieve your marketing goals.<br><br></td>
                                                </tr>
                                                <tr>
                                                    <td>
                                                      <a href="javascript:;" style="background:#CB4231; padding:10px 100px; border-radius:5px; text-decoration:none; color:#fff; font-size:20px; outline:none; font-weight:bold;">Log in</a>
                                                    </td>
                                                </tr>
                                                <tr>
                                                  <td style="font-size:12px; text-align:center;"><input type="checkbox"> Click here to disable your account if you are not the intended recipient.</td>
                                                </tr>
                                                 <tr>
                                                  <td style="font-size:12px; text-align:center; line-height:20px;">Warm Regards,<br>
                                                    AgentPro.io Team <br>
                                                    <a href="#" style="color:#666;">http://www.agentpro.io</a></td>
                                                  <td></td>
                                                </tr>

                                               </table>
                                               </td>
                                           </tr>
                                    </table>
                                </td>
                              </tr>

                              <tr>
                                <td>
                                    <table width="90%" cellspacing="5" cellpadding="5" style=" font-size:11px; font-family:Helvetica; color:#999; padding:0px; margin: auto">
                                        <tr>
                                          <td>
                                            <a href='. site_url().' style="color:#999; border-right:1px solid #999; padding:0px 15px;">Privacy</a>
                                            <a href='. site_url().' style="color:#999; border-right:1px solid #999; padding:0px 15px;">About us</a>
                                            <a href='. site_url().' style="color:#999; padding:0 15px;">FAQ</a>
                                          </td>
                                          <td>&copy; 2015 - All Rights Reserved.</td>
                                            <td style="text-align:right; padding-right:15px;"><a href="'. site_url().'"><img src="http:cardbanana.net/demo/farmingflyers/prototype/farmingflyer/img/social-icons/facebook.png" width="20"></a>
                                              <a href="'. site_url().'"><img src="http:cardbanana.net/demo/farmingflyers/prototype/farmingflyer/img/social-icons/twitter.png" width="20"></a>
                                              <a href="'. site_url().'"><img src="http:cardbanana.net/demo/farmingflyers/prototype/farmingflyer/img/social-icons/linkedin.png" width="20"></a>
                                              <a href="'. site_url().'"><img src="http:cardbanana.net/demo/farmingflyers/prototype/farmingflyer/img/social-icons/googleplus.png" width="20"></a>
                                              <a href="'. site_url().'"><img src="http:cardbanana.net/demo/farmingflyers/prototype/farmingflyer/img/social-icons/rss.png" width="20"></a>
                                              <a href="'. site_url().'"><img src="http:cardbanana.net/demo/farmingflyers/prototype/farmingflyer/img/social-icons/skype.png" width="20"></a>
                                            </td>
                                        </tr>
                                    </table>
                                 </td>
                              </tr>
                              </tr>
                            </table>';
                $this->load->helper('sendemail');
                $send = send_email('noreply@farmingflyers.com',$name, $this->get('uemail'),'AgentPro Registration', $message);
                
                if($this->get('plan_checkbox') == 'true'){
                        require_once(APPPATH.'libraries/StripeInterface.php');
                        try {
                        //set api key
                        $stripe = array(
                          "secret_key"      => "TEST",
                          "publishable_key" => "TEST"
                        );
                        $stripInterface = new StripeInterface();
                        $token = $stripInterface->createToken($this->get('uemail'),$this->get('card_num'),$this->get('expmonth'),$this->get('expyear'),$this->get('cardcvv'));
                        $createCustomer = $stripInterface->createCustomer();
                        $subscription = $stripInterface->subscribeToStripe();
                        $subscriptionDetail = json_encode($subscription);
                        if(isset($subscription['create_subscription_response']['id'])){
                            $paid_amount = ($subscription['create_subscription_response']['plan']->amount)/100;
                            $current_period_end = date('Y-m-d H:i:s', $subscription['create_subscription_response']['current_period_end']);
                            $current_period_start = date('Y-m-d H:i:s', $subscription['create_subscription_response']['current_period_start']);
                            // Save deatil
                            $subscriptionData = array(
                                'user_id' => $newUser[0]['user_id_pk'],
                                'name' => $this->get('fname'),
                                'email' => $this->get('uemail'),
                                'card_num' => $this->get('card_num'),
                                'card_cvc' => $this->get('cardcvv'),
                                'card_exp_month' => $this->get('expmonth'),
                                'card_exp_year' => $this->get('expyear'),
                                'paid_amount' => $paid_amount,
                                'paid_amount_currency' => 'USD',
                                'payment_status' => $subscription['status'],
                                'subscription_data' => $subscriptionDetail,
                                'plan_id' => $subscription['create_subscription_response']['plan']->id,
                                'product_id' => $subscription['create_subscription_response']['plan']->product,
                                'nickname' => $subscription['create_subscription_response']['plan']->nickname,
                                'subscription_id' => $subscription['create_subscription_response']['id'],
                                'current_period_end' => $current_period_end,
                                'current_period_start' => $current_period_start,
                                'status' => 0,
                                'customer' =>$subscription['create_subscription_response']['customer'],
                                'created' => date('Y-m-d H:i:s'),
                                'modified' => date('Y-m-d H:i:s'),
                            );
                            $result2 = $this->base_model->insert_one_row('ff_strip_users',$subscriptionData);
                            
                            /*Add unique code when user subscribe subscripton plan*/
                            $subUniqueCode = 'SUB'.date('Y').$newUser[0]['user_id_pk'];
                            $data = array(
                                'subscription_usercode' =>$subUniqueCode
                            );
                            $where = array(
                                'user_id_pk' => $newUser[0]['user_id_pk']
                            );
                            $result2 = $this->base_model->update_record_by_id('ff_user_mst',$data,$where);
                            
                            if($result2){
                                $resp = array(
                                    'status'=>'success',
                                    'msg'=>'User added successfully.'
                                );
                                if($this->get('callback')){
                                    echo $this->get('callback')."(".json_encode($resp).")";
                                }else{
                                    $this->response($resp, 200);
                                }
                            }
                        }   
                    }catch (Exception $e) {
                      // Something else happened, completely unrelated to Stripe
                        $resp = array(
                            'status'=>'error',
                            'msg'=>'User could not be added.'
                        );
                        if($this->get('callback')){
                            echo $this->get('callback')."(".json_encode($resp).")";
                        }else{
                            $this->response($resp, 200);
                        }
                    }
                }else if($send){
                    //echo"2";die;
                    $resp = array(
                        'status'=>'success',
                        'msg'=>'User added successfully.'
                    );
                    if($this->get('callback')){
                        echo $this->get('callback')."(".json_encode($resp).")";
                    }else{
                        $this->response($resp, 200);
                    }
                }
             } else {
                $resp = array(
                    'status'=>'error',
                    'msg'=>'User could not be added.'
                );
                if($this->get('callback')){
                    echo $this->get('callback')."(".json_encode($resp).")";
                }else{
                    $this->response($resp, 200);
                }
            }
        } else {
            $resp = array(
                'status'=>'error',
                'msg'=>'Email already exists.'
            );
            if($this->get('callback')){
                echo $this->get('callback')."(".json_encode($resp).")";
            }else{
                $this->response($resp, 200);
            }
        }
    }
    
    // ramdon string function
    private function generateRandomString() {
        return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"), 0, 8);
    }
    // forgot password api
    public function userforgotpass_get()
    {
        $data = array(
            'email'=>$this->get('uemail')
        );
        $table = "ff_user_mst";
        $result = $this->base_model->get_record_by_id($table, $data);
        if($result){
            $userId = $result->user_id_pk;
            $userName =  $result->first_name;
            $pemail = $result->email;
            $random_password = $this->generateRandomString();
            $data = array(
                'password' => password_hash($random_password, PASSWORD_BCRYPT)
            );
            $where = array(
                'user_id_pk' => $userId
            );
            $result2 = $this->base_model->update_record_by_id($table,$data,$where);
            if($result2){
                $name = 'Administrator';
                $message ='<table cellpadding="0" cellspacing="0" border="0"  width="100%" style="background: url(''); background-size:cover; margin: 0; padding: 0; border:1px solid #ccc;" >
                              <tr>
                                <td style="padding:0 20px; box-shadow: 0 2px 2px rgba(0, 0, 0, 0.05), 0 1px 0 rgba(0, 0, 0, 0.05); font-family:Helvetica;">
                                    <table width="100%" cellpadding="5">
                                        <tr>
                                            <td style="text-align:center;"><img src="" width="200px" style="padding:10px;" /></td>

                                        </tr>

                                    </table>
                                </td>
                              </tr>

                              <tr>
                                    <td style=" padding:5px 20px;">
                                        <table width="90%" cellpadding="10" cellspacing="0" style="font-size:13px; margin:0 auto; text-align:center; font-family:Helvetica; color:#666666; background:#ffffff;">
                                                <tr><td style="color: #666; font-weight: bold;"><span style="font-size: 26px;">Temporary Password</span></td></tr>
                                                <tr>
                                                <td>
                                                <table cellpadding="10" cellspacing="0" width="100%" style="font-size:14px; text-align:center; font-family:Helvetica; color:#666666; margin-top:-15px;">
                                                   <tr>
                                                       <td style="font-size:12px; text-align:center;">Forgot your password? Not a problem we got your back. We have created a temporary password <br>that you can use.  You can change this at anytime by going to your account settings.</td>
                                                   </tr>
                                                   <tr><td>Your New Password is: <strong>'.$random_password.'</strong><br><br></td></tr>
                                                   <tr>
                                                    <td>
                                                      <a href="javascript:;" style="background:#37bcac; padding:10px 100px; border-radius:5px; text-decoration:none; color:#fff; font-size:20px; outline:none; font-weight:bold;">Log in</a>
                                                    </td>
                                                </tr>
                                                    <tr><td style="font-size:12px; text-align:center;"><br>We hope you enjoy you stay at FarmingFlyers.com, if you have any problems, questions, options, or suggestions,<br> please feel free can contact or mail us any time.</td></tr>


                                                <tr>
                                                  <td style="font-size:12px; text-align:center; line-height:20px;">Warm Regards,<br>
                                                    FarmingFlyer.com Team <br>
                                                    <a href="#" style="color:#666;">TEST</a></td>
                                                  <td></td>
                                                </tr>

                                                <tr>
                                                  <td style="font-size:10px; text-align:center; border-top:1px solid #ccc;">TEST</td>
                                                </tr>

                                               </table>
                                               </td>
                                           </tr>
                                    </table>
                                </td>
                              </tr>

                              <tr>
                                <td>
                                    <table width="90%" cellspacing="5" cellpadding="5" style=" font-size:11px; font-family:Helvetica; color:#999; padding:0px; margin: auto">
                                        <tr>
                                        <td> <a href="javascript:;" style="color:#999; border-right:1px solid #999; padding:0px 15px;">Privecy</a>
                                            <a href="javascript:;" style="color:#999; border-right:1px solid #999; padding:0px 15px;">About us</a>
                                            <a href="javascript:;" style="color:#999; padding:0 15px;">FAQ</a></td>
                                          <td>&copy; 2015 - All Rights Reserved.</td>

                                            <td style="text-align:right; padding-right:15px;"><a href="javascript:;"><img src="http:cardbanana.net/demo/farmingflyers/prototype/farmingflyer/img/social-icons/facebook.png" width="20"></a>
                                              <a href="javascript:;"><img src="" width="20"></a>
                                              <a href="javascript:;"><img src="" width="20"></a>
                                              <a href="javascript:;"><img src="" width="20"></a>
                                              <a href="javascript:;"><img src="" width="20"></a>
                                              <a href="javascript:;"><img src="" width="20"></a>



                                            </td>
                                        </tr>
                                    </table>
                                 </td>
                              </tr>
                              </tr>
                            </table>';
                $this->load->helper('sendemail');
                $send = send_email('noreply@farmingflyers.com',$name, $pemail,'Farming Flyers Reset Password', $message);
		if($send){
                    $resp = array(
                        'status'=>'success',
                        'msg'=>'Password has been sent to your registered email.'
                    );
                    if($this->get('callback')){
                        echo $this->get('callback')."(".json_encode($resp).")";
                    }else{
                        $this->response($resp, 200);
                    }
                }else{
                    $resp = array(
                        'status'=>'error',
                        'msg'=>'Reset password could not be sent. Please try again.'
                    );
                    if($this->get('callback')){
                        echo $this->get('callback')."(".json_encode($resp).")";
                    }else{
                        $this->response($resp, 200);
                    }
                }
            }
        }else{
            $resp = array(
                'status'=>'error',
                'msg'=>'Email does not exists.'
            );
            if($this->get('callback')){
                echo $this->get('callback')."(".json_encode($resp).")";
            }else{
                $this->response($resp, 200);
            }
        }

    }
    
    // change password
    public function changepassword_get()
    {
            $userId = $this->get('userid');
            $oldPassword = $this->get('oldpassword');
            $newPassword = $this->get('newpassword');
            $confirmPassword = $this->get('confirmpassword');
            $where = array('password' => $oldPassword);
            $resultCheck = $this->base_model->check_existent('att_user_mst',$where);
            if($resultCheck){
                if(!empty($newPassword) && !empty($confirmPassword) && ($newPassword == $confirmPassword)) {
                    $data = array(
                        'password'=>$newPassword
                    );
                    $where = array(
                        'user_id_pk'=>$userId
                    );
                    $result2 = $this->base_model->update_record_by_id('att_user_mst',$data,$where);
                    if($result2){
                        $resp = array(
                            'status'=>'success',
                            'msg'=>'Password updated successfully.'
                        );
                        echo $this->get('callback')."(".json_encode($resp).")";
                    }else{
                        $resp = array(
                            'status'=>'error',
                            'msg'=>'Password could not be updated.'
                        );
                        echo $this->get('callback')."(".json_encode($resp).")";
                    }
                }else{
                    $resp = array(
                        'status'=>'error',
                        'msg'=>'Password does not match.'
                    );
                    echo $this->get('callback')."(".json_encode($resp).")";
                }
            }else{
                $resp = array(
                    'status'=>'error',
                    'msg'=>'Old password is invalid.'
                );
                 echo $this->get('callback')."(".json_encode($resp).")";
            }

    }
    // logout api
    public function logout_get()
    {
            $userId = $this->get('userid');
            $this->session->unset_userdata('userid');
            $this->session->sess_destroy();
            if($userId){
                $resp = array(
                    'status'=>'success',
                    'msg'=>'Logout successfully.'
                );
                if($this->get('callback')){
                    echo $this->get('callback')."(".json_encode($resp).")";
                }else{
                    $this->response($resp, 200);
                }
            }else{
                $resp = array(
                    'status'=>'error',
                    'msg'=>'Logout failled'
                );
                if($this->get('callback')){
                    echo $this->get('callback')."(".json_encode($resp).")";
                }else{
                    $this->response($resp, 200);
                }
            }

    }
}
