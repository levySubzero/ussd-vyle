<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class UssdController extends Controller
{
    use UssdMenuTrait;
    use SmsTrait;
    use HelperFunctions;

    public function ussdRequestHandler(Request $request)
    {
        $params = $request->all();

        Log::info(json_encode($params));

        $sessionId   = $request["sessionId"];
        $serviceCode = $request["serviceCode"];
        $phone       = $request["phoneNumber"];
        $text        = $request["text"];

        // Log::info(json_encode($sessionId));

        // return $this->categoryMenu();

        $this->handleRequest($text, $phone, $sessionId);

        
        // if(User::where('phone', $phone)->exists()){
        //     // Function to handle already registered users
        //     $this->handleReturnUser($text, $phone);
        // }else {
        //      // Function to handle new users
        //      $this->handleNewUser($text, $phone);
        // }
    } 

    public function handleRequest($ussd_string, $phone, $sessionId)
	{ 
		$ussd_string_exploded = explode ("*",$ussd_string);

		// Get menu level from ussd_string reply
		$level = count($ussd_string_exploded);
        Log::info($ussd_string_exploded[0]);
		if(empty($ussd_string) or $level == 0) {
			$this->mainMenu(); // show main menu
		} else if ($ussd_string_exploded[0] == "5") {
                    
            // If user selected 1 show the categories
            return $this->handleNewUser($ussd_string, $phone);
        } 

		switch ($level) {
			case ($level == 1 && !empty($ussd_string)):
                if ($ussd_string_exploded[0] == "1") {
					// If user selected 1 show the categories
					$this->categoryMenu();
				} else {
					if(User::where('phone', $phone)->exists()){
                        if ($ussd_string_exploded[0] == "2") {
                            //2 = show user wallet balance
                            $this->ussd_proceed("Enter your PIN to view account balance");
                        } else if ($ussd_string_exploded[0] == "3") {
                            //3 = top up user wallet balance
                            $this->ussd_proceed("Enter your PIN to top up account");
                        } else if ($ussd_string_exploded[0] == "4") {
                            // pin update
                            $this->ussd_proceed("Enter your old PIN");
                        }
                    } else {
                        // Function to handle new users
                        $this->ussd_stop("Register to access this section!");
                    }
				}
			break;
			case 2:
                if ($ussd_string_exploded[0] == "1") {
                    $this->subCategoriesMenu($ussd_string_exploded[1]);
                } else {
                    if ($this->ussdLogin($ussd_string_exploded[1], $phone) == "Success") {
                        if ($ussd_string_exploded[0] == "2") {
                            //2 = show user wallet balance
                            //Logic to obtain users account balance 


                            return $this->ussd_stop("Your account balance is KES 384.");
                        } else if ($ussd_string_exploded[0] == "3") {
                            //3 = top up user wallet balance
                            $this->ussd_proceed("Enter top up amount");
                        } else if ($ussd_string_exploded[0] == "4") {
                            // pin update
                            $this->ussd_proceed("Enter your new PIN");
                        }
                    }
                }
			break;
			case 3:
				if ($ussd_string_exploded[0] == "3") {
                    //3 = top up user wallet balance
                    $this->ussd_proceed("Enter top up amount");
                } else if ($ussd_string_exploded[0] == "4") {
                    // pin update
                    $this->ussd_proceed("Confirm your new PIN");
                }
			break;
            case 4:
				if ($ussd_string_exploded[0] == "3") {
                    //3 = top up user wallet balance
                    $this->ussd_proceed("Enter top up amount");
                } else if ($ussd_string_exploded[0] == "4") {
                    // pin update

                    if ($ussd_string_exploded[2] == $ussd_string_exploded[3]) {
                        $user = User::where('phone', $phone)->first();
                        $user->pin = $ussd_string_exploded[3];
                        $user->save();
                        $this->ussd_stop("PIN changed successfully.");
                    } else {
                        $this->ussd_stop("PIN does not match.");
                    }
                }
			break;
		}
	}

    public function handleNewUser($ussd_string, $phone)
    {
        $ussd_string_exploded = explode ("*",$ussd_string);

        // Get menu level from ussd_string reply
        $level = count($ussd_string_exploded);

        // if(empty($ussd_string) or $level == 0) {
        //     $this->newUserMenu(); // show the home menu
        // }

        switch ($level) {
            case ($level == 1 && !empty($ussd_string)):
                $this->ussd_proceed("Please enter your full name and desired PIN separated by commas. \n eg: Jane Doe,1234");
                // if ($ussd_string_exploded[0] == "1") {
                //     // If user selected 1 send them to the registration menu
                //     $this->ussd_proceed("Please enter your full name and desired PIN separated by commas. \n eg: Jane Doe,1234");
                // } else if ($ussd_string_exploded[0] == "2") {
                //     //If user selected 2, send them the information
                //     $this->ussd_stop("You will receive more information on Vnews via sms shortly.");
                //     // $this->sendText("This is a subscription service from Vnews.",$phone);
                // } else if ($ussd_string_exploded[0] == "3") {
                //     //If user selected 3, exit
                //     $this->ussd_stop("Thank you for reaching out to Vnews.");
                // }

            break;
            case 2:
                if ($this->ussdRegister($ussd_string_exploded[1], $phone) == "success") {
                    $this->mainMenu();
                }
            break;
            // N/B: There are no more cases handled as the following requests will be handled by return user
        }
    }

    public function ussd_proceed($ussd_text) {
        echo "CON $ussd_text";
    }

    public function ussd_stop($ussd_text) {
        echo "END $ussd_text";
    }

    /*
     * Handles USSD Registration Request
    */
    // public function ussdRegister($details, $phone)
    // {
    //     $input = explode(",",$details);//store input values in an array
    //     $full_name = $input[0];//store full name
    //     $pin = $input[1];        
       
    //     $user = new User;
    //     $user->name = $full_name;
    //     $user->phone = $phone;
    //     // You should encrypt the pin
    //     $user->pin = $pin;
    //     $user->save();
 
    //     return "success";
    // }
 
    // /**
    //  * Handles Login Request
    //  */
    // public function ussLogin($details, $phone)
    // {
    //     $user = User::where('phone', $phone)->first();

    //     if ($user->pin == $details ) {
    //         return "Success";           
    //     } else {
    //         return $this->ussd_stop("Login was unsuccessful!");
    //     }
    // }


}
    


