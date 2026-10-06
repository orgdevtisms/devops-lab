<?php
// Whitelist de recursos: tabela, colunas permitidas [tipo, obrigatório, extra], busca e SQL de listagem.
$RES = [
  'veiculos' => [
    'table'=>'veiculos', 'label'=>"CONCAT(t.placa,' - ',t.modelo)",
    'select'=>'t.*', 'from'=>'veiculos t', 'order'=>'t.placa', 'search'=>['t.placa','t.modelo','t.marca'],
    'fields'=>[
      'placa'=>['placa',1],'renavam'=>['digits',0,11],'chassi'=>['text',0,17],'marca'=>['text',1,50],'modelo'=>['text',1,60],
      'ano'=>['int',0],'cor'=>['text',0,30],
      'combustivel'=>['enum',1,['gasolina','etanol','flex','diesel','eletrico','gnv','hibrido']],
      'km_atual'=>['int',1],'status'=>['enum',1,['ativo','manutencao','inativo']],
    ],
  ],
  'condutores' => [
    'table'=>'condutores', 'label'=>"t.nome",
    'select'=>'t.*', 'from'=>'condutores t', 'order'=>'t.nome', 'search'=>['t.nome','t.cpf','t.cnh_numero'],
    'fields'=>[
      'nome'=>['text',1,120],'cpf'=>['cpf',1],'cnh_numero'=>['digits',1,11],
      'cnh_categoria'=>['enum',1,['A','B','C','D','E','AB','AC','AD','AE']],'cnh_validade'=>['date',1],
      'telefone'=>['phone',0],'email'=>['email',0,150],'status'=>['enum',1,['ativo','inativo']],
    ],
  ],
  'manutencoes' => [
    'table'=>'manutencoes', 'label'=>"CONCAT('#',t.id,' ',t.tipo)",
    'select'=>'t.*, v.placa, v.modelo', 'from'=>'manutencoes t JOIN veiculos v ON v.id=t.veiculo_id',
    'order'=>'t.data_manutencao DESC', 'search'=>['v.placa','t.tipo','t.oficina_nome'],
    'fields'=>[
      'veiculo_id'=>['ref',1],'tipo'=>['enum',1,['preventiva','corretiva','revisao','troca_oleo','pneus','outros']],
      'descricao'=>['text',0,2000],'data_manutencao'=>['date',1],'km_manutencao'=>['int',0],'custo'=>['dec',0],
      'oficina_nome'=>['text',0,120],'oficina_cnpj'=>['cnpj',0],'proxima_data'=>['date',0],'proxima_km'=>['int',0],
      'status'=>['enum',1,['agendada','concluida']],
    ],
  ],
  'uso' => [
    'table'=>'uso_veiculos', 'label'=>"CONCAT('#',t.id)",
    'select'=>'t.*, v.placa, c.nome AS condutor',
    'from'=>'uso_veiculos t JOIN veiculos v ON v.id=t.veiculo_id JOIN condutores c ON c.id=t.condutor_id',
    'order'=>'t.data_uso DESC, t.id DESC', 'search'=>['v.placa','c.nome'],
    'fields'=>[
      'veiculo_id'=>['ref',1],'condutor_id'=>['ref',1],'autorizacao_id'=>['ref',0],'data_uso'=>['date',1],
      'km_inicial'=>['int',1],'km_final'=>['int',0],'observacao'=>['text',0,255],
    ],
  ],
  'autorizacoes' => [
    'table'=>'autorizacoes_conducao', 'label'=>"CONCAT('#',t.id,' - ',DATE_FORMAT(t.data_inicio,'%d/%m/%Y'))",
    'select'=>'t.*, v.placa, c.nome AS condutor',
    'from'=>'autorizacoes_conducao t JOIN veiculos v ON v.id=t.veiculo_id JOIN condutores c ON c.id=t.condutor_id',
    'order'=>'t.id DESC', 'search'=>['v.placa','c.nome','t.finalidade'],
    'fields'=>[
      'veiculo_id'=>['ref',1],'condutor_id'=>['ref',1],'data_inicio'=>['date',1],'data_fim'=>['date',1],
      'finalidade'=>['text',1,200],'destino'=>['text',0,200],'status'=>['enum',1,['pendente','aprovada','negada','encerrada']],
    ],
  ],
];
