<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Users extends MY_Controller{
	public function Login() : void
	{
            // define two arrays for required fields
            $required_normal_login = $this->ValidateRequiredFields(['Email','Password'], false);
            $required_token_login = $this->ValidateRequiredFields(['Token'], false);
            
            //set a vairable with time to sue later 
            $date_now = date('Y-m-d H:i:s');
            
            //if the required fields aren't here for a normal or token login this function is useless.
            if(!$required_normal_login && !$required_token_login){
                $this->ShowOutput(400, ['Error' => [ 'Message' => "Missing required fields(Email & Password or Token"]]);
            }
            //this is what to do when its a token login
            elseif(!$required_normal_login && $required_token_login){
                //GetUser by token
                $user = $this->Users_model->GetUserByToken($this->json->Token);
                
                if(!$user){
                    $this->ShowOutput(404, $response = ['Error' => ['Message' => 'No user with this token found.']]);
                }
                
                //remove all tokens that are not the current token
                $this->Users_model->DeleteUsersTokens(['Email' => $user->Email, 'Token !=' => $this->json->Token]);
                
                //create a token based on date + email and apassword
                $token = $this->json->Token;
            }
            //this is what to do when its a normal login
            elseif($required_normal_login && !$required_token_login){
                //set clauses to get users
                $clauses = [
                    'Email' => $this->json->Email
                ];
                
                //find user with email password combo
                $user = $this->Users_model->GetUsers(['Id, Password, Email, First_name, Middle_name, Last_name'], $clauses, $row = true);
                if(!$user){
                    $this->ShowOutput(404, $response = ['Error' => ['Message' => 'No user with this username and password combination found.']]);
                }
                
                //see if the password are the same.. 
                $divide_password = explode('.',$user->Password);
                
                //if its not exploded in 3 that would mean you are having a non valid password you cant have less than 3 after an explode.
                if(count($divide_password) !== 3){
                     $this->ShowOutput(401, $response = ['Error' => ['Message' => 'No valid password for this user']]);
                }
                //starting point is the first character
                $starting_point = $divide_password[0];
                
                //the password itself with the hidden random string.
                $password_remaining = $divide_password[1];
                
                //this is the length of the random string hidden in the password
                $length = $divide_password[2];
                
                //beginning of the password till the starting point
                $password_beginning = substr($password_remaining, 0, $starting_point);
                
                //password_ending is the starting point + the length and the remainder of the string.
                $password_ending = substr($password_remaining, $starting_point + $length, strlen($password_remaining) - $length); 
                
                //combine password
                $password = "$password_beginning$password_ending";
                
                //see if the password are equal
                if($password !== hash('sha256', $this->json->Password . $this->json->Email)){
                     $this->ShowOutput(404, $response = ['Error' => ['Message' => 'No user with this credentials found.']]);
                }
                
                //create a token based on date + email and apassword
                $token = hash('sha256', date('YmdHis') . "-$user->Id-$user->Last_name");
                
                //check if this user already has a log in.
                $UserToken = $this->Users_model->GetUsersTokens(['UT.Token', 'UT.Email'], ['UT.Email' => $this->json->Email]);
                
                //if a usertoken is found remove it cause this type of login is clean!
                if($UserToken){
                    $this->Users_model->DeleteUsersTokens(['Email' => $UserToken->Email]);
                }
                
                //insert the token in the database to reverse look up the user.
                $this->Users_model->AddToken(['Token' => $token, 'Email' => $this->json->Email, 'Expiration_date' => date('Y-m-d H:i:s', strtotime("$date_now + 30 minutes"))]);
            }
            //You cant do a token and normal login at the same time
            else{
                $this->ShowOutput(400, "You cant send a token and username/password. Those are 2 login types at the same time");
            }
          
            //return token and user
            $this->ShowOutput('200', [
                'Error' => null,
                'Message' => "Success",
                'Response_data' => [
                    'Token' => $token,
                    'User' => $user
                ],
            ]);
	}
        
        public function Logout(){
            //check required fields
            $required_fields = ['Token'];
            $this->ValidateRequiredFields($required_fields);
            
            if($this->Users_model->DeleteUsersTokens(['Token' => $this->json->Token])){
                $this->showOutput(200, ['Error' => [ 'Message' => "Logout successfull"]]);
            }
            else{
                $this->showOutput(200, ['Error' => ['Error' => null, 'Message' => "Logout failed"]]);   
            }
        }
        
        public function GetAll(){
            $users = $this->Users_model->GetUsers([],[],false);
            
            if(!$users){
                  $this->ShowOutput(404, "No users found");
            }
            
            $this->ShowOutput(200, ['users' => $users]);
        }
}
/* this is for creating a password..
 * //get a string length for our salt.
                $string_length = rand(1, 30);
                
                //empty string to fill with a random character or number
                $random_string = "";
                //remaining char startpoint
                $remaining = $string_length;
                while($remaining > 0){
                    //random int 0 or 1 to find out if we want string or number
                    if(rand(0, 1) == 1){
                         $random_string .= rand(0, 9);
                    }
                    else{
                        $random_string .= chr(rand(97,122));
                    }
                    $remaining--;
                }
                
                //get a location to put the random string
                $random_string_location = rand(0,64);
                
                //simple, hash
                $simple_hash = hash('sha256',$this->json->Password . $this->json->Email);
                
                //break it down in parts
                $start = substr($simple_hash, 0, $random_string_location);
                $end = substr($simple_hash, $random_string_location, 64 - strlen($start));
                
                //full password
                $password = $start . $random_string . $end;
                
                //paste location of random string and length behind
                $password = "$random_string_location.$password.$string_length";
 */