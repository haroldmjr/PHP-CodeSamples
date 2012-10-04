<?php
/**
 * Class to manage all product call methods
 * using a host of API's
 * 
 * @author Harold Merrell Jr.
 * @copyright 2011
 */
class Search_model extends CI_Model {
	
	// Basic search properties used in item searches for API calls
	private $searchTerm;
	private $category;
	private $type;
	private $page;
	private $local;
	private $limit;
	private $id;
	private $totalpages;
	
	// Constructor
	function __construct()
    {
        parent::__construct();
        // Load content API models
        $this->load->model('API/A_model');
        $this->load->model('API/B_model');
        $this->load->model('API/C_model');
        // Load result model for storing in session var
        $this->load->model('result_model');
    }
    
    // Base method to search new and stored content
    // @params: (array) query params 
    // @return: array of data objects, false on error.
    public function getContent(array $params = NULL)
    {
    	if(empty($params)) return false;	// Check params array has values
    	extract($params);					// Pulls all set parameters
    	// Check for empty search text. Refine later
    	if(empty($term) || $term == 'null') $this->searchTerm = NULL;
    	else $this->searchTerm = $term;
    	$this->category = $category;
    	// Make API call
    	$aCall = $this->A_model->search($this->searchTerm, $this->category);
    	$bCall = $this->B_model->search($this->searchTerm, $this->category);
    	$cCall = $this->C_model->search($this->searchTerm, $this->category);

    	// Execute mCurl and start parsing response. Mcurl is auto-loaded
    	$responses = $this->mcurl->execute();
    	
		// Now parse and map returned API responses
    	$matchingContent = array();		// Will hold search results
    	$sourceList = array();			// Will pass as reference to hold all the sources
    	foreach($responses as $callKey => $callResponse)
    	{
    		if(stripos($callKey, 'aCall-') !== false){    			
    			$Aresults = $this->A_model->mapResults($responses[$callKey]['response'], $sourceList);
    			if($Aresults) $matchingContent = array_merge($matchingContent, (array)$Aresults);
    			unset($Aresults);
    		} else if(stripos($callKey, 'bCall-') !== false){
    			$Bresults = $this->B_model->mapResults($responses[$callKey]['response'], $sourceList);
    			if($Bresults) $matchingContent = array_merge($matchingContent, (array)$Bresults);
    			unset($Bresults);
    		} else if(stripos($callKey, 'cCall-') !== false){
    			$Cresults = $this->C_model->mapResults($responses[$callKey]['response']);
    			if($Cresults) $matchingContent = array_merge($matchingContent, (array)$Cresults);
    			unset($Cresults);
    		}
    	}
    	// Validate results and randomize the resulting array content
    	if(count($matchingContent) <= 0 || count($sourceList) <= 0 || empty($matchingContent)) return false;
    	else shuffle($matchingContent);
    	// Return complete results of search items and matching sources
    	$completeResults = array('items' => $matchingContent, 'sources' =>$sourceList);
    	
    	//return $matchingContent;
    	return $completeResults;
    }
    
} // End - Content_search model class

?>
