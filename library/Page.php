<?php 
/*
* Simple, Light, Fast 
* Template Handler for CodeIgniter
*/

defined('BASEPATH') OR exit('No direct script access allowed');

class Template {

	private $CI;
	private $view_file_path = '';
	private $default_view_file = '';
	private $view_file_header = '';
	private $view_file_navs = '';
	private $view_file_footer = '';
	private $js = array();
	private $css = array();
	private $inline_js;
	private $inline_css;
	private $html_metas;
	private $parse_delimiters;

	public function __construct()
	{
		// Load CI Super Variable
		$this->CI =& get_instance();

		// Load Site Config
		$this->CI->config->load('site_config');

		// set vars
		$this->_load_vars();


	}

	private function _load_vars()
	{
		// Get $view_file_path from site_config 
		$this->view_file_path = $this->CI->config->item('view_file_path');
		// Get default $view_file from site_config
		$this->default_view_file = $this->CI->config->item('default_view_file');
		// Get $view_file for header from site_config
		$this->view_file_header = $this->CI->config->item('view_file_header');
		// Get $view_file for navs from site_config
		$this->view_file_navs = $this->CI->config->item('view_file_navs');
		// Get $view_file for footer from site_config
		$this->view_file_footer = $this->CI->config->item('view_file_footer');
		// Get $parse_delimiters from site_config
		$this->parse_delimiters = $this->CI->config->item('parse_delimiters');
	}

	// each meta should be in array. can only accept 1 parameters
	public function add_metas($meta_dat = array())
	{
		$this->CI->load->helper('html');
		$this->html_metas = meta($meta_dat);
	}

	// $type can only accept 'css','js','inline_css','inline_js' value and both parameter is required
	public function add_script($type,$script_url)
	{
		if(strtolower($type) == 'css')
		{
			if(is_array($script_url))
			{
				$add = array();
				foreach($script_url as $url)
				{
					$add[] = array('script_css_url'=>$url);
				}

				$this->css = array_merge_recursive($this->css,$add);
			}
			else {
				$this->css[] = array('script_css_url'=>$script_url);
			}
		} else if(strtolower($type) == 'js') {
			if(is_array($script_url))
			{
				$add = array();
				foreach($script_url as $url)
				{
					$add[] = array('script_js_url'=>$url);
				}

				$this->js = array_merge_recursive($this->js,$add);
			}
			else {
				$this->js[] = array('script_js_url'=>$script_url);
			}
		} else if(strtolower($type) == 'inline_css') {
			// defined $script_url as inline script content
			$this->inline_css = $script_url;
		} else if(strtolower($type) == 'inline_js') {
			// defined $script_url as inline script content
			$this->inline_js = $script_url;
		}
	}

	public function build($title = 'No title', $view_file = 'default', $page_dat = array(), $return = FALSE)
	{
		if(is_array($title))
		{
			// Set title as array
			$dat = (array) $title;

			// Convert array to json then print and exit;
			$this->CI->output
		        ->set_status_header(200)
		        ->set_content_type('application/json', 'utf-8')
		        ->set_output(json_encode($dat, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
		        ->_display();
			exit;
		} else {
			
			// Handle $view_file and $page_dat vars
			if($view_file === NULL)
			{
				// this will return a page that includes the skeleton files
				// requires view file to be null and $page_dat as html
				$view_file = $this->default_view_file;
				$page_dat = array('html_str'=>$page_dat);
			}
			if($view_file == FALSE)
			{
				// this will return a page that does not include the skeleton files
				// requires view file to be false and $page_dat as html
				$view_file = $this->default_view_file;
				$page_dat = array('html_str'=>$page_dat);
				$html = $this->CI->load->view($this->view_file_path.$view_file, $page_dat, TRUE);
				echo $html;

				// were done so 
				exit;
			}

			if(is_array($page_dat))
			{
				$page_dat = array_merge($page_dat,array(
						'site_name'		=> $this->CI->config->item('site_name'),
						'page_title' 	=> $title
					)
				);
			}

			// Build html
			$h = $this->CI->load->view($this->view_file_path.$this->view_file_header, $page_dat, TRUE);
			$n = $this->CI->load->view($this->view_file_path.$this->view_file_navs, $page_dat, TRUE);
			$c = $this->CI->load->view($this->view_file_path.$view_file, $page_dat, TRUE);
			$f = $this->CI->load->view($this->view_file_path.$this->view_file_footer, $page_dat, TRUE);

			$html = $h.PHP_EOL.$n.PHP_EOL.$c.PHP_EOL.$f;

			// Parse templates 
			$this->CI->load->library('parser');
			$this->CI->parser->set_delimiters($this->parse_delimiters[0],$this->parse_delimiters[1]);
			$parse_vars = array(
				'html_metas'	=> $this->html_metas,
				'script_js'		=> $this->js,
				'inline_js'		=> $this->inline_js,
				'script_css'	=> $this->css,
				'inline_css'	=> $this->inline_css
			);
			
			$html = $this->CI->parser->parse_string($html, $parse_vars, TRUE);

			// were done building html so out or return
			if($return === TRUE)
			{
				return array('h'=>$h, 'n'=>$n, 'c'=>$c, 'f'=>$f, 'html'=> $html);
			} else {
				echo $html;
			}

			// nothing else to do here so
			exit;
		}	
	}

}
