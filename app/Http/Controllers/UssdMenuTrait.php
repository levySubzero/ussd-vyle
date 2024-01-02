<?php
namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Support\Facades\Log;

trait UssdMenuTrait{

    public function mainMenu($ussdSession, $status=null){ // HOME
        $ussdSession->current_menu = 'HOME';
        $ussdSession->page = 1;
        $ussdSession->category_page = 1;
        $ussdSession->save();

        $start = is_null($status) ? '' :  $status . " \n";
        $start .= "Main Menu.\n";
        $start .= "1. Browse Content\n";
        $start .= "2. Check Balance\n";
        $start .= "3. Top Up Wallet\n";
        $start .= "4. Change Pin\n";
        $start .= "5. Register";
        $this->ussd_proceed($start);
    }

    public function categoryMenu($ussdSession){ // CATEGORIES
        $ussdSession->current_menu = 'CATEGORIES';
        $ussdSession->save();

        $cats = Category::available();
        $catsNum = $cats->count();
        $page = $ussdSession->category_page;
        $start = (7 * $page) - 8; //9 is the number of items on the menu
        $end = 7 * $page;
        $cats = $cats->whereBetween('id', [$start, $end])->get();
        $more = $end < $catsNum ? "n:More \n" : "";

        $categories = "Select Category.\n";
        foreach ($cats as $key => $cat) {
            $categories .= $cat->id .". " . $cat->name ."\n";
        }
        $categories .= "\n --- \n" . $more ." 00:Main";
        $this->ussd_proceed($categories);
    }

    public function subCategoriesMenu($id, $ussdSession){ // SUBCATS
        $category = Category::find($id);
        if ($category == null) {
            $ussdSession->page = 1;
            $ussdSession->category_page = 1;
            $ussdSession->save();
            return $this->mainMenu($ussdSession, "Invalid option!");
        }
        $ussdSession->current_menu = 'SUBCATS';
        $ussdSession->category_id = $id;
        $ussdSession->save();

        $catsNum = $category->subCategories()->count();
        $page = $ussdSession->page;
        $start = (7 * $page) - 8; //9 is the number of items on the menu
        $end = 7 * $page;
        $cats = $category->subCategories()->whereBetween('id', [$start, $end])->get();
        $more = $end < $catsNum ? "n:More \n" : "";
        // Log::info(json_encode($start));
        
        $subCategories  = "Select SubCategory.\n";
        foreach ($cats as $key => $cat) {
            $subCategories .= $cat->id .". " . $cat->name ." (". $cat->duration ." mins)\n";
        }
        $subCategories .= "\n --- \n" . $more ." b:Back \n 00:Main";
        return $this->ussd_proceed($subCategories);
    }

    public function newUserMenu(){
        $start  = "Login or Register to access.\n";
        $start .= "1. Register\n";
        $start .= "2. Login\n";
        $start .= "00. Home";
        $this->ussd_proceed($start);
    }

    public function returnUserMenu(){
        $con  = "Welcome back to Vnews\n";
        $con .= "1. Login\n";
        $con .= "2. Exit";
        $this->ussd_proceed($con);
    }

    public function servicesMenu(){
        $serve = "Select option?\n";
        $serve .= "1. Content categories\n";
        $serve .= "2. My account\n";
        $serve .= "3. Top up wallet\n";       
        $serve .= "4. Logout";
        $this->ussd_proceed($serve);
    }
}