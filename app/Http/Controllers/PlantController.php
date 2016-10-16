<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;

use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Response;

use App\User;
use App\Plant;

class PlantController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
         try {
            $getUser = JWTAuth::parseToken()->toUser();

            $user = User::findOrFail($getUser['id']);

            if(! $getUser) {
                 return response()->json(['message' => 'You are unauthorized to make this request', 'status code' => 401], 401);
            }
        } catch (JWTException $ex) {
            return Response::json([$ex], 403);
        }

        $plants = Plant::where('is_accepted', '=', 'true')
                    ->where('is_deleted', null)->orderBy('id', 'ASC')->get();

        return $plants;
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
        // Validate Plant Input
        $plantValidator = Validator::make($request->all(), [
            'herbal_name' => 'required|',
            'scientific_name' => 'required|',
            'properties' => 'required|',
            'usage' => 'required|',
            'process' => 'required|',
            'image_url' => 'required|',
            ]);

        if($plantValidator->fails()){
            $errors = $plantValidator->errors();

            return $errors->toJson();
        }

        // Validate user token
       
        $getUser = JWTAuth::parseToken()->toUser();

        $user = User::findOrFail($getUser['id']);

        if(! $getUser) {
            return response()->json(['message' => 'You are unauthorized to make this request', 'status code' => 401], 401);
        }

        // Insert plant's details to database
        $find = Plant::where('herbal_name', '=' , $request->input('herbal_name'))->first();
        
        $plant_image = $request->file('image_url');
        $file_name = $plant_image->getClientOriginalName();
        $plant_image->move('gallery/images', $file_name);

        if($find == null) {
            $insertPlant = Plant::create([
                'herbal_name'       => $request->input('herbal_name'),
                'scientific_name'   => $request->input('scientific_name'),
                'vernacular_name'   => $request->input('vernacular_name'),
                'properties'        => $request->input('properties'),
                'usage'             => $request->input('usage'),
                'process'           => $request->input('process'),
                'image_url'         => 'gallery/images/' .$file_name,
                'is_accepted'       => "false"
                ]);

            // Display
            if($insertPlant) {
                $data = [
                    'user' => [
                        'id'                => $user['id'],
                        'name'              => $user['name'],
                        'email'             => $user['email'],
                        'is_admin'          => $user['is_admin'],
                        'profile_image_url' => $user['profile_image_url']
                    ],
                    'plant' => [
                        'id'                => $insertPlant['id'],
                        'herbal_name'       => $request->input('herbal_name'),
                        'scientific_name'   => $request->input('scientific_name'),
                        'vernacular_name'   => $request->input('vernacular_name'),
                        'properties'        => $request->input('properties'),
                        'usage'             => $request->input('usage'),
                        'process'           => $request->input('process'),
                        'image_url'         => 'gallery/images/' .$file_name,
                        'is_accepted'       => 'false',
                        'success'           => 'false'
                    ]
                ];
                return Response::json([$data], 201);
            }
        }else {

            return Response::json(['message' => "Plant already exists!", 'status code' => 403], 403);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($query)
    {
        try {
            $getUser = JWTAuth::parseToken()->toUser();

            $user = User::findOrFail($getUser['id']);

            if(! $getUser) {
                return Response::json(['message' => 'Bad Request', 'status code' => 400], 400);
            }
        } catch (JWTException $ex) {
            return Response::json($ex);
        }


        $search = '%'.$query.'%';

        $plants = Plant::where('is_accepted', 'true')
                        ->where('is_deleted', null)
                        ->where(function ($prevQuery) use ($search){
                            $prevQuery->where('herbal_name', 'LIKE', $search)
                                ->orWhere('id', $search);
                        })
                        ->get(['id', 'herbal_name', 'scientific_name', 'vernacular_name', 'properties', 'usage', 'process', 'image_url']);

        if($plants->isEmpty()) {
            return Response::json(['message' => 'Nothing to display', 'status code' => 404], 404);
        }

        return $plants;
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
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function accept(Request $request)
    {   
        $plant = Plant::find($request->id);

        $user = JWTAuth::parseToken()->toUser();
        
        if($user['is_admin'] == 'false'){
            return response()->json(['message' => 'You are unauthorized to make this request', 'status_code' => 401], 401);
        }

        if($plant['is_accepted'] == 'false') {
            $plant['is_accepted'] = 'true';
            $plant->save();
        }else {
            return Response::json(['message' => 'Plant is already in the directory. No need to make this request.', 'status code' => 422], 422);
        }

        $data = [
            'plant' => [
                'id'                => $plant->id,
                'herbal_name'       => $plant->herbal_name,
                'scientific_name'   => $plant->scientific_name,
                'vernacular_name'   => $plant->vernacular_name,
                'properties'        => $plant->properties,
                'usage'             => $plant->usage,
                'process'           => $plant->process,
                'image_url'         => $plant->image_url,
                'is_accepted'       => $plant->is_accepted,
                'success'           => 'true'
            ]
        ];

        return Response::json([$data], 202);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $plantID = Plant::find($id);

        $user = JWTAuth::parseToken()->toUser();

        if($user['is_admin'] == 'false'){
            return response()->json(['message' => 'You are unauthorized to make this request', 'status code' => 401], 401);
        }

        if($plantID['is_accepted'] == 'true' && $plantID->is_deleted == 'true' || $plantID == null){
            return Response::json(['message' => "Plant was already deleted!", 'status code' => 422], 422);
        }

        if($plantID['is_accepted'] == 'false') {
            $plantID->delete();
        }else {
            $plantID->is_deleted = 'true';
            $plantID->save();
        }

        $plant = [
            'id'                => $plantID->id,
            'herbal_name'       => $plantID->herbal_name,
            'scientific_name'   => $plantID->scientific_name,
            'vernacular_name'   => $plantID->vernacular_name,
            'properties'        => $plantID->properties,
            'usage'             => $plantID->usage,
            'process'           => $plantID->process,
            'image_url'         => $plantID->image_url,
        ];

        return Response::json(['plant' => $plant, 'message' => "Successfully Deleted!"], 200);
    }
}
