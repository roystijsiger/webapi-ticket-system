<?php
class MY_Controller extends CI_Controller{
    public $token;
    public $json;
    private $userGroups;
    private $userPermissions;
    
    public function __construct() {
        parent::__construct();
        
        //load in all models
        $this->loadModels();
        
        //get the php://input so that we know what data has been posted
        $this->getPhpInput();
        
        //see if there is a token and renew it if its used
        $this->renewToken();
        
        
        //lets check if logged in
        if(isset($this->json->Token)){
            // get user by token
            $user = $this->Users_model->GetUserByToken($this->json->Token);
            //if logged in
            if($user){
                //set user groups
                $this->setUserGroups($user);
                $this->setPermissions($user);
            }
        }
        
        $this->checkPermissions();
    }
    
    
    public function ShowOutput($status = 404, $data = ['Error'=> ['Message' => 'Not found']]){
        http_response_code($status);
        header('Content-Type: application/json');

        if(is_object($data) || is_array($data)){
            die(json_encode($data));
        }
        else{
            $response = [
                'Error' => [
                    'Message' => 'Couldnt encode the json string',
                    'Type' => json_last_error()
                 ]
            ];

            die(json_encode($response));
        }
    }
    
    public function ValidateRequiredFields($required_fields = [], $show_response_on_fail = true){
        //set empty array to fill with missing required
        $missing_fields = [];
        foreach($required_fields as $rf){
            if(!isset($this->json->$rf)){
                $missing_fields[] = $rf;
            }
        }
        if(count($missing_fields)> 0){
            if($show_response_on_fail){
                $response = [
                  'Error' => [
                      'Message' => 'Missing one or more required fields',
                      'Missing fields' => $missing_fields
                  ]  
                ];
                $this->ShowOutput(400, $response);
            }
            else{
                return false;
            }
        }
        return true;
    }
    
    public function GetPermissions($permission_name){
        foreach($this->userPermissions as $permission){
            if($permission->Action == $permission_name){
                return true;
            }
        }
        return false;
        
    }
    
    public function GetGroups(){
        return $this->userGroups;
    }
    
    private function getPhpInput(){
        //get the raw input that has been send
        $raw_php_input = file_get_contents("php://input");
        
        //decode the value we have received
        $this->json = json_decode($raw_php_input);
        
        //if the decoded json === null and the error is not JSON_ERROR_NONE that means it wasnt able to decode the value
        if ($this->json === null && json_last_error() !== JSON_ERROR_NONE) {
            //show an error if its not okay
            $response = [
                'Error' => [
                    'Message' => 'Couldnt decode the json string',
                    'Type' => json_last_error()
                 ]
            ];
            
            $this->ShowOutput('400', $response);
        }
    }
    
    private function renewToken() : bool{
        if(isset($this->json->Token)){
            //get user by token
            $user = $this->Users_model->GetUsersTokens(['UT.Token', 'UT.Expiration_date'], ['UT.Token' => $this->json->Token]);
            if($user){
                //set date now and then renew your token end_date
                $date_now = date('Y-m-d H:i:s');
                if($user->Expiration_date >= $date_now){
                    return $this->Users_model->UpdateUsersTokens(['Expiration_date' => date('Y-m-d H:i:s', strtotime("$date_now + 30 minutes"))], ['Token' => $this->json->Token]);
                }
                else{
                    $this->ShowOutput(400, $response = ['Error' => ['Message' => 'Rip you cant use this token anymore. You have to relog.']]);
                }
            }
            else{
                $this->ShowOutput(400, $response = ['Error' => ['Message' => 'Rip you cant use this token anymore. You have to relog.']]);
            }
            return false;
        }
        return false;
    }
    
    private function loadModels(){
        $this->load->model('Users_model');
        $this->load->model('Permissions_model');
        $this->load->model('Task_model');
    }
    
    private function setUserGroups($user){
        //get all group s from the given user
        $user_groups = $this->Users_model->GetGroupsByUserId($user->Id);
        //create and empty arary
        $user_group_array = [];
        //create a simpler array from it for easy future use
        foreach($user_groups as $group){
            $user_group_array[] = $group->Id;
        }
        
        $this->userGroups = $user_group_array;
    }
    
    private function setPermissions($user){
        //see if permissions are needed
        $user_permissions = $this->Permissions_model->GetUsersPermissions($select = ['Id', 'Action'], $clauses= ['Up.Users_id' => $user->Id]);
        $group_permissions = $this->Permissions_model->GetGroupsPermissions($select = ['Id', 'Action'], $clauses = [], false, $where_in = ['Ugp.Users_groups_id' => $this->userGroups]);
        $this->userPermissions = (object) array_merge((array)$group_permissions, (array)$user_permissions);
    }
    
    private function checkPermissions(){
        //set the action based on controller + method
        $action = $this->uri->segment(1) . "/" . $this->uri->segment(2);
            
        //see if permissions are needed
        $permission_found = $this->Permissions_model->GetPermissionAction($select = ['Id', 'Action'], $clauses= ['Action' => $action]);
        if($permission_found){
            if(!$this->userPermissions){
                $this->ShowOutput($status = 401, $error = ['Error' => ['Message' => "Not authorized"]]);    
            }
            else{
                foreach($this->userPermissions as $permission){
                    if($permission->Action == $action){
                        return true;
                    }
                }
            }
            
        }
        //no permission needed
        return true;
    }
    
    /*
     * outdated
     */
    private function permissionsCheck(){
        if(isset($this->json->Token)){
            //first get the user by Token
            $user = $this->Users_model->GetUserByToken($this->json->Token);
            
            //get the action which is segment1(controller) + segment2(action);
            $action = $this->uri->segment(1) . "/" . $this->uri->segment(2);
            
            //see if permissions are needed
            $permissions = $this->Permissions_model->GetPermissionAction($select = ['Id', 'Action'], $clauses= ['Action' => $action]);
            
            //if no permission is found there is no permission needed
            if(!$permissions){
                return true;
            }
            //if no user is found the token wasnt valid
            elseif(!$user){
                $this->ShowOutput(401, ['Error' => ['Message' => "Nobody found with a token and login is necesarry cause this action is in the permission table."]]);
            }
            else{
                $user_permissions = $this->Permissions_model->GetUserPermissions($select = ['Upa.Action'], $clauses=['Up.Users_id' => $user->Id, 'Upa.Action' => $action], true);
                $group_permissions = $this->Permissions_model->GetGroupPermissions();
                if($permissions || $group_permissions){
                    return true;
                }
                $this->ShowOutput(401, ['Error' => ['Message' => "Unauthorized"]]);
            }
        }
        else{
            return true;
        }
        //array of select values
        $select = ['Id'];
        
        //array of clauses we want to use in our where construction
        $clauses = [
            'Action' => $this->uri->segment(1) . '/' . $this->uri->segment(2)
        ];
        
        //we expect one row
        $permission = $this->Permissions_model->GetPermissions($select, $clauses);
    }
}