<?php
date_default_timezone_set('America/Sao_Paulo');
require 'vendor/autoload.php';
session_start();

if (isset($_POST['voltar'])) {
  header('Location: index.php');
  die();
}

if (isset($_POST['data']) && $_POST['data'] > date("Y-m-d")) {
  $_SESSION['mensagem'] = 'Não é permitido jogar no futuro.';
  header('Location: index.php');
  die();
}

if (empty($_POST['novo'])) {
  //if (isset($_SESSION['seed']) && $_SESSION['seed'] != date("Ymd")) {
  //  unset($_SESSION);
  //  $_SESSION['mensagem'] = 'Havia um jogo em andamento.';
  //  header('Location: index.php');
  //  die();
  //}

  if (empty($_SESSION['modo'])) {
    header('Location: index.php');
    die();
  } else if ($_SESSION['modo'] != 'arma') {
    $_SESSION['mensagem'] = 'Já existe um jogo em andamento.';
    header('Location: index.php');
    die();
  }
}

function obter_dados($rota, $post=[]) {
  $URL_BASE = 'http://localhost/ragnarokdle-api/ragnarokdle-api/v1';
  //$URL_BASE = 'http://ragnarokdle.x10.mx/ragnarokdle-api/v1';
  $cookieFile = getcwd().'/cookies/cookie.txt';
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, $URL_BASE.$rota);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
  curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  //curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID='.$_COOKIE['PHPSESSID']);
  if ($post && is_array($post)) {
    curl_setopt($ch, CURLOPT_POST, count($post));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
  }

  $dados = (array) json_decode(curl_exec($ch));
  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);
//var_dump($dados);exit;
//var_dump($_COOKIE['PHPSESSID']);exit;
  if (isset($dados['erro']))
    return $dados;
  if ($http_code != 200 || !$dados) {
    return ['erro' => 'Erro na comunicação com o servidor: '.curl_error($ch)];
  }
  return $dados;
}

function replace_accents($str) {
  $str = htmlentities($str, ENT_COMPAT, "UTF-8");
  $str = preg_replace('/&([a-zA-Z])(uml|acute|grave|circ|tilde|cedil);/','$1',$str);
  return html_entity_decode($str);
}
//iconv('UTF-8','ASCII//TRANSLIT',$val);
function cmp($a, $b) {
  //echo '<br>ori: '.$a.' '.$b;
  $a = preg_replace('/ \[\d\]/', '', $a);
  $b = preg_replace('/ \[\d\]/', '', $b);
  //echo '<br>-slot: '.$a.' '.$b;
  $a = strtolower(replace_accents($a));
  $b = strtolower(replace_accents($b));
  //echo '<br>minús, -acen: '.$a.' '.$b;
  //exit;
  return strcmp($a, $b);
}

$seed = 0;
$palpites = [];
$nomes = [];
$descobriu = false;
$dicas = [
  ['dica' => '', 'revelada' => false, 'durante_o_jogo' => false],
  ['dica' => '', 'revelada' => false, 'durante_o_jogo' => false]
];
$qtde_palpites_pra_revelar_dica_1 = 5;
$qtde_palpites_pra_revelar_dica_2 = 10;

$palpite = '';
$erro = '';
$arma = '';
$nomes_restantes = [];

if (isset($_SESSION['seed']))
  $seed = $_SESSION['seed'];
if (isset($_SESSION['palpites']))
  $palpites = array_reverse($_SESSION['palpites']);
if (isset($_SESSION['nomes']))
  $nomes = $_SESSION['nomes'];
if (isset($_SESSION['descobriu']))
  $descobriu = $_SESSION['descobriu'];

if (isset($_SESSION['dicas']))
  $dicas = $_SESSION['dicas'];

if (isset($_POST['dica'])) {
  $n = (int) $_POST['dica'];
  if ($n < 0 || $n > 1) {
    $_SESSION['mensagem'] = 'Dica inexistente: "'.$_POST['dica'].'"';
    die();
  }
  $_SESSION['dicas'][$n]['revelada'] = true;
  if (!$descobriu)
    $_SESSION['dicas'][$n]['durante_o_jogo'] = true;
  $dicas = $_SESSION['dicas'];
  header('Location: ragnarokdle-armas.php');
  die();
}
  
