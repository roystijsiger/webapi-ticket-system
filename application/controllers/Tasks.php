<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Tickets
 *
 * @author royst
 */
class Tasks extends MY_Controller{
   
    
    /*
     * AddCategory
     */
    public function AddCategory(){
        $required_fields = ["Description"];
        $this->ValidateRequiredFields($required_fields);
        
        $category = new StdClass();
        $category->Description = $this->json->Description;
        
        if($this->Task_model->AddCategory($category)){
            return $this->ShowOutput(200, ['Category_added' => $category]);
        }
        else{
            return $this->ShowOutput(400, ['Error' => ['Message' => "Category couldn't be added"]]);
        }
    }
    
    public function DeleteCategory(){
        $required_fields = ["Category_id"];
        $this->ValidateRequiredFields($required_fields);
        
        $category_id = $this->json->Category_id;
        if($this->Task_model->UpdateCategory(['Deleted' => 1],['Id' => $category_id])){
            return $this->ShowOutput(200, ['Message' => "Category $category_id deleted."]);
        }
        else{
            return $this->ShowOutput(400, ['Error' => ['Message' => "Category $category_id could not be deleted."]]);
        }
    }
    
    public function RestoreCategory(){
        $required_fields = ["Category_id"];
        $this->ValidateRequiredFields($required_fields);
        
        $category_id = $this->json->Category_id;
        if($this->Task_model->UpdateCategory(['Deleted' => 0],['Id' => $category_id])){
            return $this->ShowOutput(200, ['Message' => "Category $category_id restored."]);
        }
        else{
            return $this->ShowOutput(400, ['Error' => ['Message' => "Category $category_id could not be restored."]]);
        }
    }
    
    public function  GetAllCategories(){
        $required_fields = ["Deleted"];
        $this->ValidateRequiredFields($required_fields);
        
        $task_categories = $this->Task_model->GetCategories(['Id', 'Description'], ['Deleted' => $this->json->Deleted], false);
        if($task_categories){
            return $this->ShowOutput(200, ['Task_categories' => $task_categories]);
        }
        else{
            return $this->ShowOutput(404, ['Error' => ['Message' => "No categories found."]]);
        }
    }
    
    //put your code here
    public function Add(){
        $required_fields = ["Description","Category","User_id"];
        $this->ValidateRequiredFields($required_fields, true);
        $user_token = $this->Users_model->GetUsersTokens(['UT.Token', 'UT.Email','U.Id'], ['UT.Token' => $this->json->Token]);
        
        $task = new StdClass();
        $task->Parent_id = isset($this->json->Parent_id) ? $this->json->Parent_id  : null;
        $task->Tasks_categories_id = $this->json->Category;
        $task->Description = $this->json->Description;
        $task->Create_date = date("Y-m-d H:i:s");
        $task->Modified_date = date("Y-m-d H:i:s");
        //$task->Users_id = $user_token->Id;
        $task->Users_id = $this->json->User_id;
        $task->Completed = false;
        
        if($this->Task_model->AddTask($task)){
            return $this->showOutput(200, ['Task_added' => $task]);
        }
        else{
            return $this->ShowOutput(400, ['Error' => ['Message' => "Task couldn't be added"]]);
        }
    }
    
    public function Edit(){
          $required_fields = ["Description","Category","User_id", "Task_id"];
        $this->ValidateRequiredFields($required_fields, true);
        //$user_token = $this->Users_model->GetUsersTokens(['UT.Token', 'UT.Email','U.Id'], ['UT.Token' => $this->json->Token]);
        
        $task = new StdClass();
        $task->Parent_id = isset($this->json->Parent_id) ? $this->json->Parent_id  : null;
        $task->Tasks_categories_id = $this->json->Category;
        $task->Description = $this->json->Description;
        $task->Create_date = date("Y-m-d H:i:s");
        $task->Modified_date = date("Y-m-d H:i:s");
        //$task->Users_id = $user_token->Id;
        $task->Users_id = $this->json->User_id;
        $task->Completed = false;
        
        
        if($this->Task_model->EditTask($task,['Id' => $this->json->Task_id])){
            return $this->showOutput(200, ['Task_edited' => $task]);
        }
        else{
            return $this->ShowOutput(400, ['Error' => ['Message' => "Task couldn't be edited"]]);
        }
    }
   
    public function GetMy(){
        //get user by token
        $user_token = $this->Users_model->GetUsersTokens(['UT.Token', 'UT.Email','U.Id'], ['UT.Token' => $this->json->Token]);
        
        //get the user
        $user  = $this->Users_model->GetUsers(['Id'], ['Email' => $user_token->Email], true);
        
        //get the task itself
        $select = ['Id','Tasks_categories_id','Parent_id', 'Users_id', 'Description', 'Create_date', 'Modified_date', 'completed'];
        
        $clauses['Users_id'] = $user->Id;
        $clauses['Completed'] = 0;
        $open_tasks = $this->Task_model->GetTask($select, $clauses, false);
        $clauses['Completed'] = 1;
        $completed_tasks = $this->Task_model->GetTask($select, $clauses, false);
        
        return $this->ShowOutput(200, ['Open_tasks' => $open_tasks, 'Closed_tasks' => $completed_tasks]);
    }
    
