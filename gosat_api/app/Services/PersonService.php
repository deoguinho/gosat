<?php
namespace App\Services;

use App\Models\Simulacao;
use Illuminate\Support\Facades\Http;
use PhpParser\Node\Expr\Cast\Object_;

class PersonService
{
    public function getProposals($cpf, $valorSolicitado, $qntParcelas)
    {
        $instituicoes = $this->consultApiCredit($cpf);
        $simulacao = $this->consultSimulacao($cpf, $instituicoes);
        $analyze = $this->analyzeData($cpf, $valorSolicitado, $qntParcelas, $instituicoes, $simulacao);
        return $analyze;
    }
    private function consultApiCredit($cpf)
    {
        $response = Http::post('https://dev.gosat.org/api/v1/simulacao/credito', [
            'cpf' => $cpf
        ])->json();
        return $response['instituicoes'];
    }
    private function consultSimulacao($cpf, $instituicoes)
    {
        $dados = [];

        foreach ($instituicoes as $instituicao) {
            foreach ($instituicao['modalidades'] as $modalidade) {
                $response = Http::post('https://dev.gosat.org/api/v1/simulacao/oferta', [
                    'cpf' => $cpf,
                    'instituicao_id' => $instituicao['id'],
                    'codModalidade' => $modalidade['cod']
                ])->json();
                $obj = new Simulacao();
                $obj->instituicaoFinanceira = $instituicao['nome'];
                $obj->modalidadeCredito = $modalidade;
                $obj->qntParcelaMin = $response['QntParcelaMin'];
                $obj->qntParcelaMax = $response['QntParcelaMax'];
                $obj->valorMin = $response['valorMin'];
                $obj->valorMax = $response['valorMax'];
                $obj->jurosMes = $response['jurosMes'];

                array_push($dados, $obj);
            }
        }
        return $dados;

    }
    private function analyzeData($cpf, $valorSolicitado, $qntParcelas, $instituicoes, $dados)
    {
        $analyzeData = [];
        foreach ($dados as $data) {
            if ($valorSolicitado >= $data['valorMin'] && $valorSolicitado <= $data['valorMax']) {
                $jurosComposto = $this->jurosComposto($valorSolicitado, $data, $qntParcelas);
                $array = [
                    "instituicaoFinanceira" => $data['instituicaoFinanceira'],
                    "modalidadeCredito" => $data['modalidadeCredito']['nome'],
                    "valorSolicitado" => $jurosComposto[0],
                    "taxaJuros" => $jurosComposto[1],
                    "valorAPagar" => $jurosComposto[2],
                    "qntParcelas" => $qntParcelas,
                ];
                array_push($analyzeData, $array);
            }
        }
        if (count($analyzeData) <= 0) {
            array_push($analyzeData, 'Não há oportunidades');
        }
        return $analyzeData;
    }

    private function jurosComposto($valorSolicitado, $data, $qntParcelas)
    {
        $opo = [];
        $investimento_inicial = $valorSolicitado;
        $investimento_mensal = 0;
        $meses = $qntParcelas;
        $taxa_de_juros = $data['jurosMes'] * 100;
        $investimento_acumulado = $investimento_inicial;
        $investimento_acumulado2 = $investimento_inicial + ($investimento_mensal * $meses);
        $juros_compostos_total = 0;

        for ($i = 0; $i < $meses; $i++) {
            $juros_compostos = ($investimento_acumulado * $taxa_de_juros) / 100;
            $juros_compostos_total += $juros_compostos;
            $investimento_acumulado += $juros_compostos + $investimento_mensal;
        }
        $valor_a_receber = $investimento_acumulado2 + $juros_compostos_total;
        number_format($investimento_acumulado2, 2, ",", ".");
        $juros_compostos_total = number_format($juros_compostos_total, 2, ",", ".");
        $valor_a_receber = number_format($valor_a_receber, 2, ",", ".");

        array_push($opo, $investimento_acumulado2, $juros_compostos_total, $valor_a_receber);
        return $opo;
    }
}