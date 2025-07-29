<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\Language;
use App\Models\Tafseer;
use Illuminate\Http\Request;


class LanguageController extends Controller
{
    public function getAllLangauges()
    {
        $all = Language::all();
        if (isset($all)) {
            $response['response'] = $all;
            $response['result'] = 'success';
            $response['message'] = 'updated message testing updated';
            return response()->json($response);
        } else {
            $response['response'] = "There is no record in Table";
            $response['result'] = 'failed';
            return response()->json($response);
        }
    }

    public function getTafseers()
    {
        try {
            $tafseers = Tafseer::all();
            return response([
                'success' => true,
                'data' => $tafseers,
                'message' => null,
            ]);
        } catch (\Exception $e) {
            logger()->error($e);
            return response([
                'success' => false,
                'data' => [],
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function testingRoute()
    {
        $response['result'] = 'success';
        $response['message'] = 'updated message testing';
        return response()->json($response);
    }
}
