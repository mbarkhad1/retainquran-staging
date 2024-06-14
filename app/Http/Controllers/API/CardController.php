<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Models\Card;
use App\Models\Card_history;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Arr;

class CardController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth:api');
    }

    public function view_cards(Request $request)
    {
        $data = $request->only('usr_id');
        $date = Carbon::now()->toDateTimeString();

        $response = $this->validator_view($data);

        if ($response->fails()) {
            $result = 'failed';
            $errors = $response->errors()->all();
            $err_str = implode(" ", $errors);
            $res = ['result' => $result, 'response' => $err_str];
            return response()->json($res, 200);
        } else {
            $cards = DB::table('tbl_cards')
                ->where('usr_id', '=', $data['usr_id'])
                ->count('card_id');
            if ($cards) {
                $users = DB::table('tbl_cards')
                    ->select(DB::raw("surah_name,tbl_cards.surah_id,surah_number, SUM(CASE WHEN due_at IS NULL THEN 1 ELSE 0 END) As new_cards, 
                SUM(CASE WHEN (due_at) <= '" . $date . "' THEN 1 ELSE 0 END) AS pending_cards ,due_at, created_at"))
                    ->where('usr_id', '=', $data['usr_id'])
                    ->groupBy('tbl_cards.surah_id')
                    ->join('tbl_surah', 'tbl_surah.surah_id', '=', 'tbl_cards.surah_id')
                    ->get()->toArray();
            } else {
                $users = 0;
            }



            $res = ['result' => 'success', 'response' => $users];
            return response()->json($res, 200);
        }
    }

    public function deck_view(Request $request)
    {
        $data = $request->only('usr_id', 'deck_id');
        $response = $this->validator_view($data);
        $date = Carbon::now()->toDateTimeString();

        if ($response->fails()) {
            $result = 'failed';
            $errors = $response->errors()->all();
            $err_str = implode(" ", $errors);
            $res = ['result' => $result, 'response' => $err_str];
            return response()->json($res, 200);
        } else {
            $usr_id = $data['usr_id'];
            $cards = DB::table('tbl_cards')
                ->select(DB::raw("Count(card_id) as Total, SUM(CASE WHEN due_at IS NULL THEN 1 ELSE 0 END) As new_cards, 
                SUM(CASE WHEN (due_at) <= '" . $date . "' THEN 1 ELSE 0 END) AS pending_cards"))
                ->where('usr_id', '=', $data['usr_id'])
                ->join('tbl_surah', 'tbl_surah.surah_id', '=', 'tbl_cards.surah_id')
                ->get()->toArray();
            $res = ['result' => 'success', 'response' => $cards];
            return response()->json($res, 200);
        }
    }

    public function review_all_cards(Request $request)
    {
        $time_start = microtime(true);
        $data = $request->only('usr_id');
        $response = $this->validator_view($data);
        $responseCards = array();

        if ($response->fails()) {
            $result = 'failed';
            $errors = $response->errors()->all();
            $err_str = implode(" ", $errors);
            $res = ['result' => $result, 'response' => $err_str];
            return response()->json($res, 200);
        } else {
            $usr_id = $data['usr_id'];
            $time_start_query = microtime(true);
            $cards = DB::table('tbl_cards')
                ->select('*')
                ->where('usr_id', '=', $usr_id)
                ->get()->toArray();
            $response = array();

            foreach ($cards as $card) {

                if ($card->due_at == null) {
                    $card->status = 'New';
                } else {
                    $card->status = 'Due';
                }
                if ($card->due_at == "2030-11-01 00:00:00") {
                    $card->due_at = NULL;
                }
                $response[] = $card;
            }
            $time_end_query = microtime(true);
            // foreach ($cards as $card) {
            //     if ($card->due_at == "2030-11-01 00:00:00") {
            //         $card->due_at = NULL;
            //     }
            //     $responseCards[] = $card;
            // }
            $time_end = microtime(true);
            $execution_time = ($time_end - $time_start);
            $queryTime = ($time_end_query - $time_start_query);
            $res = ['result' => 'success', 'response' => $response, 'executionTime' => $execution_time, 'queryTime' => $queryTime];
            return response()->json($res, 200);
        }
    }

    public function get_cards_with_surah(Request $request)
    {
        //DB::enableQueryLog();
        $data = $request->only('usr_id', 'surah_id');
        $response = $this->validator_getSurahCards($data);


        if ($response->fails()) {
            $result = 'failed';
            $errors = $response->errors()->all();
            $err_str = implode(" ", $errors);
            $res = ['result' => $result, 'response' => $err_str];
            return response()->json($res, 200);
        } else {
            $usr_id     = $data['usr_id'];
            $surah_id   = $data['surah_id'];

            $where = [
                ['usr_id', '=', $usr_id],
                ['surah_id', '=', $surah_id],
            ];
            $cards = Card::select(DB::raw("tbl_cards.*,(CASE WHEN due_at IS NULL THEN 'New' ELSE 'Due' END) As status"))
                ->where($where)->where(function ($query) {
                    $date = Carbon::now()->toDateTimeString();
                    $query->whereNull('due_at')
                        ->orWhere('due_at', '<=', $date);
                })->inRandomOrder()->get();

            //dd(DB::getQueryLog());
            $res = ['result' => 'success', 'response' => $cards];
            return response()->json($res, 200);
        }
    }

    public function get_cards_with_deck(Request $request)
    {
        //DB::enableQueryLog();
        $data = $request->only('usr_id', 'deck_id');
        $response = $this->validator_getDeckCards($data);

        if ($response->fails()) {
            $result = 'failed';
            $errors = $response->errors()->all();
            $err_str = implode(" ", $errors);
            $res = ['result' => $result, 'response' => $err_str];
            return response()->json($res, 200);
        } else {
            $usr_id     = $data['usr_id'];
            $deck_id   = $data['deck_id'];

            $where = [
                ['usr_id', '=', $usr_id],
                ['deck_id', '=', $deck_id],
            ];
            $cards = Card::select(DB::raw("tbl_cards.*,(CASE WHEN due_at IS NULL THEN 'New' ELSE 'Due' END) As status"))
                ->where($where)->where(function ($query) {
                    $date = Carbon::now()->toDateTimeString();
                    $query->whereNull('due_at')
                        ->orWhere('due_at', '<=', $date);
                })->inRandomOrder()->get();

            //dd(DB::getQueryLog());
            $res = ['result' => 'success', 'response' => $cards];
            return response()->json($res, 200);
        }
    }

    /**
     * Get a validator for an incoming Card Creation request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'card_name' => ['required', 'string', 'max:255'],
            'usr_id'    => ['required'],
            'state_id'  => ['required'],
            'verse_id'  => ['required'],
            'deck_id'   => ['required']
        ]);
    }

    protected function validator_update(array $data)
    {
        return Validator::make($data, [
            'usr_id'    =>   ['required'],
            'state_id'  =>   ['required'],
            'condition' =>   ['required'],
            'card_id'   =>   ['required'],

        ]);
    }

    protected function validator_view(array $data)
    {
        return Validator::make($data, [
            'usr_id'    =>   ['required'],
            'deck_id'    =>   ['required'],
        ]);
    }


    protected function validator_getSurahCards(array $data)
    {
        return Validator::make($data, [
            'usr_id'      =>   ['required'],
            'surah_id'    =>   ['required'],
            'deck_id'     =>   ['required'],
        ]);
    }

    protected function validator_getDeckCards(array $data)
    {
        return Validator::make($data, [
            'usr_id'     =>   ['required'],
            'deck_id'    =>   ['required']
        ]);
    }

    /**
     * Create a new card instance after a validation.
     *
     * @param  array  $data
     * @return \App\Models\Card
     */
    protected function create(array $data)
    {
        return Card::create([
            'card_name'     => $data['card_name'],
            'due_at'        => $data['due_at'],
            'usr_id'        => $data['usr_id'],
            'state_id'      => $data['state_id'],
            'verse_id'      => $data['verse_id'],
            'surah_id'      => $data['surah_id'],
            'deck_id'       => $data['deck_id'],
            'is_performed'  => 0,
            'created_at'    => $data['created_at']

        ]);
    }

    protected function create_history(array $data)
    {
        return Card_history::create([
            'card_status'       => NULL,
            'usr_id'            => $data['usr_id'],
            'state_id'          => $data['state_id'],
            'card_id'           => $data['card_id'],
            'timely_performed'  => $data['timely_performed'],
            'due_date'          => $data['due_date'],



        ]);
    }

    protected function update(array $data)
    {
        return Card::where("card_id", $data['card_id'])->update([
            'due_at'        => $data['due_at'],
            'state_id'      => $data['state_id'],
            'updated_at'    => $data['updated_at'],
            'is_performed'  => 1

        ]);
    }

    public function create_cards(Request $request)
    {
        $data = $request->only('card_name', 'usr_id', 'state_id', 'verse_id', 'deck_id');

        $response = $this->validator($data);

        if ($response->fails()) {
            $result = 'failed';
            $errors = $response->errors()->all();
            $err_str = implode(" ", $errors);
            $res = ['result' => $result, 'response' => $err_str];
            return response()->json($res, 200);
        } else {
            $mytime = Carbon::now();
            $data['created_at'] = $mytime->toDateTimeString(); //2022-05-19 10:24:55
            $data['due_at']     = $mytime->toDateTimeString(); //2022-05-19 10:24:55
            $data['verse_id']   = json_decode($data['verse_id']);
            $verses             = $data['verse_id'];

            foreach ($verses as $key => $verse_id) {

                $data['surah_id'] = $key;
                foreach ($verse_id as $verse) {

                    $data['verse_id'] = $verse;
                    $card = $this->create($data);
                }
            }
            $result = 'success';
            $res = ['result' => $result, 'response' => $card];
            return response()->json($res, 200);
        }
    }

    public function update_card(Request $request)
    {
        $data = $request->only('card_id', 'state_id', 'condition', 'usr_id');

        $response = $this->validator_update($data);

        if ($response->fails()) {
            $result = 'failed';
            $errors = $response->errors()->all();
            $err_str = implode(" ", $errors);
            $res = ['result' => $result, 'response' => $err_str];
            return response()->json($res, 200);
        } else {
            $due_date  = Card::find($data['card_id']);
            $data['due_date'] = $due_date->due_at;
            $condition = $data['condition'];
            $mytime = Carbon::now();
            $data['updated_at'] = $mytime->toDateTimeString(); //2022-05-19 10:24:55
            $due = $mytime->toDateString();
            $now = Carbon::today();
            if ($due == $now) {
                $data['timely_performed'] = 1;
            } else {
                $data['timely_performed'] = 0;
            }
            if ($data['state_id'] == 1) {
                if ($condition == 'Poor') {
                    $data['due_at']        = $mytime->toDateTimeString();
                    $data['state_id'] = 1;
                } elseif ($condition == 'Fair') {
                    $data['due_at']    = $mytime->addDay(1)->toDateTimeString();
                    $data['state_id'] = 2;
                } elseif ($condition == 'Good') {
                    $data['due_at']    = $mytime->addDay(2)->toDateTimeString();
                    $data['state_id'] = 2;
                }
            } elseif ($data['state_id'] == 2) {
                if ($condition == 'Poor') {
                    $data['due_at']        = $mytime->toDateTimeString();
                    $data['state_id'] = 1;
                } elseif ($condition == 'Fair') {
                    $data['due_at']    = $mytime->addDay(2)->toDateTimeString();
                    $data['state_id'] = 3;
                } elseif ($condition == 'Good') {
                    $data['due_at']    = $mytime->addDay(4)->toDateTimeString();
                    $data['state_id'] = 3;
                }
            } elseif ($data['state_id'] == 3) {
                if ($condition == 'Poor') {
                    $data['due_at']        = $mytime->toDateTimeString();
                    $data['state_id'] = 1;
                } elseif ($condition == 'Fair') {
                    $data['due_at']    = $mytime->addDay(4)->toDateTimeString();
                    $data['state_id'] = 4;
                } elseif ($condition == 'Good') {
                    $data['due_at']    = $mytime->addDay(8)->toDateTimeString();
                    $data['state_id'] = 4;
                }
            } elseif ($data['state_id'] == 4) {
                if ($condition == 'Poor') {
                    $data['due_at']        = $mytime->toDateTimeString();
                    $data['state_id'] = 1;
                } elseif ($condition == 'Fair') {
                    $data['due_at']    = $mytime->addDay(8)->toDateTimeString();
                    $data['state_id'] = 5;
                } elseif ($condition == 'Good') {
                    $data['due_at']    = $mytime->addDay(16)->toDateTimeString();
                    $data['state_id'] = 5;
                }
            } elseif ($data['state_id'] == 5) {
                if ($condition == 'Poor') {
                    $data['due_at']        = $mytime->toDateTimeString();
                    $data['state_id'] = 1;
                } elseif ($condition == 'Fair') {
                    $data['due_at']    = $mytime->addDay(16)->toDateTimeString();
                    $data['state_id'] = 6;
                } elseif ($condition == 'Good') {
                    $data['due_at']    = $mytime->addDay(30)->toDateTimeString();
                    $data['state_id'] = 6;
                }
            } elseif ($data['state_id'] == 6) {
                if ($condition == 'Poor') {
                    $data['due_at']        = $mytime->toDateTimeString();
                    $data['state_id'] = 1;
                } elseif ($condition == 'Fair') {
                    $data['due_at']    = $mytime->addDay(30)->toDateTimeString();
                    $data['state_id'] = 6;
                } elseif ($condition == 'Good') {
                    $data['due_at']    = $mytime->addDay(60)->toDateTimeString();
                    $data['state_id'] = 6;
                }
            }
            unset($data['condition']);

            $card = $this->update($data);
            $card_history = $this->create_history($data);
            $result = 'success';
            $res = ['result' => $result, 'response' => $card];
            return response()->json($res, 200);
        }
    }


    public function get_all_history(Request $request)
    {
        $time_start = microtime(true);
        $data = $request->all();
        $response = Validator::make($data, [
            'usr_id' => ['required'],
            'created_at' => ['required'],
            'limit' => ['required']
        ]);
        $usr_id = $data['usr_id'];
        $dateTime = $data['created_at'];
        $limit = $data['limit'];
        $responseCards = array();



        if ($response->fails()) {
            $result = 'failed';
            $errors = $response->errors()->all();
            $err_str = implode(" ", $errors);
            $res = ['result' => $result, 'response' => $err_str];
            return response()->json($res, 200);
        } else {
            $time_start_query = microtime(true);
            $cardCount = count(DB::table('tbl_usr_card_history')
                ->select('*')
                ->where('usr_id', '=', $usr_id)
                ->get());
            if ($dateTime == 'null') {

                $Cards_history = DB::table('tbl_usr_card_history')
                    ->select('card_id', 'state_id', 'usr_id', 'due_date', 'created_at')
                    ->where('usr_id', '=', $usr_id)
                    ->orderBy('created_at', 'asc')
                    ->orderBy('uch_id', 'asc')
                    ->limit($limit)
                    ->get();
                $time_end_query = microtime(true);
            } else {
                $Cards_history = DB::table('tbl_usr_card_history')
                    ->select('card_id', 'state_id', 'usr_id', 'due_date', 'created_at')
                    ->where('usr_id', '=', $usr_id)
                    ->where('created_at', '>', $dateTime)
                    ->orderBy('created_at', 'asc')
                    ->orderBy('uch_id', 'asc')
                    ->limit($limit)
                    ->get();
                $time_end_query = microtime(true);
            }

            $time_end = microtime(true);
            $execution_time = ($time_end - $time_start);
            $queryTime = ($time_end_query - $time_start_query);
            if ($Cards_history) {
                $res = ['result' => 'success', 'response' => $Cards_history, 'historyCount' => $cardCount, 'executionTime' => $execution_time, 'queryTime' => $queryTime];
                return response()->json($res, 200);
            } else {
                $res = ['result' => 'failed', 'response' => "No Cards in Table"];
                return response()->json($res, 200);
            }
        }
    }

    public function TestGetAllHistory()
    {
        // $data = $request->only('usr_id');
        $usr_id = 38;
        $limit = 100;
        $dateTime = "2022-09-01 00:00:00";
        $date = Carbon::now()->toDateTimeString();

        // $cards = Card::select(DB::raw("tbl_cards.*,(CASE WHEN due_at IS NULL THEN 'New' ELSE 'Due' END) As status"))
        //     ->where('usr_id', $usr_id)->inRandomOrder()->tosql();

        $cards = DB::table('tbl_usr_card_history')
            ->select('*')
            ->where('usr_id', '=', $usr_id)
            ->where('created_at', '>', $dateTime)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()->toArray();
        // $response = array();

        // foreach ($cards as $card) {

        //     if ($card->due_at == null) {
        //         $card->status = 'New';
        //     } else {
        //         $card->status = 'Due';
        //     }
        //     $response[] = $card;
        // }

        print_r($cards);
    }

    public function delete_all_cards(Request $request)
    {
        $data = $request->only('usr_id', 'deck_id');
        $usr_id = $data['usr_id'];
        $deck_id = request('deck_id');

        $response = $this->validator_view($data);

        if ($response->fails()) {
            $result = 'failed';
            $errors = $response->errors()->all();
            $err_str = implode(" ", $errors);
            $res = ['result' => $result, 'response' => $err_str];
            return response()->json($res, 200);
        }

        $delCards = Card::where('usr_id', '=', $usr_id)->where('deck_id', $deck_id)->delete();
        if (!$delCards) {
            $res = ['result' => 'failed', 'response' => "Cards Cannot be Deleted."];
            return response()->json($res, 200);
        }
        $res = ['result' => 'success', 'response' => "All Cards Deleted Successfully."];
        return response()->json($res, 200);
    }

    public function delete_surah_cards(Request $request)
    {
        $data = $request->only('usr_id', 'surah_id', 'deck_id');
        $response = $this->validator_getSurahCards($data);
        $usr_id = $data['usr_id'];
        $surah_id = $data['surah_id'];
        $deck_id = $data['deck_id'];

        if ($response->fails()) {
            $result = 'failed';
            $errors = $response->errors()->all();
            $err_str = implode(" ", $errors);
            $res = ['result' => $result, 'response' => $err_str];
            return response()->json($res, 200);
        }
        $delCards = Card::where('usr_id', '=', $usr_id)->where('deck_id', $deck_id)->where('surah_id', '=', $surah_id)->delete();
        if (!$delCards) {
            $res = ['result' => 'failed', 'response' => "Cards Cannot be Deleted."];
            return response()->json($res, 200);
        }
        $res = ['result' => 'success', 'response' => "All Cards Deleted Successfully."];
        return response()->json($res, 200);
    }

    public function delete_user_cards(Request $request)
    {
        $data = $request->only('usr_id');
        $usr_id = $data['usr_id'];


        $delCards = Card::where('usr_id', '=', $usr_id)->delete();
        if ($delCards) {
            $res = ['result' => 'success', 'response' => "All Cards Deleted Successfully."];
            return response()->json($res, 200);
        } else {
            $res = ['result' => 'failed', 'response' => "Cards Cannot be Deleted."];
            return response()->json($res, 200);
        }
    }


    public function delete_user_history(Request $request)
    {
        $data = $request->only('usr_id');
        $usr_id = $data['usr_id'];


        $delCards = Card_history::where('usr_id', '=', $usr_id)->delete();
        if ($delCards) {
            $res = ['result' => 'success', 'response' => "All History Deleted Successfully."];
            return response()->json($res, 200);
        } else {
            $res = ['result' => 'failed', 'response' => "History Cannot be Deleted."];
            return response()->json($res, 200);
        }
    }

    public function DeleteUser(Request $request)
    {
        $data = $request->all();
        $responseArray = Validator::make($data, [
            'user_id' => ['required']
        ]);

        if (!$responseArray->fails()) {
            $delCards = Card_history::where('usr_id', '=', $data['user_id'])->delete();
            $delCards = Card::where('usr_id', '=', $data['user_id'])->delete();
            $delCards = User::where('id', '=', $data['user_id'])->delete();

            $responseArray = ['result' => 'success', 'response' => "All History Deleted Successfully."];
        } else {
            $responseArray = array('code' => '202', 'Message' => $responseArray->errors()->all());
        }
        return response()->json($responseArray, 200);
    }
}
