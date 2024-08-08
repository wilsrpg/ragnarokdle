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
  } else if ($_SESSION['modo'] != 'monstro') {
    $_SESSION['mensagem'] = 'Já existe um jogo em andamento.';
    header('Location: index.php');
    die();
  }
}

//$URL_BASE = 'http://localhost/ragnarokdle-api/ragnarokdle-api/v1';
//$cookieFile = getcwd().'/cookies/cookie.txt';

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
$monstro = '';
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
  header('Location: ragnarokdle-monstros.php');
  die();
}

if (isset($_POST['novo'])) {
  if (isset($_POST['data']))
    $data = str_replace('-','',$_POST['data']);
  $dados = obter_dados('/jogo', ['modo'=>'monstro', 'data' => $data]);
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
    ['dica' => $dados['dicas'][1], 'revelada' => false, 'durante_o_jogo' => false]
  ];
  $dicas = $_SESSION['dicas'];
  header('Location: ragnarokdle-monstros.php');
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
    $monstro = $dados;
    array_push($_SESSION['palpites'], $monstro);
    array_unshift($palpites, $monstro);
    header('Location: ragnarokdle-monstros.php');
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

$nomes_dos_monstros_palpitados = array_map(function($p) {return $p['nome'];}, $palpites);
$nomes_restantes = array_diff($nomes, $nomes_dos_monstros_palpitados);

if (isset($monstro['id_r']) && $monstro['id_r'] === 1) {
  $descobriu = true;
  $_SESSION["descobriu"] = true;
  $erro = 'Parabéns! Você descobriu o monstro!';
}
?>

<!DOCTYPE html>
<html lang="pt-br"> 
  <head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="favicon.svg"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ragnarökdle - Monstros</title>
  </head>
<body>

<datalist id="monstros">
<?php
foreach ($nomes_restantes as $p)
 echo '<option value="'.$p.'"></option>';
?>
</datalist>

Ragnarökdle - Monstros<br>
Data do jogo: <?php echo $seed; ?><br>

<form action="ragnarokdle-monstros.php" method="POST">
  <input type="submit" name="voltar" value="Voltar">
</form>

<form id="form_palpite" action="ragnarokdle-monstros.php" method="POST" style="margin: 0.5rem 0;">
  <label for="palpite">Monstro:</label><br>
  <input id="palpite" list="monstros" name="palpite" autofocus autocomplete="off" />
  <input id="enviar" type="submit" <?php if ($descobriu) echo 'disabled'; ?> value="Enviar">
</form>
<?php echo $erro; ?>
<br>
<br>

Palpites: <?php echo count($palpites); ?>
<br>Dicas reveladas durante o jogo:
<?php
  echo ($dicas[0]['durante_o_jogo'] ? 'mapa' : '')
    . ($dicas[0]['durante_o_jogo'] && $dicas[1]['durante_o_jogo'] ? ', ' : '')
    . ($dicas[1]['durante_o_jogo'] ? 'drop' : '')
    . (!$dicas[0]['durante_o_jogo'] && !$dicas[1]['durante_o_jogo'] ? 'nenhuma' : '');
?>
<form action="ragnarokdle-monstros.php" method="POST">
<?php
  if (!$dicas[0]['revelada']){
    if (count($palpites) < $qtde_palpites_pra_revelar_dica_1 && !$descobriu)
      echo '<button disabled>Revelar primeira letra de um mapa onde spawna em '
        .($qtde_palpites_pra_revelar_dica_1 - count($palpites))
        .' palpites</button>';
    else
      echo '<button type="submit" name="dica" value="'. 0 .'">Revelar primeira letra de um mapa onde spawna</button>';
  } else if ($_SESSION['dicas'][0]['dica'])
    echo 'Nome do mapa'
      .($descobriu ? ': '.$_SESSION['dicas'][0]['dica'][0]->mapname
        : ' começa com: "'.substr($_SESSION['dicas'][0]['dica'][0]->mapname, 0, 1).'"');
  else
    echo 'Nome do mapa: não spawna naturalmente.';
  echo '<br>';
  if (!$dicas[1]['revelada']){
    if (count($palpites) < $qtde_palpites_pra_revelar_dica_2 && !$descobriu)
      echo '<button disabled>Revelar item com maior chance de drop em '
        .($qtde_palpites_pra_revelar_dica_2 - count($palpites))
        .' palpites</button>';
    else
      echo '<button type="submit" name="dica" value="'. 1 .'">Revelar item com maior chance de drop</button>';
  } else if ($_SESSION['dicas'][1]['dica']->id)
    echo 'Item com maior chance de drop: '.$_SESSION['dicas'][1]['dica']->nome.' ('.($_SESSION['dicas'][1]['dica']->chance/100).'%)';
  else
    echo 'Item com maior chance de drop: não dropa nenhum item.';
?>
</form>

<table>
<tr>
  <th></th>
  <th>Nome</th>
  <th>Nível</th>
  <th>Raça</th>
  <th>Tamanho</th>
  <th>Propriedade</th>
  <th>Nv. prop.</th>
</tr>

<?php
foreach($palpites as $pp) {
  $pp = (object) $pp;
  echo '
  <tr>
    <td style="float: right;"><img title="'.$pp->id.'" src="https://db.irowiki.org/image/monster/'.$pp->id.'.png"</td>
    <td style="background-color: '.($pp->nome_r ? 'lime' : 'red').';">'
    .$pp->nome.'</td>
    <td style="background-color: '.($pp->nivel_r === 1 ? 'lime' : 'red').';">'
    .($pp->nivel_r === 2 ? '<' : ($pp->nivel_r === 0 ? '>' : '')).($pp->nivel).'</td>
    <td style="background-color: '.($pp->raca_r ? 'lime' : 'red').';">'
    .$pp->raca.'</td>
    <td style="background-color: '.($pp->tamanho_r ? 'lime' : 'red').';">'
    .$pp->tamanho.'</td>
    <td style="background-color: '.($pp->propriedade_r ? 'lime' : 'red').';">'
    .$pp->propriedade.'</td>
    <td style="background-color: '.($pp->nivel_prop_r === 1 ? 'lime' : 'red').';">'
    .$pp->nivel_prop.'</td>
  </tr>
  ';
}
?>
</table>

<?php
//if ($descobriu && isset($_POST['palpite']))
//  echo "<script>alert('Parabéns! Você descobriu o monstro!')</script>";
?>

</body>

<script>
  let alterou,tecla,tkey;
  document.getElementById('palpite').addEventListener('keydown', function (e) {
    tkey = e.key;
    if ((e.keyCode >= 33 && e.keyCode <= 40) || e.keyCode == 13)
      tecla = false;
    else
      tecla = true;
  });
  document.getElementById('palpite').addEventListener('click', function (e) {
    //console.log('clicou');
    tecla = false;
    //console.log(tecla);
  });
  document.getElementById('palpite').addEventListener('input', function (e) {
    //console.log(tecla);
    if ((!tecla || !tkey) && !document.getElementById('enviar').disabled)
      document.getElementById('form_palpite').submit();
    tecla = false;
  });
</script>

</html>