<?php
require 'vendor/autoload.php';
session_start();

if (isset($_POST['voltar'])) {
  header('Location: index.php');
  die();
}

if (empty($_POST['novo'])) {
  //if (isset($_SESSION['seed']) && $_SESSION['seed'] != date("Ymd")) {
  //  unset($_SESSION);
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

$seed = 0;
$palpites = [];
$nomes = [];
$dicas = [false, false];
$descobriu = false;
$qtde_palpites_pra_revelar_dica_1 = 7;
$qtde_palpites_pra_revelar_dica_2 = 12;

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
if (isset($_SESSION['dicas_reveladas']))
  $dicas = $_SESSION['dicas_reveladas'];
if (isset($_SESSION['descobriu']))
  $descobriu = $_SESSION['descobriu'];

if (isset($_POST['dica'])) {
  $n = $_POST['dica'];
  //var_dump($n);
  if (isset($_SESSION['dicas'][$n])) {
    $_SESSION['dicas_reveladas'][$n] = ['durante_o_jogo' => !$descobriu];
    $dicas = $_SESSION['dicas_reveladas'];
  }
}

if (isset($_POST['novo'])) {
  $dados = obter_dados('/jogo', ['modo'=>'monstro']);
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
  $_SESSION['dicas'] = $dados['dicas'];
  $seed = $_SESSION['seed'];
  $palpites = [];
  $nomes = [];
  $descobriu = false;
  $_SESSION['dicas_reveladas'] = [false, false];
  $dicas = $_SESSION['dicas_reveladas'];
  unset($_SESSION['palpites']);
  unset($_SESSION['nomes']);
  unset($_SESSION['descobriu']);

  unset($_SESSION['ids']);
  unset($_SESSION['sprites']);
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
  //$_SESSION['ids'] = $dados['ids_dos_monstros'];
  $_SESSION['nomes'] = $dados['nomes'];
  //$collator = collator_create('pt-BR');
  //collator_sort($collator, $_SESSION['nomes']);
  setlocale(LC_COLLATE, 'pt_BR', 'pt_BR.utf-8');
  sort($_SESSION['nomes']);
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
seed: [<?php echo $seed; ?>]<br>

<form action="ragnarokdle-monstros.php" method="POST">
  <input type="submit" name="voltar" value="Voltar">
</form>

<form action="ragnarokdle-monstros.php" method="POST" id="form_palpite" style="margin: 0.5rem 0;">
  <label for="palpite">Monstro:</label><br>
  <input id="palpite" list="monstros" name="palpite" autofocus autocomplete="off" />
  <input id="enviar" type="submit" <?php if ($descobriu) echo 'disabled'; ?> value="Enviar">
</form>
<?php echo $erro; ?>
<br>

<br>Palpites: <?php echo count($palpites); ?>
<br>Dicas reveladas durante o jogo:
<?php
  echo ($dicas[0] && $dicas[0]['durante_o_jogo'] ? 'mapa' : '')
    . ($dicas[0] && $dicas[0]['durante_o_jogo'] && $dicas[1] && $dicas[1]['durante_o_jogo'] ? ', ' : '')
    . ($dicas[1] && $dicas[1]['durante_o_jogo'] ? 'item' : '')
    . (!$dicas[0] && !$dicas[1] ? 'Nenhuma' : '');
?>
<form action="ragnarokdle-monstros.php" method="POST">
<?php
//var_dump($_SESSION['dicas']);
//var_dump($dicas);
//if (count($dicas) > 0)
  //for ($i=0; $i < count($dicas); $i++) {
    //$i = $seed % count($dicas);
    if (!$dicas[0]){
      if (count($palpites) < $qtde_palpites_pra_revelar_dica_1 && !$descobriu)
        echo '<button disabled>Revelar primeira letra do mapa em '
          .($qtde_palpites_pra_revelar_dica_1 - count($palpites))
          .' palpites</button>';
      else
        echo '<button type="submit" name="dica" value="'. 0 .'">Revelar primeira letra do mapa</button>';
    } else if (isset($_SESSION['dicas'][0][0]))
      echo 'Nome do mapa'
        .($descobriu ? ': '.$_SESSION['dicas'][0][0]->mapname
          : ' começa com: "'.substr($_SESSION['dicas'][0][0]->mapname, 0, 1).'"');
    else
      echo 'Não spawna naturalmente.';
  //}
  echo '<br>';
  if (!$dicas[1]){
    if (count($palpites) < $qtde_palpites_pra_revelar_dica_2 && !$descobriu)
      echo '<button disabled>Revelar item com maior chance de drop em '
        .($qtde_palpites_pra_revelar_dica_2 - count($palpites))
        .' palpites</button>';
    else
      echo '<button type="submit" name="dica" value="'. 1 .'">Revelar item com maior chance de drop</button>';
  } else if (isset($_SESSION['dicas'][1]->id))
    echo 'Item com maior chance de drop: '.$_SESSION['dicas'][1]->nome.' ('.($_SESSION['dicas'][1]->chance/100).'%)';
  else
    echo 'Não dropa nenhum item.';
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
  let alterou,tecla;
  document.getElementById('palpite').addEventListener('keydown', function (e) {
    //if(e.repeat)
    //  return;
    //console.log(e.keyCode);
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
    if (!tecla && !document.getElementById('enviar').disabled) {
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