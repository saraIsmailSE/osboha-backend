<?php

namespace App\Exceptions;
use Exception;

class NotFound extends Exception
{
    public function render()
    {
        $responseJson['statusCode']=404;
        $responseJson['message']='Request not found';
        return response($responseJson,404);
    }
}
