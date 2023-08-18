<?php

namespace App\Http\Controllers\Apis;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth:api', 'verified'], ['except' => ['login', 'register', 'verify', 'notice', 'resend']]);
    }


    public function register (Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required|unique:users',
            'password' => 'required', 'confirmed',

        ]);

        if ($validator->fails()){
            $status = false;
            $message = $validator->errors(); 

            return response()->json([
                'status' => $status,
                'message' => $message
            ], 400);
        } else{
            $status = true;
            $message = 'new user has been created. Please check your email for verification.';

            $hashPassword = Hash::make($request->password);
            $request['password'] = $hashPassword;
            $user = User::create($request->all())->sendEmailVerificationNotification();

            //$token = Auth::login($user); //ni artinya pembuatan token login
            $typeToken = 'Bearer ';


            return response()->json([
                'status' => $status,
                'message' => $message,
                //'user' => $user,
                //'authorization' => $typeToken.$token
                // 'authorization' => [
                //     'token' => $token,
                //     'type' => 'Bearer'
                // ]
            ], 201);
        }
    }


    public function login (Request $request){
        $validator = Validator::make($request->all(), [
            'name' => 'required',
            'email' => 'required',
            'password' => 'required', 'confirmed',

        ]);

        if($validator->fails()){
            return response()->json([
                'status' => false,
                'message' => $validator->errors()

            ], 400);
        } else{

            $loginValue = $request->only('email', 'password');

            $token = Auth::attempt($loginValue);
            $typeToken = 'Bearer ';

            if(!$token){
                return response()->json([
                    'status' => false,
                    'message' => 'Email atau Password Salah'
                ], 400);
            } else{
                $user = Auth::user();
                return response()->json([
                    'status' => true,
                    'message' => 'Login Berhasil',
                    'user' => $user,
                    'authorization' => $typeToken.$token
                ], 200);
            }


            // if(Auth::attempt(['name' => $request->name, 'password' => $request->password, 'email' => $request->email])){
            //     // $user = Auth::user();
            //     $token = $request->user()->createToken('authToken')->plainTextToken;
    
            //     return response()->json([
            //         'status' => true,
            //         'message' => 'Login Berhasil',
            //         'token' => $token
            //     ], 200);
            // } else{
            //     return response()->json([
            //         'status' => true,
            //         'message' => 'Login Gagal'
            //     ], 400);
            // }
        }

        
    }


    public function logout(Request $request){
       
        Auth::logout();
        return response()->json([
            'status' => true,
            'message' => 'Logout Berhasil'
        ], 200);
    }

    public function refresh()
    {
        $token = Auth::refresh();
        $user = Auth::user();
        $typeToken = 'Bearer ';

    //             return response()->json([
    //                 'status' => true,
    //                 'message' => 'Anda berhasil refresh token',
    //                 'user' => $user,
    //                 'authorization' => [
    //                     'token' => $token,
    //                     'type' => 'Bearer'
    //                 ]
    //             ], 200);
    // }
        return response()->json([
            'status' => true,
            'message' => 'Anda berhasil refresh token',
            'user' => $user,
            'authorization' => $typeToken.$token
        ], 200);
    }


    public function verify($id, Request $request)
    {
        if(!$request->hasValidSignature())
        {
            return response()->json([
                'status' => false,
                'message' => 'Verifikasi Email Gagal'
            ], 400);
        }

        $user = User::find($id);
        if(!$user->hasVerifiedEmail())
        {
            $user->markEmailAsVerified();
        } else{
            return redirect()->to('/apagitu');
        }

        return redirect()->to('/');
    }


    public function notice(){
        return response()->json([
            'status' => false,
            'message' => 'Anda belum verifikasi email'
        ], 400);
    }

    public function resend(Request $request){
        if($request->user()->hasVerifiedEmail())
        {
            return response()->json([
                'status' => true,
                'message' => 'Email sudah diverifikasi'
            ], 200);
        } else{
            $request->user()->sendEmailVerificationNotification();

            return response()->json([
                'status' => false,
                'message' => 'Anda belum verifikasi email. Kami telah mengirimkan email verifikasi untuk anda.'
            ], 400);
        }
    }
}


