<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\UssdSession as Session;
use App\Models\Category;
use App\Models\SubCategory;
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
        $ussdSession = Session::where('phone', $phone)->first();
        $currentMenu = $ussdSession->current_menu;
		$ussd_string_exploded = explode ("*",$ussd_string);

		// Get menu level from ussd_string reply
		$level = count($ussd_string_exploded);
        Log::info($ussd_string_exploded[0]);

        //nav
        if (in_array(end($ussd_string_exploded), ['0','00','98', 'b', 'n'])) {
            return $this->navigation(end($ussd_string_exploded), $ussdSession);
        }
        

		if(empty($ussd_string)) {
			return $this->mainMenu($ussdSession); // show main menu
		} else if ($level == 1 || $currentMenu == 'HOME') {
            return $this->homeHandler(end($ussd_string_exploded), $ussdSession);
        } else if (in_array($currentMenu, ['CATEGORIES', 'SUBCATS', 'CONTENTCONFIRM'])) {
            return $this->categoryHandler(end($ussd_string_exploded), $ussdSession);
        } else if ($currentMenu == 'REGISTER') {
             return $this->handleNewUser(end($ussd_string_exploded), $ussdSession);
        } else if ($currentMenu == 'BALANCE' || 'TOPUP') {
            return $this->walletHandler(end($ussd_string_exploded), $currentMenu, $ussdSession);
        }

	}

    public function handleNewUser($ussd_string, $ussdSession)
    {
        if ($this->ussdRegister($ussd_string, $ussdSession->phone) == "success") {
            return $this->mainMenu($ussdSession, 'Account Created');
        } else {
            return $this->mainMenu($ussdSession, 'Retry');
        }

        // $ussd_string_exploded = explode ("*",$ussd_string);

        // // Get menu level from ussd_string reply
        // $level = count($ussd_string_exploded);

        // if(empty($ussd_string) or $level == 0) {
        //     $this->newUserMenu(); // show the home menu
        // }

        // switch ($level) {
        //     case ($level == 1 && !empty($ussd_string)):
        //         $this->ussd_proceed("Please enter your full name and desired PIN separated by commas. \n eg: Jane Doe,1234");
        //         // if ($ussd_string_exploded[0] == "1") {
        //         //     // If user selected 1 send them to the registration menu
        //         //     $this->ussd_proceed("Please enter your full name and desired PIN separated by commas. \n eg: Jane Doe,1234");
        //         // } else if ($ussd_string_exploded[0] == "2") {
        //         //     //If user selected 2, send them the information
        //         //     $this->ussd_stop("You will receive more information on Vnews via sms shortly.");
        //         //     // $this->sendText("This is a subscription service from Vnews.",$phone);
        //         // } else if ($ussd_string_exploded[0] == "3") {
        //         //     //If user selected 3, exit
        //         //     $this->ussd_stop("Thank you for reaching out to Vnews.");
        //         // }

        //     break;
        //     case 2:
        //         if ($this->ussdRegister($ussd_string_exploded[1], $phone) == "success") {
        //             $this->mainMenu();
        //         } else {
        //             $this->mainMenu();
        //         }
        //     break;
        //     // N/B: There are no more cases handled as the following requests will be handled by return user
        // }
    }

    public function navigation($option, $ussdSession) {
        if($option == "00") {
            $ussdSession->page = 1;
            $ussdSession->category_page = 1;
            $ussdSession->save();
			return $this->mainMenu($ussdSession); // show main menu            
		} else if ($option == "b") {
            $ussdSession->page = 1;
            $ussdSession->category_page = 1;
            $ussdSession->save();
            if($ussdSession->current_menu == 'SUBCATS') {
                return $this->categoryMenu($ussdSession);                
            }
        } else if ($option == "n") {
            if($ussdSession->current_menu == 'SUBCATS') {
                $ussdSession->page = $ussdSession->page + 1;
                $ussdSession->save();
                return $this->subCategoriesMenu($ussdSession->category_id, $ussdSession);
            } else if($ussdSession->current_menu == 'CATEGORIES') {
                $ussdSession->category_page = $ussdSession->category_page + 1;
                $ussdSession->save();
                return $this->categoryMenu($ussdSession);                
            }
        }
    }

    public function categoryHandler($option, $ussdSession) {
        if ($ussdSession->current_menu == 'CATEGORIES') {
            return $this->subCategoriesMenu($option, $ussdSession);
        } else if ($ussdSession->current_menu == 'SUBCATS'){
            $content = SubCategory::find($option);
            if ($content == null) {
                $ussdSession->page = 1;
                $ussdSession->save();
                return $this->mainMenu($ussdSession, "Invalid option!");
            }
            $this->updateSession($ussdSession, 'CONTENTCONFIRM');
            return $this->ussd_proceed("Confirm selection. \n". $content->name." \n --- \n 1:Confirm \n 0:Back \n 00:Main");
        } else {
            if ($option == '1') {
                return $this->ussd_stop("Wallet Confirmation");
            }
        }
    }

    public function homeHandler($option, $ussdSession) {
        if ($option == "5") {
            if (User::where('phone', $ussdSession->phone)->exists()){
                return $this->mainMenu($ussdSession, 'Account Exists');
            } else {
                $this->updateSession($ussdSession, 'REGISTER');
                // $ussdSession->current_menu = 'REGISTER';
                // $ussdSession->save();
                return $this->ussd_proceed("Please enter your full name and desired PIN separated by commas. \n eg: Jane Doe,1234 \n --- \n 00:Main");
            }
        } else if ($option == "1") {
            return $this->categoryMenu($ussdSession);
        } else if (in_array($option, ['2','3','4'])) {
            if(User::where('phone', $ussdSession->phone)->exists()){
                if ($option == "2") {
                    //2 = show user wallet balance
                    $this->updateSession($ussdSession, 'BALANCE');
                    return $this->ussd_proceed("Enter your PIN to view account balance \n --- \n 00:Main");
                } else if ($option == "3") {
                    //3 = top up user wallet balance
                    $this->updateSession($ussdSession, 'TOPUP');
                    return $this->ussd_proceed("Enter your PIN to top up account");
                } else if ($option == "4") {
                    // pin update
                    $this->ussd_proceed("Enter your old PIN");
                }
            } else {
                // Function to handle new users
                $this->ussd_proceed("Register to access this section! \n --- \n 00:Main");
            }
        } else {
            return $this->mainMenu($ussdSession, "Invalid option!");
        }
    }

    public function walletHandler($option, $currentMenu, $ussdSession) {
        if ($this->ussdLogin($option, $ussdSession->phone) == "Success") {
            if ($currentMenu == 'BALANCE') {
                $balance = $this->getWalletBalance($ussdSession->phone);
                return $this->ussd_proceed("Your account balance is KES " . $balance .". \n --- \n 00:Main");
            } else {
                return $this->ussd_stop("STK push");
            }

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
    


