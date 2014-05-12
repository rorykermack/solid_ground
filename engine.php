<?php
class engine {
	
	private $host = 'localhost';
	private $name = '';
    private $user = '';
    private $password = '';
    
    public $site = '';
    private $directory = '';
    private $email = '';

	
	public function __construct() {
		
		session_start();
		$this->connection();
    }
	
	private function connection() {
		
		try {
		    $this->db = new PDO('mysql:host='.$this->host.';dbname='.$this->name.'', $this->user, $this->password);
		    $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		    $this->db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		    
		} 
		catch(PDOException $error) {
		    echo 'ERROR: ' . $error->getMessage();
		}
         
        return true;	
	}

	private function prefix($array,$prefix = ':') {
		
		$data = array();
		foreach($array as $key => $value) {
		
			$data[$prefix.$key] = $value;
		}
		
		return $data;
	}

	public function retrive($attributes = array()) {
		
		$statement = "SELECT ".implode(', ',$attributes['fields'])." FROM ".$attributes['table'];
				
		if(isset($attributes['join'])) {
			
			$statement .= " LEFT JOIN ".$attributes['join']['table']." ON ".$attributes['join']['parent']." = ".$attributes['join']['child'];
		}
		
		
		$parameters = array();
		if(isset($attributes['matches'])) { 
		
			$counter = 0;
			foreach($attributes['matches'] as $match => $value) {
						
				$statement .= ($counter == 0 ? " WHERE " : " AND ").$value['bound'].($value['operator'] ? $value['operator'] : ' = ').":".$value['bound'];
				$parameters[$value['bound']] = $value['parameter'];
				$counter++;
			}
		}
		
		if(isset($attributes['order'])) {
			
			$statement .= " ORDER BY ".$attributes['order']." ".($attributes['type'] ? $attributes['type'] : "").($attributes['limit'] ? " LIMIT ".$attributes['limit'] : "");
		}
		
		$data = $this->db->prepare($statement);
		$data->execute($parameters);
		
		
		$results = array();
		while($result = $data->fetch()) {
			
			$results[] = $result;
		}
		
		return $results;
	}
	
	public function insert($attributes = array()) {
		
		$statement = "INSERT INTO ".$attributes['table'];
				
		$statement .= " (".implode(', ',array_keys($attributes['data'])).") VALUES (".implode(', ',array_keys($this->prefix($attributes['data']))).")";
				
		$data = $this->db->prepare($statement);
		$data->execute($attributes['data']);
		
		return true;
	}
	
	public function update($attributes = array()) {
		
		$statement = "UPDATE ".$attributes['table'].' SET ';
		
		foreach($attributes['data'] as $data => $value) {
			
			$statement .= ($data != key($attributes['data']) ? "" : ", ").$data." = :".$data;
		}
		
		$counter = 0;
		foreach($attributes['matches'] as $match => $value) {
		
			$statement .= ($counter == 0 ? " WHERE " : " AND ").$match." = :$match";
			$counter++;
		}
		
		$data = $this->db->prepare($statement);
		$data->execute(array_merge($attributes['data'],$attributes['matches']));
		
		return true;
	}
	
	public function page() {
				
		$templates = (isset($_GET['ajax'])) ? array() : array('header.php','footer.php'));
		
		$template = (key($_GET) ? key($_GET).'.php' : 'home.php');
		
		array_splice($templates,1,0,$template);
			
		foreach($templates as $template) { 

	        if(file_exists($this->directory.$template)) {
				
	        	include_once($this->directory.$template);
	        }
        }
        
        return true;
	}
}
?>
