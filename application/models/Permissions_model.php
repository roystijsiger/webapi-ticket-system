<?php

class Permissions_model extends CI_Model{
    private $db;
    
    public function __construct() {
        parent::__construct();
        $this->db = $this->load->database('default', true);
    }
    
    
    public function GetPermissionAction($select = [], $clauses = [], $row = true){
         if($select){
            foreach($select as $value){
                $this->db->select($value);
            }
        }
        
        $this->db->from('Users_permissions_actions as Upa');
       
        foreach($clauses as $key => $value){
            $this->db->where($key, $value);
        }
        
        $q = $this->db->get();
           
        if($q->num_rows() > 0){
            if($row){
                return $q->row();
            }
            return $q->result();
        }
        return null;
    }
    
    public function GetUsersPermissions($select = [], $clauses = [], $row = false){
        if($select){
            foreach($select as $value){
                $this->db->select($value);
            }
        }
        
        $this->db->from('Users_permissions as Up')
            ->join('Users_permissions_actions as Upa', 'Upa.Id = Up.Users_permissions_actions_id');
       
        foreach($clauses as $key => $value){
            $this->db->where($key, $value);
        }
        
        $q = $this->db->get();
           
        if($q->num_rows() > 0){
            if($row){
                return $q->row();
            }
            return $q->result();
        }
        return null;
    }
    
    public function GetGroupsPermissions($select = [], $clauses = [], $row = false, $where_in = []){
        if($select){
            foreach($select as $value){
                $this->db->select($value);
            }
        }
        
        $this->db->from('Users_groups_permissions as Ugp')
            ->join('Users_permissions_actions as Upa', 'Upa.Id = Ugp.Users_permissions_actions_id');
       
        if($clauses){
            foreach($clauses as $key => $value){
                $this->db->where($key, $value);
            }
        }
        
        if($where_in){
            foreach($where_in as $key => $value){
                    $this->db->where_in($key, $value);
            }
        }
        
        $q = $this->db->get();
           
        if($q->num_rows() > 0){
            if($row){
                return $q->row();
            }
            return $q->result();
        }
        return null;
    }
    
    public function GetTaskCategoriesPermissions($groups, $task_category_id){
        $this->db
            ->select('*')
            ->from('Users_groups_tasks_categories_permissions AS ugtcp')
            ->where_in('ugtcp.Users_groups_id', $groups)
            ->where('Tasks_categories_id', $task_category_id);

        $q = $this->db->get();
        
        if($q->num_rows() > 0){
            return $q->row();
        }
        return null;
    }
}