    public function GetByCategory(){
        //$required_fields 
        $required_fields = ["Category_id"];
        $this->ValidateRequiredFields($required_fields);
        
        //get user by token
        $user_token = $this->Users_model->GetUsersTokens(['UT.Token', 'UT.Email','U.Id'], ['UT.Token' => $this->json->Token]);
        
        //get the task itself
        $select = ['Id','Tasks_categories_id','Parent_id', 'Users_id', 'Description', 'Create_date', 'Modified_date', 'Completed'];
        
        //task_id is necesarry for both
        $clauses['Tasks_categories_id'] = $this->json->Category_id;
        $clauses['Parent_id'] = NULL;
        
        $task = $this->Task_model->GetTask($select, $clauses, false);
        if(!$task){
            return $this->ShowOutput(404, ['Task' => "Not found"]);
        }       
        
        //get permissions to category by json category_id
        $hasGroupPermissions = $this->Permissions_model->GetTaskCategoriesPermissions($this->GetGroups(), $this->json->Category_id);
        
        if(!$hasGroupPermissions){
            return $this->ShowOutput(401, ['Error' => ['Message' => "Not authorized to see this task"]]);
        }
        
        return $this->ShowOutput(200, ['Tasks' => $task]);
    }
    
    public function Get(){
        //$required_fields 
        $required_fields = ["Task_id"];
        $this->ValidateRequiredFields($required_fields);
        
        //get user by token
        $user_token = $this->Users_model->GetUsersTokens(['UT.Token', 'UT.Email','U.Id'], ['UT.Token' => $this->json->Token]);
        
        //get the task itself
        $select = ['Id','Tasks_categories_id','Parent_id', 'Users_id', 'Description', 'Create_date', 'Modified_date', 'completed'];
        
        //task_id is necesarry for both
        $clauses['Id'] = $this->json->Task_id;
        
        $task = $this->Task_model->GetTask($select, $clauses, true);
        if(!$task){
            return $this->ShowOutput(404, ['Task' => "Not found"]);
        }       
        
        //get permissions to category by groups
        $hasGroupPermissions = $this->Permissions_model->GetTaskCategoriesPermissions($this->GetGroups(), $task->Tasks_categories_id);
        
        if(!$hasGroupPermissions && !$this->checkIfOwnerOfTaskOrParent($task->Id, $user_token->Id)){
            return $this->ShowOutput(401, ['Error' => ['Message' => "Not authorized to see this task"]]);
        }
        
        return $this->ShowOutput(200, ['Task' => $task]);
    }
    
    /*
     * GetReactions
     */
    public function GetReactions(){
         $required_fields = ["Task_id"];
        $this->ValidateRequiredFields($required_fields);
        
        $reactions = $this->Task_model->GetReaction([],['Tasks_id' => $this->json->Task_id], false);
        
        $this->ShowOutput(200, ['Reactions' => $reactions]);
    }
    
    /*
     * add reaction
     */
    public function AddReaction(){
        $required_fields = ["Message", "Task_id"];
        $this->ValidateRequiredFields($required_fields);
        
         //get user by token
        $user_token = $this->Users_model->GetUsersTokens(['UT.Token', 'UT.Email','U.Id'], ['UT.Token' => $this->json->Token]);
        
        //get the user by token
        $user  = $this->Users_model->GetUsers(['Id'], ['Email' => $user_token->Email], true);
        
        $reaction = new stdClass();
        $reaction->Tasks_id = $this->json->Task_id;
        $reaction->Users_id = $user->Id;
        $reaction->Message = $this->json->Message;
        $reaction->Create_date = date("Y-m-d H:i:s");
        $reaction->Modified_date = date("Y-m-d H:i:s");
        $reaction->Modified_date_users_id = date("Y-m-d H:i:s");
        
        $add = $this->Task_model->AddReaction($reaction);
        
        if(!$add){
            $this->ShowOutput(400, ["Error" => ["Message" => "Couldn't add an reaction to the task."]]);
        }
        
        $this->showOutput(200, ["Reaction_added" => $reaction]);
    }
    
     /*
     * AddTimeSpent
     */
    public function AddTimeSpent(){
        $required_fields = ["Time_spent_minutes", "Task_id"];
        $this->ValidateRequiredFields($required_fields);
        
         //get user by token
        $user_token = $this->Users_model->GetUsersTokens(['UT.Token', 'UT.Email','U.Id'], ['UT.Token' => $this->json->Token]);
        
        $time_spent = new stdClass();
        $time_spent->Users_id = (int) $user_token->Id;
        $time_spent->Tasks_id = $this->json->Task_id;
        $time_spent->Time_spent = $this->json->Time_spent_minutes * 60;
        $time_spent->Date_created = date("Y-m-d H:i:s");
        $time_spent->Date_modified = date("Y-m-d H:i:s");
        $time_spent->Date_modified_users_id = (int) $user_token->Id;
        
        if($this->Task_model->AddTimeSpent($time_spent)){
            return $this->ShowOutput(200, ['Time_spent_added' => $time_spent]);
        }
        else{
            return $this->ShowOutput(400, ['Error' => ['Message' => "Time spent couldn't be added"]]);
        }
    }
    