if (isset($_POST['novo'])) {
  if (isset($_POST['data']))
    $data = str_replace('-','',$_POST['data']);
  $dados = obter_dados('/jogo', ['modo'=>'arma', 'data' => $data]);
  //var_dump($dados);exit;
  if (!$dados) {
    $_SESSION['mensagem'] = 'Erro na comunicação com o servidor.';
    header('Location: index.php');
    die();
  }
  else if (isset($dados['erro'])) {
    $_SESSION['mensagem'] = $dados['erro'];
    //var_dump($dados['erro']);
    header('Location: index.php');
    die();
  }

  $_SESSION['seed'] = $dados['seed'];
  $_SESSION['modo'] = $dados['modo'];
  $seed = $_SESSION['seed'];
  $palpites = [];
  $nomes = [];
  $descobriu = false;
  unset($_SESSION['palpites']);
  unset($_SESSION['nomes']);
  unset($_SESSION['descobriu']);
  $_SESSION['dicas'] = [
    ['dica' => $dados['dicas'][0], 'revelada' => false, 'durante_o_jogo' => false],
    ['dica' => $dados['dicas'][1], 'revelada' => false, 'durante_o_jogo' => false],
    ['dica' => $dados['dicas'][2], 'revelada' => false, 'durante_o_jogo' => false]
  ];
  $dicas = $_SESSION['dicas'];
  header('Location: ragnarokdle-armas.php');
  die();
}

if (empty($_SESSION['nomes'])) {
  $dados = obter_dados('/nomes');
  if (!$dados) {
    $_SESSION['mensagem'] = 'Erro na comunicação com o servidor.';
    header('Location: index.php');
    die();
  }
  else if (isset($dados['erro'])) {
    $_SESSION['mensagem'] = $dados['erro'];
    header('Location: index.php');
    die();
  }
  $_SESSION['nomes'] = $dados['nomes'];
  //$collator = collator_create('pt-BR');
  //collator_sort($collator, $_SESSION['nomes']);
  //setlocale(LC_COLLATE, 'pt_BR', 'pt_BR.utf-8');
  //sort($_SESSION['nomes']);
  usort($_SESSION['nomes'], "cmp");
  $nomes = $_SESSION['nomes'];
}

if (isset($_POST['palpite']) && $_SESSION['descobriu'] == false) {
  $dados = obter_dados('/palpites', ['palpite' => $_POST['palpite']]);
  //var_dump($dados);exit;
  if (!$dados)
    $erro = 'Erro na comunicação com o servidor.';
  else if (isset($dados['erro']))
    $erro = $dados['erro'];
  else {
    $arma = $dados;
    array_push($_SESSION['palpites'], $arma);
    array_unshift($palpites, $arma);
    header('Location: ragnarokdle-armas.php');
    die();
  }
}

if (empty($_SESSION['palpites'])) {
  $dados = obter_dados('/palpites');
  if (!$dados) {
    $_SESSION['mensagem'] = 'Erro na comunicação com o servidor.';
    header('Location: index.php');
    die();
  }
  else if (isset($dados['erro'])) {
    $_SESSION['mensagem'] = $dados['erro'];
    header('Location: index.php');
    die();
  }
  $_SESSION['palpites'] = $dados['palpites'];
  $palpites = array_reverse($_SESSION['palpites']);
  //var_dump($palpites);
}

if (empty($_SESSION['descobriu'])) {
  $dados = obter_dados('/jogo');
  if (!$dados) {
    $_SESSION['mensagem'] = 'Erro na comunicação com o servidor.';
    header('Location: index.php');
    die();
  }
  else if (isset($dados['erro'])) {
    $_SESSION['mensagem'] = $dados['erro'];
    header('Location: index.php');
    die();
  }
  $_SESSION['descobriu'] = $dados['descobriu'];
  $descobriu = $_SESSION['descobriu'];
}

