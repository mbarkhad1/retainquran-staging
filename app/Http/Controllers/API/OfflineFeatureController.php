<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AppLog;
use App\Providers\RouteServiceProvider;
use App\Models\Card;
use App\Models\OfflineLog;
use App\Models\Card_history;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class OfflineFeatureController extends Controller
{
    protected function validator_offline(array $data)
    {
        return Validator::make($data, [
            'data_array'        =>  ['required'],
            'current_datetime'  =>  ['required']

        ]);
    }

    public function insert_offline_data(Request $request)
    {
        //DB::enableQueryLog();

        $res = $this->createRequestLog($request);
        return json_encode($res);

        // $data = $request->only('data_array', 'current_datetime');
        // $current_datetime = $data['current_datetime'];
        // $response = $this->validator_offline($data);
        // $data_array = $data['data_array'];
        // $data_array = json_decode($data_array);

        // if ($response->fails()) {
        //     $result = 'failed';
        //     $errors = $response->errors()->all();
        //     $err_str = implode(" ", $errors);
        //     $res = ['result' => $result, 'response' => $err_str];
        //     return response()->json($res, 200);
        // } else {
        //     if (is_array($data_array)) {
        //         foreach ($data_array as $da) {
        //             $card_status =  Card::where('usr_id', $da->usr_id)
        //                 ->where('surah_id', $da->surah_id)
        //                 ->where('verse_id', $da->verse_id)
        //                 ->select('card_id')->first();
        //             if (isset($card_status)) {
        //                 if (($da->card_id) != NULL) {
        //                     $card_id = $da->card_id;
        //                 } else {
        //                     $card_id = $card_status->card_id;
        //                 }
        //                 $update_query = 'UPDATE `tbl_cards` SET `due_at`="' . $da->due_at . '",`is_performed`= 1 ,`state_id`="' . $da->state_id . '",`updated_at`="' . $da->updated_at . '" WHERE `card_id` = ' . $card_id;

        //                 $update = DB::select(DB::raw($update_query));
        //                 // dd(DB::getQueryLog());

        //                 foreach ($da->history as $dh) {

        //                     $insert_history_array = [
        //                         'card_status'       => NULL,
        //                         'usr_id'            => $dh->usr_id,
        //                         'state_id'          => $dh->state_id,
        //                         'card_id'           => $card_id,
        //                         'due_date'          => $dh->due_at,
        //                         'created_at'        => $dh->created_at,
        //                         'timely_performed'  => 0
        //                     ];
        //                     $insert_history = Card_history::create($insert_history_array);
        //                 }
        //                 $result = 'success';
        //                 $message = 'Data Updated Successfully';
        //             } else {

        //                 $insert_card_array = [
        //                     'card_name'     => $da->card_name,
        //                     'due_at'        => $da->due_at,
        //                     'usr_id'        => $da->usr_id,
        //                     'state_id'      => $da->state_id,
        //                     'verse_id'      => $da->verse_id,
        //                     'surah_id'      => $da->surah_id,
        //                     'deck_id'       => $da->deck_id,
        //                     'is_performed'  => $da->is_performed,
        //                     'created_at'    => $da->created_at

        //                 ];

        //                 $insertCards = Card::insertGetId($insert_card_array);
        //                 $card_id = $insertCards;
        //                 //echo $insertCards;
        //                 if (isset($insertCards)) {
        //                     if (isset($da->history)) {
        //                         foreach ($da->history as $dh) {

        //                             $insert_history_array = [
        //                                 'card_status'       => NULL,
        //                                 'usr_id'            => $dh->usr_id,
        //                                 'state_id'          => $dh->state_id,
        //                                 'card_id'           => $card_id,
        //                                 'due_date'          => $dh->due_at,
        //                                 'created_at'        => $dh->created_at,
        //                                 'timely_performed'  => 0
        //                             ];

        //                             $insert_history = Card_history::create($insert_history_array);
        //                         }

        //                         $result = 'success';
        //                         $message = 'Cards Inserted Successfully';
        //                     } else {

        //                         $result = 'success';
        //                         $message = 'Data Inserted Successfully';
        //                     }
        //                 } else {
        //                     $res = ['result' => 'failed', 'response' => 'can not insert cards'];
        //                     return response()->json($res, 200);
        //                 }
        //             }
        //         }
        //         $res = ['result' => $result, 'response' => $message];
        //         return response()->json($res, 200);
        //     } else {
        //         $result = 'failed';
        //         $message = 'Data Must be an Array';
        //         $res = ['result' => $result, 'response' => $message];
        //         return response()->json($res, 200);
        //     }
        // }
    }

    public function createRequestLog(Request $request)
    {
        $data = $request->only('data_array', 'current_datetime');
        // $current_datetime = $data['current_datetime'];
        $current_datetime = date('Y-m-d H:i:s');
        $response = $this->validator_offline($data);
        $data_array = $data['data_array'];
        $data_array = json_decode($data_array);
        $res = array();
        $logsArray = array();

        if ($response->fails()) {
            $result = 'failed';
            $errors = $response->errors()->all();
            $err_str = implode(" ", $errors);
            $res = ['result' => $result, 'response' => $err_str];
        } else {
            if (is_array($data_array)) {
                $tmp = array();
                $tmp['current_datetime'] = $current_datetime;
                $tmp['status'] = 0;
                $tmp['created_at'] = $current_datetime;
                $tmp['updated_at'] = $current_datetime;
                foreach ($data_array as $data) {
                    $logsArray[] = $data;
                    // $logsArray[] = "('" . $tmp['request_json'] . "'+ '" . $tmp['current_datetime'] . "'+ '" . $tmp['status'] . "'+ '" . $tmp['created_at'] . "'+ '" . $tmp['updated_at'] . "')";
                    // OfflineLog::create($tmp);
                }
                $tmp['request_json'] = json_encode($logsArray);
                // $result = DB::statement("INSERT INTO offline_logs('request_json','current_datetime','status','created_at', 'updated_at') VALUES " . implode('+', $logsArray));
                OfflineLog::create($tmp);
                $result = 'sucess';
                $message = 'Data inserted sucessfully';
                $res = ['result' => $result, 'response' => $message];
            } else {
                $result = 'failed';
                $message = 'Data Must be an Array';
                $res = ['result' => $result, 'response' => $message];
            }
        }

        $response = $this->processLogRequests();
        return $res;
    }

    public function processLogRequests()
    {
        $start = microtime(true);
        $historyDataArray = array();
        $cardsDataArray = array();
        $data_array = OfflineLog::where('status', '=', 0)->get();
        OfflineLog::where(['status' => '0'])->update(['status' => 2]);
        foreach ($data_array as $data) {
            $requestArray = json_decode($data->request_json);
            $cardQuery = true;
            foreach ($requestArray as $da) {
                if ($cardQuery) {
                    $cardQuery = false;
                    $all_cards =  Card::where('usr_id', $da->usr_id)
                        // ->where('surah_id', $da->surah_id)
                        // ->where('verse_id', $da->verse_id)
                        ->select('card_id', 'surah_id', 'verse_id', 'deck_id')->get();
                }
                $card_status = $this->isCardIsInArray($all_cards, $da->surah_id, $da->verse_id, $da->deck_id);
                if ($card_status) {
                    // if (($da->card_id) != NULL) {
                    //     $card_id = $da->card_id;
                    // } else {
                    //     $card_id = $card_status->card_id;
                    // }
                    $card_id = $card_status->card_id;

                    if (!$da->due_at || !$da->updated_at) {
                        $da->due_at = date("d-m-Y H:i:s", strtotime("0000:00:00 00:00:00"));
                        $da->updated_at = date("d-m-Y H:i:s", strtotime("0000:00:00 00:00:00"));
                    }
                    $update_query = 'UPDATE `tbl_cards` SET `due_at`="' . $da->due_at . '",`is_performed`= 1 ,`state_id`="' . $da->state_id . '",`updated_at`="' . $da->updated_at . '" WHERE `card_id` = ' . $card_id;
                    $update = DB::select(DB::raw($update_query));
                    foreach ($da->history as $dh) {
                        $due_at = isset($dh->due_at) ? $dh->due_at : $dh->due_date;
                        $insert_history_array = "(null," . $dh->usr_id . ",1," . $dh->unique_id . ",'" . $due_at . "','" . $dh->created_at . "',0)";
                        $historyDataArray[] = $insert_history_array;
                    }
                } else {

                    if ($da->due_at) {
                        $insert_card_array = "('" . $da->card_name . "','" . $da->due_at . "' ," . $da->usr_id . "," . $da->state_id . "," . $da->verse_id . "," . $da->surah_id . "," . $da->deck_id . "," . $da->is_performed . ",'" . $da->created_at . "')";
                    } else {
                        $insert_card_array = "('" . $da->card_name . "',null ," . $da->usr_id . "," . $da->state_id . "," . $da->verse_id . "," . $da->surah_id . "," . $da->deck_id . "," . $da->is_performed . ",'" . $da->created_at . "')";
                    }
                    $cardsDataArray[] = $insert_card_array;
                    $card_id = 1;
                    if (isset($da->history)) {
                        foreach ($da->history as $dh) {
                            $insert_history_array = "(null," . $da->usr_id . ",1," . $dh->unique_id . ", null,'" . $dh->created_at . "',0)";
                            $historyDataArray[] = $insert_history_array;
                        }
                    }
                }
            }
        }
        if (count($historyDataArray) > 0) {
            $result = DB::statement("INSERT INTO tbl_usr_card_history(card_status,usr_id,state_id,card_id, due_date, created_at, timely_performed) VALUES " . implode(',', $historyDataArray));
        }
        if (count($cardsDataArray) > 0) {
            $result = DB::statement("INSERT INTO tbl_cards(card_name,due_at,usr_id,state_id,verse_id,surah_id,deck_id,is_performed,created_at) VALUES " . implode(',', $cardsDataArray));
        }

        $time_elapsed_secs = microtime(true) - $start;
        return $time_elapsed_secs;
    }

    public function isCardIsInArray($all_cards, $surah_id, $verse_id, $deck_id = 1)
    {
        $cards = null;
        foreach ($all_cards as $card) {
            if ($card->surah_id == $surah_id && $card->verse_id == $verse_id && $card->deck_id == $deck_id) {
                $cards = $card;
                break;
            }
        }
        return $cards;
    }
}
