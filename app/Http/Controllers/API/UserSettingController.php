<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\User_setting;
use App\Models\Translator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api');
    }

    protected function validator_reciter(array $data)
    {
        return Validator::make($data, [
            'usr_id'        =>   ['required'],
            'reciter_id'    =>   ['required']

        ]);
    }
    protected function validator_view(array $data)
    {
        return Validator::make($data, [
            'usr_id'        =>   ['required'],
            'rtype_id'    =>   ['required']

        ]);
    }
    protected function validator_notify(array $data)
    {
        return Validator::make($data, [
            'usr_id'              =>   ['required'],
            'is_notify'           =>   ['required'],
            'notification_time'   =>   ['required']

        ]);
    }
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'usr_id'        =>   ['required']
        ]);
    }
    protected function validator_translator(array $data)
    {
        return Validator::make($data, [
            'usr_id'        =>   ['required'],
            'trans_id'    =>   ['required']

        ]);
    }
    public function get_settings(Request $request)
    {
        $data = $request->only('usr_id');
        $response = $this->validator($data);

        if ($response->fails()) {
            $result = 'failed';
            $errors = $response->errors()->all();
            $err_str = implode(" ", $errors);
            $res = ['result' => $result, 'response' => $err_str];
            return response()->json($res, 200);
        } else {
            $settings = User_setting::where('usr_id', $data['usr_id'])
                ->join('tbl_reciters', 'tbl_reciters.reciter_id', '=', 'tbl_user_settings.reciter_id')
                ->select('tbl_user_settings.*', 'tbl_reciters.rec_dir_name')->get();


            if ($settings) {
                if (count($settings) > 0) {
                    $res = ['result' => 'success', 'response' => $settings];
                } else {
                    $settings = [
                        'usr_id' => $data['usr_id'],
                        'reciter_id' => 18,
                        'trans_id' => 1,
                        'rtype_id' => 1,
                        'translation_id' => 1,
                        'language_id' => 1,
                        'is_notify' => 1,
                        'notification_time' => '08:00:00'

                    ];

                    User_setting::create($settings);
                    $settings = User_setting::where('usr_id', $data['usr_id'])
                        ->join('tbl_reciters', 'tbl_reciters.reciter_id', '=', 'tbl_user_settings.reciter_id')
                        ->select('tbl_user_settings.*', 'tbl_reciters.rec_dir_name')->get();
                    $res = ['result' => 'success', 'response' => $settings];
                }
            } else {
                $res = ['result' => 'failed', 'response' => $settings];
            }
            return response()->json($res, 200);
        }
    }

    public function update_default_reciter(Request $request)
    {
        $data = $request->only('usr_id', 'reciter_id');
        $response = $this->validator_reciter($data);

        if ($response->fails()) {
            $result = 'failed';
            $errors = $response->errors()->all();
            $err_str = implode(" ", $errors);
            $res = ['result' => $result, 'response' => $err_str];
            return response()->json($res, 200);
        } else {

            $update = User_setting::where('usr_id', $data['usr_id'])
                ->update(['reciter_id' => $data['reciter_id']]);

            if ($update) {
                $res = ['result' => 'success', 'response' => 'Reciter Updated Successfully'];
            } else {
                $res = ['result' => 'failed', 'response' => 'Reciter Cannot be Updated'];
            }
            return response()->json($res, 200);
        }
    }
    public function update_default_view(Request $request)
    {
        $data = $request->only('usr_id', 'rtype_id');
        $response = $this->validator_view($data);

        if ($response->fails()) {
            $result = 'failed';
            $errors = $response->errors()->all();
            $err_str = implode(" ", $errors);
            $res = ['result' => $result, 'response' => $err_str];
            return response()->json($res, 200);
        } else {

            $update = User_setting::where('usr_id', $data['usr_id'])
                ->update(['rtype_id' => $data['rtype_id']]);

            if ($update) {
                $res = ['result' => 'success', 'response' => 'View Updated Successfully'];
            } else {
                $res = ['result' => 'failed', 'response' => 'View Cannot be Updated'];
            }
            return response()->json($res, 200);
        }
    }

    public function update_default_notification(Request $request)
    {
        $data = $request->only('usr_id', 'is_notify', 'notification_time');
        $response = $this->validator_notify($data);

        if ($response->fails()) {
            $result = 'failed';
            $errors = $response->errors()->all();
            $err_str = implode(" ", $errors);
            $res = ['result' => $result, 'response' => $err_str];
            return response()->json($res, 200);
        } else {

            $update = User_setting::where('usr_id', $data['usr_id'])
                ->update(['is_notify' => $data['is_notify'], 'notification_time' => $data['notification_time']]);

            if ($update) {
                $res = ['result' => 'success', 'response' => 'Settings Updated Successfully'];
            } else {
                $res = ['result' => 'failed', 'response' => 'Settings Cannot be Updated'];
            }
            return response()->json($res, 200);
        }
    }

    public function update_default_translator(Request $request)
    {
        $data = $request->only('usr_id', 'trans_id');
        $response = $this->validator_translator($data);

        if ($response->fails()) {
            $result = 'failed';
            $errors = $response->errors()->all();
            $err_str = implode(" ", $errors);
            $res = ['result' => $result, 'response' => $err_str];
            return response()->json($res, 200);
        } else {
            $lang_trans = Translator::join('tbl_translations', 'tbl_translations.trans_id', '=', 'tbl_translators.trans_id')
                ->select('tbl_translators.language_id', 'tbl_translations.translation_id')
                ->where('tbl_translators.trans_id', $data['trans_id'])
                ->get();
            $language_id = $lang_trans[0]->language_id;
            $translation_id = $lang_trans[0]->translation_id;
            $update = User_setting::where('usr_id', $data['usr_id'])
                ->update([
                    'trans_id'          => $data['trans_id'],
                    'language_id'       => $language_id,
                    'translation_id'    => $translation_id
                ]);

            if ($update) {
                $res = ['result' => 'success', 'response' => 'Translator Updated Successfully'];
            } else {
                $res = ['result' => 'failed', 'response' => 'Translator Cannot be Updated'];
            }
            return response()->json($res, 200);
        }
    }

    public function save_mushaf_id()
    {
        $data = request()->all();
        $validData = Validator::make($data, [
            'usr_id'    => ['required'],
            'mushaf_id' => ['required'],
        ]);

        if ($validData->fails()) {
            $result = 'failed';
            $errors = $validData->errors()->all();
            $err_str = implode(" ", $errors);
            $response = ['result' => $result, 'response' => $err_str];
            return response()->json($response, 200);
        }

        $update = User_setting::where('usr_id', $data['usr_id'])
            ->update(['mushaf_id' => $data['mushaf_id']]);

        $response = ['result' => 'success', 'response' => 'Mushaf ID Updated'];
        return response()->json($response, 200);
    }

    public function saveTafseerSetting()
    {
        $validator = Validator::make(request()->all(), [
            'tafseer_id' => ['required']
        ]);

        if ($validator->fails()) {
            $result = 'failed';
            $errors = $validator->errors()->all();
            $err_str = implode(" ", $errors);
            return response([
                'result' => $result,
                'response' => $err_str
            ]);
        }

        $userId = request('usr_id');
        $userId = auth()->user()->id;
        $tafseer_id = request('tafseer_id');
        try {

            $userSetting = User_setting::where('usr_id', $userId)->first();
            if (!$userSetting) {
                throw new \Exception('Settings not found');
            }
            $userSetting->update([
                'tafseer_id' => $tafseer_id,
            ]);

            return response([
                'success' => true,
                'data' => $userSetting,
                'message' => null,
            ]);
        } catch (\Exception $e) {
            logger()->error($e);
            return response([
                'success' => false,
                'data' => null,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
