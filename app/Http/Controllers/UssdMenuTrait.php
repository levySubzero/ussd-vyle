<?php
namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Support\Facades\Log;

trait UssdMenuTrait{

    public function mainMenu(){
        $start  = "Main Menu.\n";
        $start .= "1. Browse Content\n";
        $start .= "2. Check Balance\n";
        $start .= "3. Top Up Wallet\n";
        $start .= "4. Change Pin\n";
        $start .= "5. Register";
        $this->ussd_proceed($start);
    }

    public function categoryMenu(){
        $categories  = "Select Category.\n";
        $cats = Category::available()->get();
        foreach ($cats as $key => $cat) {
            $categories .= $cat->id .". " . $cat->name ."\n";
        }
        $this->ussd_proceed($categories);
    }

    public function subCategoriesMenu($id){
        $category = Category::find($id);
        $cats = $category->subCategories()->get();
        $subCategories  = "Select SubCategory.\n";
        Log::info(json_encode($cats));
        foreach ($cats as $key => $cat) {
            $subCategories .= $cat->id .". " . $cat->name ." (". $cat->duration ." mins)\n";
        }
        $this->ussd_proceed($subCategories);
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