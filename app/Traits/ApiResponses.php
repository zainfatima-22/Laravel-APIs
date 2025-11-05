<?php

namespace App\Traits;
use Illuminate\Database\Eloquent\Model;

trait ApiResponses{
    public function ok($message, $data=[]){
        return $this->success($message, $data, 200);
    }
    public function error($message, $data=[]){
        return $this->success($message, $data, 404);
    }
    protected function success($message, $data = [], $statuscode=200){
        return response()->json([
            'data' => $data,
            'message' => $message, 
            'status'=> $statuscode
        ], $statuscode);
    }
}