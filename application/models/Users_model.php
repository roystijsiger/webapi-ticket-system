<?php
class Users_model extends CI_Model{
    private $db;
    
    public function __construct() {
        parent::__construct();
        $this->db = $this->load->database('default', true);
    }
    /*
     * User section
     */
    public function GetUsers(array $select = [], array $clauses = [], bool $row = false){
        //check if select array is filled in otherwise select everything
        if($select){
            foreach($select as $s){
                $this->db->select($s);
            }
        }  
        else{
            $this->db->select('*');
        }
        
        $this->db->from('Users');
        
        //check if the clauses array has at least one thing filled in
        if($clauses){
            foreach($clauses as $key => $value){
                $this->db->where($key, $value);
            }
        }
        
        //execute the query
        $q = $this->db->get();
        
        //check if there is at least one row else return null 
        if($q->num_rows() > 0){
            //check if we jsut want to get one row
            if($row){
                return $q->row();
            }
            return $q->result();
        }
        return null;
    }
    
    /*
     * Users tokens sessions
     */
    
    public function AddToken(array $data){
        $this->db->insert('Users_tokens', $data);
    }
    
    public function GetUsersTokens(array $select = [],array $clauses = [], bool $row = true){
        //check if select array is filled in otherwise select everything
        if($select){
            foreach($select as $s){
                $this->db->select($s);
            }
        }  
        else{
            $this->db->select('*');
        }
        
        //define table and the tables to join which is Users
        $this->db->from('Users_tokens as UT');
        $this->db->join('Users as U', 'U.Email = UT.Email');
        
        //check if the clauses array has at least one thing filled in
        if($clauses){
            foreach($clauses as $key => $value){
                $this->db->where($key, $value);
            }
        }
        
        //execute the query
        $q = $this->db->get();
        
        //check if there is at least one row else return null 
        if($q->num_rows() > 0){
            //check if we jsut want to get one row
            if($row){
                return $q->row();
            }
            return $q->result();
        }
        return null;
    }
    
    public function UpdateUsersTokens(array $data, array $clauses = []){
        //loop through the clauses and add them to the query
        if($clauses){
            foreach($clauses as $key => $value){
                $this->db->where($key, $value);
            }
        }
        
        //update on the above conditions
        return $this->db->update('Users_tokens', $data);
    }
    
    public function DeleteUsersTokens(array $clauses = []){
        if($clauses){
            foreach($clauses as $key => $value){
                $this->db->where($key, $value);
            }
        }
        $this->db->delete('Users_tokens');
    }
    
    public function GetUserByToken(string $token){
        //select necesarry fields to identify a person logged inb y the specicfied token
        $this->db->select('UT.Email, UT.Expiration_date, U.Id, U.First_name, U.Middle_name, U.Last_name')
                ->from('Users_tokens as UT')
                ->join('Users as U', "U.Email = UT.Email")
                ->where('UT.Token', $token);
        
        $q = $this->db->get();
        
        //check if the is at least one row 
        if($q->num_rows() > 0){
            //return row there cant be someone in the database with the same token twice.
            return $q->row();
        }
        return null;
    }
    
    public function GetGroupsByUserId($user_id){
        //write query
        $this->db->select('Ug.Id, Ug.Name')
                ->from('Users_groups_users as Ugu')
                ->join('Users_groups as Ug', 'Ug.Id = Ugu.Users_groups_id')
                ->where('Ugu.Users_id', $user_id);
        
        $q = $this->db->get();
        if($q->num_rows() > 0 ){
            return $q->result();
           
        }
        return null;
    }
}
