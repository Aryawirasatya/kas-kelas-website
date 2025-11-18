<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ExpenseRequestController extends Controller
{
    public function index()
    {
        return view('expense_requests.index');
    }

    public function create()
    {
        return view('expense_requests.create');
    }
}
