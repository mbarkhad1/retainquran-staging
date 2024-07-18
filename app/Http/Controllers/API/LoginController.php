<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AppLog;
use App\Providers\RouteServiceProvider;
use Google_Client;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Models\User_setting;
use Validator;
use Socialite;
use Illuminate\Support\Facades\DB;
use Firebase\JWT\JWT;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */


    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }
    protected function usr_settings(array $data)
    {
        return User_setting::create($data);
    }

    public function login(Request $request)
    {

        $validattion = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validattion->fails()) {
            $errors = $validattion->errors()->all();
            $err_str = implode(" ", $errors);
            $response['response'] = $err_str;
            $response['result'] = "failed";
            return response()->json($response, 200);
        }

        $credentials = $request->only('email', 'password');
        try {
            if (Auth::attempt($credentials)) {

                // return redirect()->intended();


                $login = User::where('email', $credentials['email'])->update(['is_login'  => 1]);
                $user = Auth::User();
                DB::table('oauth_access_tokens')->where('user_id', $user->id)->delete();
                $response = [];
                $response['token'] = $user->createToken('api-application')->accessToken;
                $response['response'] = $user;
                $response['result'] = "success";
                //delete the previous tokens

                return response()->json($response, 200);
            } else {
                $response = [];
                $response['response'] = "error, Opps! You have entered invalid credentials";
                $response['result'] = "failed";
                return response()->json($response, 200);
            }
        } catch (\Exception $e) {
            logger($e);
            $response = [];
            $response['response'] = $e->getMessage();
            $response['result'] = "failed";
            return response()->json($response, 200);
        }





        // return redirect('login')->with('error', 'Opps! You have entered invalid credentials');
    }

    /**
     * Social Login
     */
    public function socialLogin(Request $request)
    {

        $validattion = Validator::make($request->all(), [
            'idToken' => 'required',
            'provider' => 'required',
        ]);


        if ($validattion->fails()) {
            try {
                $errors = $validattion->errors()->all();
                $err_str = implode(" ", $errors);
                $response['response'] = $err_str;
                $response['result'] = "failed";
                // return response()->json($response, 200);
                throw new \Exception(json_encode($response), 500);
            } catch (\Exception $e) {
                AppLog::create([
                    'message' => $e->getMessage(),
                    'line_no' => $e->getLine(),
                    'code' => $e->getCode(),
                    'log' => json_encode($e),
                    'ip' => $request->getClientIp(),
                ]);
                return [
                    'message' => json_decode($e->getMessage()),
                    'line_no' => $e->getLine(),
                    'code' => $e->getCode(),
                    'exception' => json_encode($e),
                ];
            }
        } else {

            $provider = $request['provider'];
            $token = $request['idToken'];

            if ($provider == 'google') {
                try {
                    $driver = Socialite::driver($provider); //->stateless()->user();

                    // $client = new Google_Client();
                    // $client->setClientId(env('GOOGLE_CLIENT_ID'));
                    // $client->setClientSecret(env('GOOGLE_CLIENT_SECRET'));

                    $client = new Google_Client(['client_id' => "698659440308-6prb0tkmeb5o277ae669981omev5bkge.apps.googleusercontent.com",]);  // Specify the CLIENT_ID of the app that accesses the backend
                    $payload = $client->verifyIdToken($token);
                } catch (\Exception $e) {
                    AppLog::create([
                        'message' => $e->getMessage(),
                        'line_no' => $e->getLine(),
                        'code' => $e->getCode(),
                        'log' => $e,
                        'ip' => $request->getClientIp(),
                    ]);
                    return [
                        'message' => $e->getMessage(),
                        'line_no' => $e->getLine(),
                        'code' => $e->getCode(),
                        'exception' => json_encode($e),
                    ];
                }
            } elseif ($provider == "apple") {
                $token = explode('.', $token);
                $payload = base64_decode($token[1]);
                $payload = json_decode($payload, true);
                $payload['name'] = $request['name'];
            } else {
                $payload = Socialite::driver($provider)->userFromToken($token);
            }



            if (!$payload || !is_array($payload)) {
                $response = array(
                    'result' => 'failed',
                    'message' => 'please provide the valid token'
                );
            }
            if ($provider == 'google') {
                try {
                    $provider_id = isset($payload['sub']) ? $payload['sub'] : null;
                    $email = isset($payload['email']) ? $payload['email'] : null;
                    $name = isset($payload['name']) ? $payload['name'] : null;
                } catch (\Exception $e) {
                    AppLog::create([
                        'message' => $e->getMessage(),
                        'line_no' => $e->getLine(),
                        'code' => $e->getCode(),
                        'log' => json_encode($e),
                        'ip' => $request->getClientIp(),
                    ]);
                    return [
                        'message' => $e->getMessage(),
                        'line_no' => $e->getLine(),
                        'code' => $e->getCode(),
                        'exception' => json_encode($e),
                    ];
                }
            } elseif ($provider == 'apple') {
                $provider_id = isset($payload['sub']) ? $payload['sub'] : null;
                $email = isset($payload['email']) ? $payload['email'] : null;
                $name = isset($payload['name']) ? $payload['name'] : null;
            } else {
                $provider_id = isset($payload['id']) ? $payload['id'] : null;
                $email = isset($payload['email']) ? $payload['email'] : null;
                $name = isset($payload['name']) ? $payload['name'] : null;
            }

            // check if access token exists etc..
            // search for a user in our server with the specified provider id and provider name
            $user = User::where('provider', $provider)->where('provider_id', $provider_id)->first();
            // if there is no record with these data, create a new user
            if ($user == null) {
                $user = User::where('email', $email)->first();
                if ($user == null) {
                    $user = User::create([
                        'email' => $email,
                        'usr_name' => $name,
                        'password' => bcrypt('hgfdsa'),
                        'provider' => $provider,
                        'provider_id' => $provider_id,
                        'is_login' => 0,
                        'is_verified' => 1
                    ]);
                    $usr_id = $user->id;
                    $login = User::where('id', $usr_id)->update(['is_login'  => 1]);
                    $settings = [
                        'usr_id' => $usr_id,
                        'reciter_id' => 18,
                        'trans_id' => 1,
                        'rtype_id' => 1,
                        'translation_id' => 1,
                        'language_id' => 1,
                        'is_notify' => 1,
                        'notification_time' => '08:00:00'

                    ];
                    $usettings = $this->usr_settings($settings);

                    // create a token for the user, so they can login
                    $token = $user->createToken(env('APP_NAME'))->accessToken;
                    // return the token for usage
                    $response = array(
                        'result' => 'success',
                        'user' => $user,
                        'token' => $token
                    );
                } else {
                    // create a token for the user, so they can login
                    $token = $user->createToken(env('APP_NAME'))->accessToken;
                    // return the token for usage
                    $response = array(
                        'result' => 'success',
                        'user' => $user,
                        'token' => $token
                    );
                }
            } else {
                // create a token for the user, so they can login
                $token = $user->createToken(env('APP_NAME'))->accessToken;
                // return the token for usage
                $response = array(
                    'result' => 'success',
                    'user' => $user,
                    'token' => $token
                );
            }
        }

        return response()->json($response);
    }

    public function googleLogin()
    {
        $user = Socialite::driver('google')->user();

        echo "<pre>";
        print_r($user);
        exit;
    }

    public function googleRedirect()
    {
        return Socialite::driver('google')->redirect();
    }


    public function logout_user(Request $request)
    {
        $data = $request->only('id');
        $id =  $data['id'];
        $login = User::where('id', $id)->update(['is_login'  => 0]);

        $res = ['result' => 'success', 'response' => 'updated successfully'];
        return response()->json($res, 200);
        //return redirect('/login');
    }
}
