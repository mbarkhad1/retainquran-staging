<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
class CheckActiveUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }
    
    protected function check_user(Request $request)
    {
        return response(['result' => 'success']);
    }
}
