<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
ob_start();
class Frontend extends CI_Controller
{
   // Initialize Constructor Here
    function __construct()
    {
        parent::__construct();
    }

     function sendmail()
    {

        $this->load->helper('sendemail');

        send_email('info@farmingflyers.com','noreply','prashant.softwares@gmail.com','Email Testing','Testing the email class.');

    }
   // frontend view
    public function index()
    {
        $data['title'] = "Farming Flyers";
        $this->load->model('category_model');
        $data['prod_type_cat'] = $this->category_model->get_prod_type_cat();
        // new flyers
        $data['product_types'] = $this->base_model->all_records('ff_product_types');
        $data['newFlyers1'] = $this->base_model->getNewFlyer('ff_product_mst','4','0');
        $data['newFlyers2'] = $this->base_model->getNewFlyer('ff_product_mst','4','4');
        $data['newFlyers3'] = $this->base_model->getNewFlyer('ff_product_mst','4','8');

        $data['mostFlyers1'] = $this->base_model->getNewFlyer2('ff_product_mst','4','0');
        $data['mostFlyers2'] = $this->base_model->getNewFlyer2('ff_product_mst','4','4');
        $data['mostFlyers3'] = $this->base_model->getNewFlyer2('ff_product_mst','4','8');

        $data['bestFlyers1'] = $this->base_model->getNewFlyer3('ff_product_mst','4','0');
        $data['bestFlyers2'] = $this->base_model->getNewFlyer3('ff_product_mst','4','4');
        $data['bestFlyers3'] = $this->base_model->getNewFlyer3('ff_product_mst','4','8');

          $this->load->view('frontend/header',$data);
          $this->load->view('frontend/index',$data);
          $this->load->view('frontend/footer',$data);
    }
    // flyer list
    public function flyerlist()
    {
      if($_POST["type"] == "flyerlist"){

        $config["base_url"] = base_url() . "frontend/flyerlist";
        $config["per_page"] = 18;
        $config["uri_segment"] = 3;
	$this->pagination->initialize($config);
	$page = ($this->uri->segment(3)) ? $this->uri->segment(3) : 0;
        $category = ($this->uri->segment(4)) ? $this->uri->segment(4) : '0';
        $product_type = ($this->uri->segment(5)) ? $this->uri->segment(5) : '0';
        $myflyers = $this->base_model->flyers_list2($config["per_page"], $page, $category,$product_type);
	    $flyerlist = $this->load->view('frontend/flyerlist',array('myflyers'=>$myflyers),true);

                        $resp = array(
                          'status' => 'success',
                          'flyerlist' => $flyerlist
                          );
                        echo json_encode($resp);
        }else{
            $resp = array(
              'status' => 'error',
              'msg' => 'Invalid Request.'
              );
            echo json_encode($resp);
        }
    }

    // flyer list
    public function pagination($start=0,$category=0,$product_type=0)
    {

        $config["base_url"] = "";
        $config["total_rows"] = $this->base_model->flyers_list2_count(18, $start,$category,$product_type);
        $config["per_page"] = 18;
        $config["uri_segment"] = 3;
        $config['full_tag_open'] = "<ul class='pagination pagination-small'>";
        $config['full_tag_close'] ="</ul>";
        $config['num_tag_open'] = '<li>';
        $config['num_tag_close'] = '</li>';
        $config['cur_tag_open'] = "<li class='active'><a href='#'>";
        $config['cur_tag_close'] = "<span class='sr-only'></span></a></li>";
        $config['next_tag_open'] = "<li>";
        $config['next_tagl_close'] = "</li>";
        $config['prev_tag_open'] = "<li>";
        $config['prev_tagl_close'] = "</li>";
        $config['first_tag_open'] = "<li>";
        $config['first_tagl_close'] = "</li>";
        $config['last_tag_open'] = "<li>";
        $config['last_tagl_close'] = "</li>";
        $config['anchor_class'] = 'class="pagination_link"';
	$this->pagination->initialize($config);
	echo $this->pagination->create_links();
    }

    // about us page view
    public function aboutus()
    {
      $data['title'] = "About Us";
      $this->load->view('frontend/header_static',$data);
      $this->load->view('frontend/about');
      $this->load->view('frontend/footer');
    }
    // how it works page view
    public function howitworks()
    {
      $data['title'] = "How it works";
      $this->load->view('frontend/header_static',$data);
      $this->load->view('frontend/howitworks');
      $this->load->view('frontend/footer');
    }
    // super simple pricing
    public function simplepricing()
    {
      $data['title'] = "Super Simple Pricing";
      $this->load->view('frontend/header_static',$data);
      $this->load->view('frontend/simplepricing');
      $this->load->view('frontend/footer');
    }
    // FAQ
    public function faq()
    {
      $data['title'] = "FAQ";
      $this->load->view('frontend/header_static',$data);
      $this->load->view('frontend/faq');
      $this->load->view('frontend/footer');
    }
    // contact us
    public function contactus()
    {
      $data['title'] = "Contact Us";
      $this->load->view('frontend/header_static',$data);
      $this->load->view('frontend/contactus');
      $this->load->view('frontend/footer');
    }

    public function partners()
    {
      $data['title'] = "Partners";
      $this->load->view('frontend/header_static',$data);
      $this->load->view('frontend/partners');
      $this->load->view('frontend/footer');
    }

    
   // Class ends here
}
?>
