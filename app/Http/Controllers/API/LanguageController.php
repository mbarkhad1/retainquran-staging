<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\Language;
use Illuminate\Http\Request;


class LanguageController extends Controller
{
    public function getAllLangauges()
    {

        $all = Language::all();
        if(isset($all))
        {
            $response['response'] = $all;
            $response['result'] = 'success';
            return response()->json($response);

        }
        else
        {
            $response['response'] = "There is no record in Table";
            $response['result'] = 'failed';
            return response()->json($response);
        }
        
    }
}
