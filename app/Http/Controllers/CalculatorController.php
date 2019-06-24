<?php

namespace App\Http\Controllers;

use App\Library\Calculator;

class CalculatorController extends Controller
{

    public function sum($id){
        $calculator = new Calculator($id);
        return $calculator->sum();
    }
}
