<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Validator;


class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register', 'forget' , 'reset']]);
    }

   public function register(Request $request)
{
    $validator = Validator::make($request->all(), [
        'name' => 'required',
        'email' => 'required|email|unique:users',
        'password' => 'required',
    ]);

    if ($validator->fails()) {
        $errors = $validator->errors()->all();
        $errorMessage = implode(', ', $errors);
        return response()->json([
            'response' => Response::HTTP_UNPROCESSABLE_ENTITY,
            'success' => false,
            'message' => $errorMessage,
            'data' => []
        ]);

    } else {
        try {
            $user = new User;
            $user->name  = $request->name;
            $user->email  = $request->email;
            $user->password  = Hash::make($request->password);
            $user->remember_token  = Str::random(60);
            $respons = $user->save();
            return response()->json([
                'response' => Response::HTTP_OK,
                'success' => true,
                'message' => 'Register successfully.',
                'data' => $respons
            ], Response::HTTP_OK);
            
        } catch (QueryException $e) {
            return response()->json([
                'response' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'success' => false,
                'message' => $e->getMessage(),
                'data' => []
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}



    public function login()
    {
        $credentials = request(['email', 'password']);
        
        // Check if the user exists in the database
        $user = User::where('email', $credentials['email'])->first();
    
        if (!$user) {
            return response()->json([
                'response' => Response::HTTP_NOT_FOUND,
                'success' => false,
                'message' => 'User not Found',
                'data' => [],
            ], Response::HTTP_NOT_FOUND);
        }
    
        if (!$token = auth()->attempt($credentials)) {
            return response()->json([
                'response' => Response::HTTP_UNAUTHORIZED,
                'success' => false,
                'message' => 'Unauthorized',
                'data' => [],
            ], Response::HTTP_UNAUTHORIZED);
        }
    
        // Mendapatkan ID pengguna dari token JWT
        $userId = auth()->user()->id;
    
        return $this->respondWithTokenLogin($token, $userId);
    }

    public function forget(Request $request ){
        $cek = User::where('email', $request->email)->first();
        if($cek == null){
            return response()->json([
                'response' => Response::HTTP_NOT_ACCEPTABLE,
                'success' => false,
                'message' => 'email is not registered',
                'data' => [],
            ], Response::HTTP_NOT_ACCEPTABLE);
        }else{
            try {
                $token = Str::random(32);
                DB::table('password_resets')->insert([
                    'email' => $request->email, 
                    'token' => $token, 
                ]); 
                Mail::send('forgetPassword', ['token' => $token], function($message) use($request){
                    $message->to($request->email);
                    $message->subject('Reset Password');
                });
                return response()->json([
                    'response' => Response::HTTP_OK,
                    'success' => true,
                    'message' => 'email sent successfully',
                    'data' => [],
                ], Response::HTTP_OK);
                
            } catch (QueryException $e) {
                return response()->json([
                    'response' => Response::HTTP_INTERNAL_SERVER_ERROR,
                    'success' => false,
                    'message' => $e->getMessage(),
                    'data' => []
                ], Response::HTTP_INTERNAL_SERVER_ERROR);
            }
        }
    }
    
    public function settingEmail(Request $request)
    {
        // Dapatkan pengguna yang sedang masuk
        $user = auth()->user();
    
        // Validasi data yang diterima dari permintaan
        $validatedData = $request->validate([
            'email' => 'required|string',
        ]);
        
        // Periksa apakah email sudah digunakan oleh pengguna lain
        $existingUser = User::where('email', $validatedData['email'])->first();
        if ($existingUser && $existingUser->id !== $user->id) {
            // Jika email sudah digunakan oleh pengguna lain, kembalikan respons dengan success false
            $message = "Email has been used";
            $response = [
                'success' => false,
                'message' => $message,
            ];
    
            return response()->json($response);
        }
        
        // Update data profil pengguna
        $user->email = $validatedData['email'];
        
        $user->save();
    
        // Buat respons
        $message = "Email setting successfully.";
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $user
        ];
    
        return response()->json($response);
    }


    public function settingPassword(Request $request)
    {
        // Dapatkan pengguna yang sedang masuk
        $user = auth()->user();
    
        // Validasi data yang diterima dari permintaan
        $validatedData = $request->validate([
            'old_password' => 'required|string',
            'password' => 'required|string',
        ]);
    
        // Ambil old_password yang divalidasi
        $oldPassword = $validatedData['old_password'];
        $newPassword = $validatedData['password'];
    
        // Periksa apakah old_password yang diberikan sesuai dengan password pengguna sebelumnya
        if (!Hash::check($oldPassword, $user->password)) {
            $message = "Old password is incorrect.";
            $response = [
                'success' => false,
                'message' => $message,
            ];
    
            return response()->json($response);
        }
    
        // Hash password baru sebelum menyimpannya
        $hashedNewPassword = bcrypt($newPassword);
    
        // Simpan hashed password baru ke dalam penyimpanan yang sesuai, misalnya basis data
        $user->password = $hashedNewPassword;
        $user->save();
    
        // Buat respons
        $message = "Password setting successfully.";
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $user
        ];
    
        return response()->json($response);
    }


    public function logout()
    {
        auth()->logout();
        return response()->json([
            'response' => Response::HTTP_OK,
            'success' => true,
            'message' => 'Successfully logged out',
            'data' => [],
        ], Response::HTTP_OK);    
    }

    public function refresh()
    {
        return $this->respondWithToken(auth()->refresh());
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'response' => Response::HTTP_OK,
            'success' => true,
            'message' => 'JWT Token refresh Successfully',
            'data' => [
                'access_token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60
            ]
        ], Response::HTTP_OK);
    }

    protected function respondWithTokenLogin($token, $userId)
    {
        return response()->json([
            'message' => 'Login Berhasil',
            'data' => [
                'user_id' => $userId,
                'token' => $token,
                'token_type' => 'bearer',
                'expires_in' => auth()->factory()->getTTL() * 60
            ]
        ], Response::HTTP_OK);
    }
}
