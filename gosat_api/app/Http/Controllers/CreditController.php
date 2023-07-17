<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use GuzzleHttp\Client;


class CreditController extends Controller
{
    // public function index(){
    //     return '<h1>Hello World</h1>';
    // }
    public function index()
    {
        $response = Http::post('https://dev.gosat.org/api/v1/simulacao/credito', [
            'cpf' => '11111111111'
        ])->json();

        foreach ($response['instituicoes'] as $value) {
            print("{$value['nome']} <br>");
        }
    }

    public function store(Request $request){
        echo 'estou aqui';
    }
}