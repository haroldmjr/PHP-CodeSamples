<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Main extends CI_Controller {

	/**
	 * Will handle general site navigation and pages.
	 */

	// Constructor to load any additional libraries if needed
	public function __construct()
	{
		parent::__construct();
		// Needed models for search
		$this->load->model('search_model');
		$this->load->model('result_model');
		$this->load->model('sources_model');
	}

	// Curated main index page
	// Pulling items from specific user to populate this page
	// Should use a model, but doing here as it's temporary
	// @todo Move to a model
	public function index()
	{
		$tmpAccount = 'some-user-id-here';
		// Complex SQL to pull specific items yet add correct Base status for current user
		// Status will be NULL if not logged in, but items still display
		// DB call here as a one-off for some quick functionality tests
		
		$sql = sprintf("SELECT c.*, d.baseStatus
				FROM (
				SELECT a.*, b.baseStatus as tmpBaseStatus FROM `items` a
				LEFT JOIN user_base as b ON(b.itemID = a.base_id)
				WHERE b.user_id = '%s' and
				b.baseStatus = %d
				ORDER BY b.itemID DESC
				LIMIT 50) c
				LEFT JOIN user_base as d ON(d.user_id = '%s' and d.itemID = c.base_id)", 
				$tmpAccount, USER_BASE, $this->session->userdata('user_id'));
		$res = $this->db->query($sql);
		// Check we got results and parse
		if($res->num_rows() > 0){
			$results = array();
			foreach ($res->result_array() as $row)
			{
				unset($row['tmpBaseStatus']);
				unset($row['entryID']);
				unset($row['initial_access']);
				unset($row['last_access']);
				unset($row['times_accessed']);
				$results[] = $row;
			}
			
			$data = array(
					'status' => 'ok',
					'items' => $results
					);
		} else {
			// If here no search term given.
			// @todo Same as above should always get results
			$data = self::_requestFail('Please enter a search term.');
		}
		// Load views and pass data
		$this->template->javascript->add(array('scripts/jquery-1.7.2.min.js', 'scripts/masonry.min.js', 'scripts/bootstrap.js', 'scripts/notifier-index.js' , 'scripts/orangebox.min.js'));
		// load view
		$this->template->title->set('Base Page');
		// Trigger staff text to be displayed
		$data['staffPage'] = true;
		$this->template->content->view('new_index_view', $data);
		// publish the template
		$this->template->set_template('template_search');
		$this->template->publish();
	}
	
	// Controls main index search page and results
	public function search()
	{
		$params = self::_getRequestVars();	// Read query params wheter post or get
		if(!empty($params['term']) && !empty($params['submit'])){		// If at least a term is given perform search
			$data = array();
			// Make the call for content
			$results = $this->search_model->getContent($params);
			// Verify we got results and parse
			if(!empty($results['items']) && count($results['items']) > 0){
				// Store results for access in single view. Bomb if can't store.
				if($this->result_model->setResultList($results['items']) === false) {
					$data = self::_requestFail('Error storing search results for display.');
				} elseif($this->sources_model->setSourceList($results['sources']) === false) {
					$data = self::_requestFail('Error storing search results for display.');				
				} else {
					// Search results are saved in the session.
					// Now redirect back to home page without search flag
					// and let it display stored session results
					$refreshParams = str_replace('&search=search', '', $_SERVER['QUERY_STRING']);
					redirect("search?{$refreshParams}");
					
				}
			} else {
				// No results returned
				// @todo Should always get results
				$data = self::_requestFail('No results found.');
			}

		} elseif(!empty($params['term']) && $this->session->userdata('resultList')) {
			// If here just display results stored in the session var.
			// @todo Will kill once cache solution is in place
			
			$data['status'] = 'ok';
			$data['items'] = $this->result_model->getResultList();
			
		} else {
			// If here no search term given.
			// @todo Same as above should always get results
			$data = self::_requestFail('Please enter a search term.');
		}		
		// Load views and pass data
		$this->template->javascript->add(array('scripts/jquery-1.7.2.min.js', 'scripts/masonry.min.js', 'scripts/bootstrap.js', 'scripts/notifier-index.js' , 'scripts/orangebox.min.js'));
        // load view
        $this->template->title->set('Base Page');
       	$this->template->content->view('new_index_view', $data);
		// publish the template
        $this->template->set_template('template_search');
        $this->template->publish();
	}

	// Get select URI query variables
	private function _getRequestVars()
	{
		// Return the REQUEST variables
		$params = array(
				'term' => ($this->input->get_post('term')) ? $this->input->get_post('term') : NULL,
				'category' => ($this->input->get_post('category')) ? setCategoryID($this->input->get_post('category')): NULL,
				'type' => ($this->input->get_post('type')) ? $this->input->get_post('type') : 'json',
				'page' => ($this->input->get_post('page')) ? $this->input->get_post('page') : 1,
				'totalPages' => ($this->input->get_post('totalpages')) ? $this->input->get_post('totalpages') : 1,
				'local' => ($this->input->get_post('local')) ? true : false,
				'userID' => ($this->input->get_post('userid')) ? $this->input->get_post('userid') : NULL,
				'limit' => ($this->input->get_post('limit')) ? $this->input->get_post('limit'): NULL,
				'base_id' => ($this->input->get_post('item')) ? $this->input->get_post('item'): NULL,
				'submit' => ($this->input->get_post('search')) ? true : false
		);
		return $params;
	}

	// Method returns failed search response message
	private function _requestFail($msg = 'Content search failure.')
	{
		// If here, URI is incorrect. Return a failed status
		$response = array(
				'status' => 'failed',
				'message' => $msg,
				'items' => NULL
		);
		// Return message and stop further processing
		return $response;
	}


} // End - Main controller

/* End of file main.php */
/* Location: ./application/controllers/main.php */
?>