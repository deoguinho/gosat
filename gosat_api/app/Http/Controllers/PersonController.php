<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Services\PersonService;

class PersonController extends Controller
{
    protected $personService;
    public function __construct(PersonService $personService) {
        $this->personService = $personService;
    }


    public function getProposals(Request $request)
    {
        $dados = $request->all();
        try{
            $response = $this->personService->getProposals($dados['cpf'],$dados['valorSolicitado'],$dados['qntParcelas']);
            return response()->json($response, 200);
        }catch(\Exception $ex){
            
            return response()->json($ex, 400);
        };
    }

}