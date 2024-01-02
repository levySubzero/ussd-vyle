<?php

namespace App\Http\Middleware;

use Illuminate\Session\Middleware\StartSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\UssdSession as Session;

class UssdSession 
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, $next)
    {
        $sessionId   = $request["sessionId"];
        $phone       = $request["phoneNumber"];

        $ussdSession = Session::where('phone', $phone)->exists();
        
        if($ussdSession) {
            $ussdSession = Session::where('phone', $phone)->first();
            if($ussdSession->session_id != $sessionId) {
                $ussdSession->session_id = $sessionId;
                $ussdSession->page = 1;
                $ussdSession->category_page = 1;
                $ussdSession->save();
            }
        } else {
            $uSession = new Session();
            $uSession->phone = $phone;
            $uSession->session_id = $sessionId;
            $uSession->page = 1;
            $uSession->category_page = 1;
            $uSession->save();
        }

        return $next($request);
    }

}
