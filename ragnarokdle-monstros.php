<?php
require 'vendor/autoload.php';
session_start();

if (isset($_POST['voltar'])) {
  header('Location: index.php');
  die();
}

if (empty($_POST['novo'])) {
  if (isset($_SESSION['seed']) && $_SESSION['seed'] != date("Ymd")) {
    unset($_SESSION);
    header('Location: index.php');
    die();
  }

  if (empty($_SESSION['modo'])) {
    header('Location: index.php');
    die();
  } else if ($_SESSION['modo'] != 'monstro') {
    $_SESSION['mensagem'] = 'Já existe um jogo em andamento.';
    header('Location: index.php');
    die();
  }
}

$URL_BASE = 'http://localhost/ragnarokdle-api/ragnarokdle-monstros-api/v1';
//$URL_BASE = 'https://wilsrpg.42web.io/pokedle-api/pokedle-api/v1';
//$URL_BASE = 'http://wilsrpg.unaux.com/pokedle-api/v1';
//$URL_BASE = 'https://wilsrpg.x10.mx/pokedle-api/v1';
$cookieFile = getcwd().'/cookies/cookie.txt';

function obter_dados($rota, $post=[]) {
  $ch = curl_init();

  curl_setopt($ch, CURLOPT_URL, $URL_BASE.$rota);
  curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 20);
  //curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  //curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  //curl_setopt($ch, CURLOPT_COOKIE, 'PHPSESSID='.$_COOKIE['PHPSESSID']);
  if ($post && is_array($post)) {
    curl_setopt($ch, CURLOPT_POST, count($post));
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
  }

  $dados = curl_exec($ch);
  $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  curl_close($ch);

  if ($http_code != 200 || !$dados) {
    return json_encode(['erro' => 'Erro na comunicação com o servidor: '.curl_error($ch)]);
  }
  return json_encode($dados);
}

$seed = 0;
$palpites = [];
$monstros = [];
$descobriu = false;

$palpite = '';
$erro = '';
$monstro = '';
$nomes = [];

if (isset($_SESSION['seed']))
  $seed = $_SESSION['seed'];
if (isset($_SESSION['palpites']))
  $palpites = array_reverse($_SESSION['palpites']);
if (isset($_SESSION['monstros']))
  $monstros = $_SESSION['monstros'];
if (isset($_SESSION['descobriu']))
  $descobriu = $_SESSION['descobriu'];

if(isset($_POST['novo'])) {
  $dados = obter_dados('/jogo');
  var_dump($dados);
  //echo '..postjogo<br>';
  //var_dump($dados);
  //exit;
  if (!$dados) {
    $_SESSION['mensagem'] = 'Erro na comunicação com o servidor.';
    header('Location: index.php');
    die();
  }
  else if (isset($dados->erro)) {
    $_SESSION['mensagem'] = $dados->erro;
    //var_dump($dados->erro);
    header('Location: index.php');
    die();
  }

  $_SESSION['seed'] = $dados->seed;
  $_SESSION['modo'] = $dados->modo;
  $seed = $_SESSION['seed'];
  $palpites = [];
  $monstros = [];
  $descobriu = false;
  unset($_SESSION['palpites']);
  unset($_SESSION['monstros']);
  unset($_SESSION['descobriu']);
  unset($_SESSION['ids']);
  unset($_SESSION['sprites']);
}

if (empty($_SESSION['monstros'])) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  //curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
  curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $URL_BASE.'/monstros',
    CURLOPT_TIMEOUT => $TIMEOUT,
    //CURLOPT_COOKIE => 'PHPSESSID='.$_COOKIE['PHPSESSID']
  ]);
  $dados = json_decode(curl_exec($curl));
  curl_close($curl);
  //var_dump($dados);exit;

  if (!$dados) {
    $_SESSION['mensagem'] = 'Erro na comunicação com o servidor: '.curl_error($curl);
    header('Location: index.php');
    die();
  }
  else if (isset($dados->erro)) {
    $_SESSION['mensagem'] = $dados->erro;
    header('Location: index.php');
    die();
  }
  $_SESSION['ids'] = $dados->ids_dos_monstros_das_geracoes_selecionadas;
  $_SESSION['monstros'] = $dados->nomes_dos_monstros_das_geracoes_selecionadas;
  $_SESSION['sprites'] = $dados->urls_dos_sprites_dos_monstros_das_geracoes_selecionadas;
  $monstros = $_SESSION['monstros'];
}

if (isset($_POST['palpite']) && $_SESSION['descobriu'] == false) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  //curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
  curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $URL_BASE.'/palpites',
    CURLOPT_POST => 1,
    CURLOPT_POSTFIELDS => ['palpite' => $_POST['palpite']],
    CURLOPT_TIMEOUT => $TIMEOUT,
    //CURLOPT_COOKIE => 'PHPSESSID='.$_COOKIE['PHPSESSID']
  ]);
  $dados = json_decode(curl_exec($curl));
  curl_close($curl);
  //var_dump($dados);exit;

  if (!$dados)
    $erro = 'Erro na comunicação com o servidor: '.curl_error($curl);
  else if (isset($dados->erro))
    $erro = $dados->erro;
  else {
    $monstro = $dados;
    array_push($_SESSION['palpites'], $monstro);
    array_unshift($palpites, $monstro);
  }
}

