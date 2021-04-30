<?php

namespace App\Http\Controllers;
use App\Http\Controllers\BaseController as BaseController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisterController extends BaseController
{
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name' =>'required',
            'email' =>'required|email',
            'password' =>'required',
            'c_password' =>'required|same:password',
        ]);

        if ($validator->fails()) {
            return $this->sendError('Please validate error' ,$validator->errors() );
        }

            $input = $request->all();
            $input['password'] = Hash::make($input['password']);
            $user = User::create($input);
            $success['token'] = $user->createToken('myAppl')->accessToken;
            $success['name'] = $user->name;

        return $this->sendResponse($success ,'تم تسجيل المستخدم بنجاح' );
    }

    public function login(Request $request)
    {

        if(Auth::attempt(['email' => $request->email, 'password' => $request->password]))
        {
            // $user = Auth::user();
            $user = User::where('email', $request->email)->first();
            $success['token'] = $user->createToken('myAppl')->accessToken;
            //store device_token in database
            $user->device_token =$success['token'];
            $user->save();
            //
            $success['name'] = $user->name;

            return $this->sendResponse($success ,'User login successfully' );
        }
        else{
            return $this->sendError('Please check your Auth' ,['error'=> 'Unauthorised'] );
        }


    }
}
