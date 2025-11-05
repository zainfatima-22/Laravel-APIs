<?php

namespace App\Http\Filters\V1;
use Illuminate\Http\Request;

class TicketFilter extends QueryFilter{
    public function include($value){
        return $this->builder->with($value);
    }
    public function status($value){
        return $this->builder->whereIn('status', values: explode(',', $value));
    }
    public function title($value){
        $likeStr = str_replace('*', '%', $value); 
        return $this->builder->where('title','like', $likeStr);
    }

}