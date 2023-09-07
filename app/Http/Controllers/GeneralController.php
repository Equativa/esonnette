<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Validator;
use Tymon\JWTAuth\Facades\JWTAuth;

use App\Models\User;
use App\Models\Role;

class GeneralController extends Controller
{
    //
    
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct() {
        $this->middleware('auth:api');
    }

    /**
     * Show Dashboard Data
     *
     * @return \Illuminate\Http\JsonResponse
    */

    public function index() {

        try {
            //code...

            $utilisateur = User::count();
            $role = Role::count();

            return response()->json([
                "status" => true,
                "data" => [
                    "utilisateur" => $utilisateur,
                    "role" => $role
                ]
            ]);

        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                "status" => false,
                "message" => $th
            ]);
        }

    }

}