$nomes_das_armas_palpitadas = array_map(function($p) {return $p['nome'];}, $palpites);
$nomes_restantes = array_diff($nomes, $nomes_das_armas_palpitadas);

if (isset($arma['id_r']) && $arma['id_r'] === 1) {
  $descobriu = true;
  $_SESSION["descobriu"] = true;
  $erro = 'Parabéns! Você descobriu a arma!';
}
?>

<!DOCTYPE html>
<html lang="pt-br"> 
  <head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="favicon.svg"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ragnarökdle - Armas</title>
  </head>
<body>

<datalist id="armas">
<?php
foreach ($nomes_restantes as $p)
 echo '<option value="'.$p.'"></option>';
?>
</datalist>

Ragnarökdle - Armas<br>
Data do jogo: <?php echo $seed; ?><br>

<form action="ragnarokdle-armas.php" method="POST">
  <input type="submit" name="voltar" value="Voltar">
</form>

<form id="form_palpite" action="ragnarokdle-armas.php" method="POST" style="margin: 0.5rem 0;">
  <label for="palpite">Arma:</label><br>
  <input id="palpite" list="armas" name="palpite" autofocus autocomplete="off" />
  <input id="enviar" type="submit" <?php if ($descobriu) echo 'disabled'; ?> value="Enviar">
</form>
<?php echo $erro; ?>
<br>
<br>

Palpites: <?php echo count($palpites); ?>
<br>Dicas reveladas durante o jogo:
<?php
  echo ($dicas[0]['durante_o_jogo'] ? 'descrição' : '')
    . ($dicas[0]['durante_o_jogo'] && $dicas[1]['durante_o_jogo'] ? ', ' : '')
    . ($dicas[1]['durante_o_jogo'] ? 'drop' : '')
    . (!$dicas[0]['durante_o_jogo'] && !$dicas[1]['durante_o_jogo'] ? 'nenhuma' : '');
?>
<form action="ragnarokdle-armas.php" method="POST">
<?php
  if (!$dicas[0]['revelada']){
    if (count($palpites) < $qtde_palpites_pra_revelar_dica_1 && !$descobriu)
      echo '<button disabled>Revelar início da descrição em '
        .($qtde_palpites_pra_revelar_dica_1 - count($palpites))
        .' palpites</button>';
    else
      echo '<button type="submit" name="dica" value="'. 0 .'">Revelar '
        .(!$descobriu ? 'início da ' : '').'descrição</button>';
  } else if ($_SESSION['dicas'][0]['dica'])
    echo 'Descrição: '.($descobriu ? $_SESSION['dicas'][0]['dica']
      : trim(substr($_SESSION['dicas'][2]['dica'], 0, 30)).'...');
  else
    echo 'Descrição: descrição não encontrada.';
  echo '<br>';
  if (!$dicas[1]['revelada']){
    if (count($palpites) < $qtde_palpites_pra_revelar_dica_2 && !$descobriu)
      echo '<button disabled>Revelar monstro com maior chance de drop em '
        .($qtde_palpites_pra_revelar_dica_2 - count($palpites))
        .' palpites</button>';
    else
      echo '<button type="submit" name="dica" value="'. 1 .'">Revelar monstro com maior chance de drop</button>';
  } else if ($_SESSION['dicas'][1]['dica']) {
    $monstro = '';
    $chance = -1.0;
    foreach ($_SESSION['dicas'][1]['dica'] as $drop) {
      $c = (double) str_replace('%', '', $drop->rate);
      if ($c > $chance) {
        $chance = $c;
        $monstro = $drop->monster;
      }
    }
    //echo 'Monstro com maior chance de drop: '.$_SESSION['dicas'][1][0]->monster
    //  .' ('.str_replace('.', ',', $_SESSION['dicas'][1][0]->rate).')';
    echo 'Monstro com maior chance de drop: '.$monstro.' ('.$chance.'%)';
  } else
    echo 'Monstro com maior chance de drop: não dropa de nenhum monstro.';
