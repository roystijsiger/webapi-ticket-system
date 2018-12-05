<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Task_model
 *
 * @author royst
 */
class Task_model extends CI_Model{
    private $db;
    
    public function __construct() {
        parent::__construct();
        $this->db = $this->load->database('default', true);
    }
    
    public function GetCategories($select = [], $clauses = [], $row = false){
        if(count($select) > 0){
            foreach($select as $value){
                $this->db->select($value);
            }
        }
        else{
            $this->db->select('*');
        }
        
        $this->db->from('Tasks_categories');
        
        if(count($clauses) > 0){
            foreach($clauses as $key => $value){
                $this->db->where($key, $value);
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
    
    public function AddCategory($task_category){
        return $this->db->insert('Tasks_categories', $task_category);
    }
    
    public function UpdateCategory($data, $clauses = []){
        if(count($clauses) > 0){
            foreach($clauses as $key => $value){
                $this->db->where($key, $value);
            }
        }
        return $this->db->update('Tasks_categories', $data);
    }
    
    public function GetTask($select = [], $clauses = [], $row = false){
           if(count($select) > 0){
            foreach($select as $value){
                $this->db->select($value);
            }
        }
        else{
            $this->db->select('*');
        }
        
        $this->db->from('Tasks');
        
        if(count($clauses) > 0){
            foreach($clauses as $key => $value){
                $this->db->where($key, $value);
            }
        }
        
        $this->db->order_by('Completed');
        
        $q = $this->db->get();
        
        if($q->num_rows() > 0){
            if($row){
                return $q->row();
            }
            return $q->result();
        }
        return null;
    }
    
    public function AddTask($task){
        return $this->db->insert('Tasks', $task);
    }
    
    /*
     * Time spetn section
     */
    public function GetTimeSpent($select = [], $clauses = [], $row = false){
        if(count($select)>0){
            foreach($select as $value){
                $this->db->select($value);
            }
        }
        
        $this->db->from('Tasks_time_spent');
        
        if(count($clauses) > 0){
            foreach($clauses as $key => $value){
                $this->db->where($key, $value);
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
    public function AddTimeSpent($time_spent){
        return $this->db->insert('Tasks_time_spent', $time_spent);
    }
    
    public function UpdateTimeSpent($data, $clauses = [] ){
        if(count($clauses) > 0){
            foreach($clauses as $key => $value){
                $this->db->where($key, $value);
            }
        }
        return $this->db->update('Tasks_time_spent', $data);
    }
    
    public function DeleteTimeSpent($clauses = []){
        if(count($clauses) > 0){
            foreach($clauses as $key => $value){
                $this->db->where($key, $value);
            }
        }
        return $this->db->delete('Tasks_time_spent');
    }
    
    public function GetReaction($select = [], $clauses = [], $row = false){
        if(count($select)>0){
            foreach($select as $value){
                $this->db->select($value);
            }
        }
        else{
            $this->db->select('*');
        }
        
        $this->db->from('Tasks_reactions');
        
        if(count($clauses) > 0){
            foreach($clauses as $key => $value){
                $this->db->where($key, $value);
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
    
    public function AddReaction($data){
        return $this->db->insert('Tasks_reactions', $data);
    }
}