    public function GetTimeSpent(){
        $required_fields = ["Task_id"];
        
        $time_spent = $this->Task_model->GetTimeSpent(['Id,Users_id,Tasks_id,Time_spent,Date_modified,Date_modified_users_id'], ['Tasks_id' => $this->json->Task_id], false);
         
        return $this->ShowOutput(200, ['Time_spent' => $time_spent]);
    }
    
    public function UpdateTimeSpent(){
        //check required fields
        $required_fields = ["Time_spent_id","Time_spent_minutes"];
        $this->ValidateRequiredFields($required_fields);
        
        
        //first get the row we are gonna update
        $time_spent = $this->Task_model->GetTimeSpent(['Id,Users_id,Tasks_id,Time_spent,Date_modified,Date_modified_users_id'], ['Id' => $this->json->Time_spent_id], true);
        
        //get the category of the task itself
        $select = ['Tasks_categories_id'];
        
        //task_id is necesarry for both
        $clauses['Id'] = $time_spent->Tasks_id;
        
        $task = $this->Task_model->GetTask($select, $clauses, true);
        if(!$task){
            return $this->ShowOutput(404, ['Error' => ["Task not found"]]);
        }
        
        //get user by token and check rights
        $user_token = $this->Users_model->GetUsersTokens(['UT.Token', 'UT.Email','U.Id'], ['UT.Token' => $this->json->Token]);
        $hasGroupPermissions = $this->Permissions_model->GetTaskCategoriesPermissions($this->GetGroups(), $task->Tasks_categories_id);
        if(!$hasGroupPermissions && !$this->checkIfOwnerOfTaskOrParent($task->Id, $user_token->Id)){
            return $this->ShowOutput(401, ['Error' => ['Message' => "Not authorized to see this task"]]);
        }
        
        
        //values to change
        $time_spent->Time_spent = $this->json->Time_spent_minutes * 60;
        $time_spent->Date_modified = date("Y-m-d H:i:s");
        $time_spent->Date_modified_users_id = $user_token->Id;
                
        if($this->Task_model->UpdateTimeSpent($time_spent,['Id' => $this->json->Time_spent_id])){
            return $this->ShowOutput(200, ['Time_spent_updated' => $time_spent]);
        }
        else{
            return $this->ShowOutput(400, ['Error' => ['Message' => "Couldn't update the time spent."]]);
        }
    }
    
    public function DeleteTimeSpent(){
        //validate required fields
        $required_fields = ['Time_spent_id'];
        $this->ValidateRequiredFields($required_fields);
        
        //get time spetn
        $time_spent = $this->Task_model->GetTimeSpent(['Tasks_id'], ['Id' => $this->json->Time_spent_id], true);
        
        //get user by token and check rights
        $user_token = $this->Users_model->GetUsersTokens(['UT.Token', 'UT.Email','U.Id'], ['UT.Token' => $this->json->Token]);
        
        //get the category of the task itself
        $select = ['Tasks_categories_id'];
        
        //task_id is necesarry for both
        $clauses['Id'] = $time_spent->Tasks_id;
        
        $task = $this->Task_model->GetTask($select, $clauses, true);
        if(!$task){
            return $this->ShowOutput(404, ['Error' => ["Task not found"]]);
        }
        
        $hasGroupPermissions = $this->Permissions_model->GetTaskCategoriesPermissions($this->GetGroups(), $task->Tasks_categories_id);
        if(!$hasGroupPermissions && !$this->checkIfOwnerOfTaskOrParent($task->Id, $user_token->Id)){
            return $this->ShowOutput(401, ['Error' => ['Message' => "Not authorized to see this task"]]);
        }
        
        if($this->Task_model->DeleteTimeSpent(['Id'=>$this->json->Time_spent_id])){
            return $this->ShowOutput(200, ['Message' => "Successfully deleted time spent."]);
        }
        else{
            return $this->ShowOutput(400, ['Error' => ['Message' => "Couldn't delete the time spent."]]);
        }
    }        

    
    private function checkIfOwnerOfTaskOrParent($task_id,$person_id){
        $task = $this->Task_model->GetTask(["Id","Parent_id","Users_id"],["Id"=>$task_id], true);
        if($task){
            if($task->Users_id == $person_id){
                return true;
            }
            else{
                if($task->Parent_id){
                    $this->checkIfOwnerOfTaskOrParent($task->Parent_id, $person_id);
                }
                return false;
            }    
        }
        return false;
    }
}
