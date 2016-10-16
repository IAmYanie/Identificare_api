<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Response;

use App\User;

use Auth;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->only('name', 'email', 'password'), [
            'name' => 'required|',
            'email' => 'required|email',
            'password' => 'required|min:6'
            ]);

        if($validator->fails()){
            $errors = $validator->errors();

            return $errors->toJson();
        }

        $user = $request->only('email', 'password');

        $profile_image = $request->file('profile_image_url');
        $file_name = $profile_image->getClientOriginalName();
        $profile_image->move('gallery/images', $file_name);

        if($request->is_admin == null) {
            $is_admin = "false";
        }else {
            $is_admin = "true";
        }

        $userData = User::create([
                        'name'              => $request->input('name'),
                        'email'             => $request->input('email'),
                        'password'          => Hash::make($request->input('password')),
                        'is_admin'          => $is_admin,
                        'profile_image_url' => 'gallery/profile_image/' .$file_name,
                            ]);

        
        if(! $authtoken = JWTAuth::attempt($user)){ 
            return Response::json(['message' => 'Invalid Credentials!', 'status code' => 401], 401);
        }

        

        User::where('id', $userData['id'])->update(['remember_token' => $authtoken]);

        $data = [
            'authtoken' => $authtoken,
            'user'      =>  [
                    'id'                    =>  $userData['id'],
                    'name'                  =>  $userData['name'],
                    'email'                 =>  $userData['email'],
                    'is_admin'              =>  $userData['is_admin'],
                    'profile_image_url'     =>  $userData['profile_image_url']
            ]
        ];

        return Response::json([$data], 201); 
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        try {
            $user = User::findOrFail($id);

            $tempUser = JWTAuth::parseToken()->toUser();

            if(! $tempUser) {
                return response()->json(['message' => 'You are unauthorized to make this request', 'status code' => 401], 401);
            }

        } catch (JWTException $ex) {
            return $ex;
        } 

        $data = [
            'user' => [
                'id'                => $user['id'],
                'name'              => $user['name'],
                'email'             => $user['email'],
                'is_admin'          => $user['is_admin'],
                'profile_image_url' => $user['profile_image_url']
            ]
        ];

        return Response::json([$data], 200); 
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * 
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $tempUser = JWTAuth::parseToken()->toUser();

        if($tempUser['id'] != $id) {
            return Response::json(['message' => "You are unauthorized to make this request", 'status code' => 401], 401);
        }

        $validator = Validator::make($request->all(), [
            'email' => 'email',
            'password' => 'min:6',
            'name' => 'min:7'
            ]);

        if($validator->fails()){
            $errors = $validator->errors();
            return $errors->toJson();
        }
            
        $user = User::find($id);

        if($request->input('name') != null) {
            $user->name = $request->input('name');
        }

        if($request->input('email') != null) {
            $user->email = $request->input('email');
        }

        if($request->input('password') != null) {
            $user->password = Hash::make($request->input('password'));
        }

        if($request->input('profile_image_url') != null) {
            $profile_image = $request->file('profile_image_url');
            $file_name = $profile_image->getClientOriginalName();
            $profile_image->move('gallery/profile_image', $file_name);

            $user->profile_image_url = 'gallery/profile_image/' .$file_name;
        }

        $user->save();
        $data = [
                    'user'      =>  [
                        'id'                    =>  $user->id,
                        'name'                  =>  $user->name,
                        'email'                 =>  $user->email,
                        'is_admin'              =>  $user->is_admin,
                        'profile_image_url'     =>  $user->profile_image_url
                    ]
                ];

        return Response::json([$data], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        try {
            $user = JWTAuth::parseToken()->authenticate();
        } catch (JWTException $e) {
            return $this->response->$e->toJson();
        }

        User::where('id', $id)->update(['is_deleted' => "true"]);

        $successful = [
            'message' => "User that has an ID number of ".$id." deleted successfully!",
            'status code' => '200'
        ];

        $unsuccessful = [
            'message' => "User that has an ID number of ".$id." deleted unsuccessfully!",
            'status code' => '400'
        ];

        $temp = User::where('id', $id)->where('is_deleted', 'true');

        if($temp == null) {
            return json_encode($unsuccessful);
        }

        return json_encode($successful);
    }

    public function me()
    {
        $tempUser = JWTAuth::parseToken()->toUser();
        
        if(! $tempUser) {
            return Response::json(["You are unauthorized to make this request"], 401);
        }

        $data = [
            'user' => [
                'id'                => $tempUser['id'],
                'name'              => $tempUser['name'],
                'email'             => $tempUser['email'],
                'is_admin'          => $tempUser['is_admin'],
                'profile_image_url' => $tempUser['profile_image_url']
            ]
        ];

        return Response::json([$data], 202);

    }

    public function login(Request $request)
    {
        $checker = $request->only('email','password');

        $user = User::where('email', $checker['email'])->first();

        if(Auth::attempt(['email'=>$request->email,'password'=>$request->password]))
        {   
            $authtoken = JWTAuth::attempt($checker);
        }else{
            return Response::json(["Invalid username/password combination."], 401);
        }

        User::where('email', $user['email'])->update(['remember_token' => $authtoken]);

        $data = [
            'authtoken' => $authtoken,
            'user'      =>  [
                    'id'                    =>  $user['id'],
                    'name'                  =>  $user['name'],
                    'email'                 =>  $user['email'],
                    'is_admin'              =>  $user['is_admin'],
                    'profile_image_url'     =>  $user['profile_image_url']
            ]
        ];

        return Response::json([$data], 200);
    }
}