?>
</form>

<table>
<tr>
  <th></th>
  <th>Nome</th>
  <th>Tipo</th>
  <th>Slots</th>
  <th>Nível da arma</th>
  <th>Ataque</th>
  <th>Propriedade</th>
  <th>Peso</th>
  <th>Preço de venda</th>
  <th>Pode ser comprado em NPC</th>
  <!--<th>Pode ser dropado por monstros</th>-->
</tr>

<?php
foreach($palpites as $pp) {
  $pp = (object) $pp;
  echo '
  <tr>
    <td style="float: right;"><img title="'.$pp->id.'" src="https://db.irowiki.org/image/item/'.$pp->id.'.png"</td>
    <td style="background-color: '.($pp->nome_r ? 'lime' : 'red').';">'
    .$pp->nome.'</td>
    <td style="background-color: '.($pp->tipo_r ? 'lime' : 'red').';">'
    .$pp->tipo.'</td>
    <td style="background-color: '.($pp->slots_r ? 'lime' : 'red').';">'
    .$pp->slots.'</td>
    <td style="background-color: '.($pp->nivel_da_arma_r ? 'lime' : 'red').';">'
    .$pp->nivel_da_arma.'</td>
    <td style="background-color: '.($pp->ataque_r === 1 ? 'lime' : 'red').';">'
    .($pp->ataque_r === 2 ? '<' : ($pp->ataque_r === 0 ? '>' : '')).($pp->ataque).'</td>
    <td style="background-color: '.($pp->propriedade_r ? 'lime' : 'red').';">'
    .$pp->propriedade.'</td>
    <td style="background-color: '.($pp->peso_r === 1 ? 'lime' : 'red').';">'
    .($pp->peso_r === 2 ? '<' : ($pp->peso_r === 0 ? '>' : '')).($pp->peso).'</td>
    <td style="background-color: '.($pp->preco_de_venda_r === 1 ? 'lime' : 'red').';">'
    .($pp->preco_de_venda_r === 2 ? '<' : ($pp->preco_de_venda_r === 0 ? '>' : '')).($pp->preco_de_venda).'</td>
    <td style="background-color: '.($pp->pode_ser_comprado_r ? 'lime' : 'red').';">'
    .($pp->pode_ser_comprado ? 'Sim' : 'Não').'</td>
  </tr>
  ';
}
?>
</table>

<?php
//if ($descobriu && isset($_POST['palpite']))
//  echo "<script>alert('Parabéns! Você descobriu a arma!')</script>";
?>

</body>

<script>
  let alterou,tecla,tkey;
  document.getElementById('palpite').addEventListener('keydown', function (e) {
    //if(e.repeat)
    //  return;
    //console.log(e.keyCode);
    tkey = e.key;
    if (e.keyCode >= 33 && e.keyCode <= 40)
      tecla = false;
    else
      tecla = true;
    //if(e.key == "Enter"){
    //  console.log('Enter');
      //document.getElementById('form_palpite').submit();
      //document.getElementById('form_palpite').click();
      //iniciar();
    //}
  });
  document.getElementById('palpite').addEventListener('click', function (e) {
    //console.log('clicou');
    tecla = false;
    //if(e.key == "Enter"){
    //  console.log('Enter');
    //  //document.getElementById('form_palpite').submit();
    //  //document.getElementById('form_palpite').click();
    //  //iniciar();
    //}
  });
  document.getElementById('palpite').addEventListener('input', function (e) {
    //if (tecla)
    //  console.log('alterou com tecla');
    //else
    if ((!tecla || !tkey) && !document.getElementById('enviar').disabled) {
      //console.log('alterou SEM tecla');
      document.getElementById('form_palpite').submit();
    }
    tecla = false;
    //if(e.key == "Enter"){
    //  console.log('Enter');
    //  //document.getElementById('form_palpite').submit();
    //  //document.getElementById('form_palpite').click();
    //  //iniciar();
    //}
  });
</script>

</html>