<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LastView;
use App\Models\Surah;
use App\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LastViewController extends Controller
{
    protected $redirectTo = RouteServiceProvider::HOME;

    public function __construct()
    {
        $this->middleware('auth:api');
    }

    public function createLastView(Request $request)
    {
        $response = Validator::make($request->all(), [
            'title'  => 'nullable|max:255',
            'page_no' => 'required|numeric',
            'surah_number' => 'required|numeric',
            'is_juz' => 'nullable',
            'juz_number' => 'required|numeric',
            'para_id'  => ['nullable', 'exists:tbl_para,para_id'],
            'surah_id' => ['nullable', 'exists:tbl_surah,surah_id'],
            'verse_id' => ['nullable', 'exists:tbl_verses,verse_id'],

        ]);

        if ($response->fails()) {
            return response([
                'result' => "failed",
                'status_code' => 422,
                'response' => $response->errors()->all()
            ]);
        }

        $user_id = auth()->user()->id;
        $view_type = LastView::$lastViewType;
        try {
            $page_no = $request->page_no;
            $last_view = LastView::where('user_id', $user_id)
                ->where('view_type', $view_type)
                ->first();

            if ($last_view) {
                $title = $last_view->title ? $last_view->title : $request->title;
                $last_view->update([
                    'title' => $title,
                    'page_no' => $page_no,
                    'surah_number' => $request->surah_number,
                    'is_juz' => $request->is_juz,
                    'juz_number' => $request->juz_number,
                    'verse_id' => $request->verse_id
                ]);
                return response([
                    'result' => "success",
                    'status_code' => 200,
                    // 'response' => 'Last view page no update!',
                    'response' => $last_view->only('user_id', 'page_no', 'title', 'surah_number', 'is_juz', 'juz_number', 'updated_at'),
                ]);
            }

            $title = $request->title;
            $surah_id = $request->surah_id;
            if (!$title && $surah_id) {
                $surah = Surah::where('surah_id', $surah_id)->frist();
                if ($surah) {
                    $title = $surah->surah_name;
                } else {
                    $title = "Page No {$page_no}";
                }
            } else if (!$title && !$surah_id) {
                $title = "Page No {$page_no}";
            }

            $last_view = LastView::create([
                'user_id' => $user_id,
                'page_no' => $page_no,
                'title' => $title,
                'view_type' => $view_type,
                'surah_number' => $request->surah_number,
                'is_juz' => $request->is_juz,
                'juz_number' => $request->juz_number,
                'verse_id' => $request->verse_id,
            ]);

            return response([
                'result' => "success",
                'status_code' => 200,
                // 'response' => 'Last view page no created!',
                'response' => $last_view->only('user_id', 'page_no', 'title', 'surah_number', 'is_juz', 'juz_number', 'updated_at'),
            ]);
        } catch (\Exception $e) {
            return response([
                'result' => "failed",
                'status_code' => 500,
                'response' => $e->getMessage(),
            ]);
        }
    }

    public function getLastView()
    {
        try {
            $last_view = LastView::where('user_id', auth()->user()->id)
                ->where('view_type', LastView::$lastViewType)
                // ->with('surah')
                // ->with('surah:surah_id,surah_name,surah_number')
                // ->with('verse:verse_id,verse_content,verse_number')
                // ->select('id', 'user_id', 'page_no', 'title', 'updated_at')
                ->first();
            return response([
                'result' => "success",
                'status_code' => 200,
                'page_no' => $last_view ? $last_view->page_no : -1,
                'response' => $last_view,
            ]);
        } catch (\Exception $e) {
            return response([
                'result' => "failed",
                'status_code' => 500,
                'page_no' => -1,
                'response' => $e->getMessage(),
            ]);
        }
    }
}