if (empty($_SESSION['palpites'])) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  //curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
  curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $URL_BASE.'/palpites',
    CURLOPT_TIMEOUT => $TIMEOUT,
    //CURLOPT_COOKIE => 'PHPSESSID='.$_COOKIE['PHPSESSID']
  ]);
  $dados = json_decode(curl_exec($curl));
  //var_dump($dados);
  //echo '..getpalpites<br>';
  curl_close($curl);
  //var_dump($dados);exit;

  if (!$dados) {
    $_SESSION['mensagem'] = 'Erro na comunicação com o servidor: '.curl_error($curl);
    header('Location: index.php');
    //echo 'errinho';
    die();
  }
  else if (isset($dados->erro)) {
    $_SESSION['mensagem'] = $dados->erro;
    //echo $dados->erro;
    //echo $_COOKIE['PHPSESSID'];
    header('Location: index.php');
    die();
    //exit;
  }
  $_SESSION['palpites'] = $dados->palpites;
  $palpites = array_reverse($_SESSION['palpites']);
  //var_dump($palpites);
}

if (empty($_SESSION['descobriu'])) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  //curl_setopt($curl, CURLOPT_SSLVERSION, CURL_SSLVERSION_TLSv1_2);
  curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $URL_BASE.'/jogo',
    CURLOPT_TIMEOUT => $TIMEOUT,
    //CURLOPT_COOKIE => 'PHPSESSID='.$_COOKIE['PHPSESSID']
  ]);
  $dados = json_decode(curl_exec($curl));
  //var_dump($dados);
  //echo '..getjogo<br>';
  curl_close($curl);

  if (!$dados) {
    $_SESSION['mensagem'] = 'Erro na comunicação com o servidor: '.curl_error($curl);
    header('Location: index.php');
    die();
  }
  else if (isset($dados->erro)) {
    $_SESSION['mensagem'] = $dados->erro;
    header('Location: index.php');
    die();
  }
  $_SESSION['descobriu'] = $dados->descobriu;
  $_SESSION['geracoes'] = $dados->geracoes;
  $_SESSION['geracao_contexto'] = $dados->geracao_contexto;
  //if (isset($dados->descobriu))
    $descobriu = $_SESSION['descobriu'];
    //$geracoes = implode(',', $_SESSION['geracoes']);
    $geracoes = $_SESSION['geracoes'];
    $geracao_contexto = $_SESSION['geracao_contexto'];
}

$nomes_dos_monstros_palpitados = array_map(function($p) {return $p->nome;}, $palpites);
$nomes = array_diff($monstros, $nomes_dos_monstros_palpitados);

if (isset($monstro->id_r) && $monstro->id_r === 1) {
  $descobriu = true;
  $_SESSION["descobriu"] = true;
  $erro = 'Parabéns! Você descobriu o pokémon!';
}
?>

<!DOCTYPE html>
<html lang="pt-br"> 
  <head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="favicon.svg"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pokédle+Gerações</title>
  </head>
<body>

<datalist id="monstros">
<?php
foreach ($nomes as $p)
 echo '<option value="'.$p.'"></option>';
?>
</datalist>

Pokédle+<br>
seed: [<?php echo $seed; ?>], gerações: [<?php echo implode(',', $geracoes); ?>], contexto: [<?php echo $geracao_contexto; ?>ª geração]<br>

<form action="pokedle.php" method="POST">
  <input type="submit" name="voltar" value="Voltar">
</form>

<label for="palpite">Pokémon:</label><br>
<form action="pokedle.php" method="POST" style="margin: 0.5rem 0;">
<input list="monstros" id="palpite" name="palpite" autofocus autocomplete="off"/>
<input type="submit" <?php if ($descobriu) echo 'disabled'; ?> value="Enviar">
</form>
<?php echo $erro; ?>
<br>
<br>

Palpites: <?php echo count($palpites); ?>
<br>

<table>
<tr>
  <th></th>
  <th>Nome</th>
  <th>Tipo 1</th>
  <th>Tipo 2</th>
  <th>Cor principal</th>
  <th>Evoluído</th>
  <th>Altura</th>
  <th>Peso</th>
</tr>

<?php
foreach($palpites as $pp) {
  $pp = (object) $pp;
  echo '
  <tr>
    <td><img src="'.$_SESSION['sprites'][array_search($pp->id,$_SESSION['ids'])].'"</td>
    <td style="background-color: '.($pp->nome_r ? 'lime' : 'red').';">'
    .$pp->nome.'</td>
    <td style="background-color: '.($pp->tipo1_r === 1 ? 'lime' : ($pp->tipo1_r === 2 ? 'yellow' : 'red')).';">'
    .$pp->tipo1.'</td>
    <td style="background-color: '.($pp->tipo2_r === 1 ? 'lime' : ($pp->tipo2_r === 2 ? 'yellow' : 'red')).';">'
    .$pp->tipo2.'</td>
    <td style="background-color: '.($pp->cor_r ? 'lime' : 'red').';">'
    .$pp->cor.'</td>
    <td style="background-color: '.($pp->evoluido_r ? 'lime' : 'red').';">'
    .$pp->evoluido.'</td>
    <td style="background-color: '.($pp->altura_r === 1 ? 'lime' : 'red').';">'
    .($pp->altura_r === 2 ? '<' : ($pp->altura_r === 0 ? '>' : '')).($pp->altura).'m</td>
    <td style="background-color: '.($pp->peso_r === 1 ? 'lime' : 'red').';">'
    .($pp->peso_r === 2 ? '<' : ($pp->peso_r === 0 ? '>' : '')).($pp->peso).'kg</td>
  </tr>
  ';
}
?>
</table>

<?php
//if ($descobriu && isset($_POST['palpite']))
//  echo "<script>alert('Parabéns! Você descobriu o pokémon!')</script>";
?>

</body>
</html>