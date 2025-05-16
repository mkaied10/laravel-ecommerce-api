<?php

namespace App\Helpers;

class ResponseHelper
{
    public static function success( $message = 'Operation Successful',$data = [], $code = 200, $meta = []){
        
        return response()->json([
            'status'=>true,
            'message'=>$message,
            'data'=>$data,
            'meta' => $meta,
        ],$code);
    }
    public static function error($message = 'Something went wrong', $code = 400, $errors = []){
        
        return response()->json([
            'status' => false,
            'message' => $message,
            'errors' => $errors,
        ], $code);
    }
}
?>