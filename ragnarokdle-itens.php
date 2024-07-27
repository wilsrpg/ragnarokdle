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
  } else if ($_SESSION['modo'] != 'tecnica') {
    $_SESSION['mensagem'] = 'Já existe um jogo em andamento.';
    header('Location: index.php');
    die();
  }
}

$URL_BASE = 'http://localhost/pokedle-api/pokedle-moves-api/v1';
//$URL_BASE = 'https://wilsrpg.42web.io/pokedle-api/pokedle-moves-api/v1';
//$URL_BASE = 'http://wilsrpg.unaux.com/pokedle-moves-api/v1';
//$URL_BASE = 'https://wilsrpg.x10.mx/pokedle-moves-api/v1';
$TIMEOUT = 15;
$cookieFile = getcwd().'/cookies/cookie.txt';

$seed = 0;
$geracoes = '';
$geracao_contexto = '';
$palpites = [];
$tecnicas = [];
$descobriu = false;

$palpite = '';
$erro = '';
$tecnica = '';
$nomes = [];

if (isset($_SESSION['seed']))
  $seed = $_SESSION['seed'];
if (isset($_SESSION['palpites']))
  $palpites = array_reverse($_SESSION['palpites']);
if (isset($_SESSION['tecnicas']))
  $tecnicas = $_SESSION['tecnicas'];
if (isset($_SESSION['descobriu']))
  $descobriu = $_SESSION['descobriu'];
if (isset($_SESSION['geracoes']))
  $geracoes = $_SESSION['geracoes'];
if (isset($_SESSION['geracao_contexto']))
  $geracao_contexto = $_SESSION['geracao_contexto'];

if(isset($_POST['novo'])) {
  $geracoes = $_POST['geracoes'];
  if (isset($_POST['geracao_contexto']))
    $geracao_contexto = $_POST['geracao_contexto'];
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $URL_BASE.'/jogo',
    CURLOPT_POST => 2,
    CURLOPT_POSTFIELDS => ['geracoes' => $geracoes, 'geracao_contexto' => $geracao_contexto],
    CURLOPT_TIMEOUT => $TIMEOUT,
    //CURLOPT_COOKIE => 'PHPSESSID='.$_COOKIE['PHPSESSID']
  ]);
  $response = json_decode(curl_exec($curl));
  curl_close($curl);
  if (!$response) {
    $_SESSION['mensagem'] = 'Erro na comunicação com o servidor: '.curl_error($curl);
    header('Location: index.php');
    die();
  }
  else if (isset($response->erro)) {
    $_SESSION['mensagem'] = $response->erro;
    header('Location: index.php');
    die();
  }

  $_SESSION['seed'] = $response->seed;
  $_SESSION['modo'] = $response->modo;
  $_SESSION['geracoes'] = $response->geracoes;
  $_SESSION['geracao_contexto'] = $response->geracao_contexto;
  $seed = $_SESSION['seed'];
  $palpites = [];
  $tecnicas = [];
  $descobriu = false;
  unset($_SESSION['palpites']);
  unset($_SESSION['tecnicas']);
  unset($_SESSION['descobriu']);
}

if (empty($_SESSION['tecnicas'])) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $URL_BASE.'/tecnicas',
    CURLOPT_TIMEOUT => $TIMEOUT,
    //CURLOPT_COOKIE => 'PHPSESSID='.$_COOKIE['PHPSESSID']
  ]);
  $response = json_decode(curl_exec($curl));
  curl_close($curl);

  if (!$response) {
    $_SESSION['mensagem'] = 'Erro na comunicação com o servidor: '.curl_error($curl);
    header('Location: index.php');
    die();
  }
  else if (isset($response->erro)) {
    $_SESSION['mensagem'] = $response->erro;
    header('Location: index.php');
    die();
  }
  $_SESSION['tecnicas'] = $response->nomes_das_tecnicas_das_geracoes_selecionadas;
  sort($_SESSION['tecnicas']);
  $tecnicas = $_SESSION['tecnicas'];
}

if (isset($_POST['palpite']) && $_SESSION['descobriu'] == false) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $URL_BASE.'/palpites',
    CURLOPT_POST => 1,
    CURLOPT_POSTFIELDS => ['palpite' => $_POST['palpite']],
    CURLOPT_TIMEOUT => $TIMEOUT,
    //CURLOPT_COOKIE => 'PHPSESSID='.$_COOKIE['PHPSESSID']
  ]);
  $response = json_decode(curl_exec($curl));
  curl_close($curl);

  if (!$response)
    $erro = 'Erro na comunicação com o servidor: '.curl_error($curl);
  else if (isset($response->erro))
    $erro = $response->erro;
  else {
    $tecnica = $response;
    array_push($_SESSION['palpites'], $tecnica);
    array_unshift($palpites, $tecnica);
  }
}

if (empty($_SESSION['palpites'])) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $URL_BASE.'/palpites',
    CURLOPT_TIMEOUT => $TIMEOUT,
    //CURLOPT_COOKIE => 'PHPSESSID='.$_COOKIE['PHPSESSID']
  ]);
  $response = json_decode(curl_exec($curl));
  curl_close($curl);

  if (!$response) {
    $_SESSION['mensagem'] = 'Erro na comunicação com o servidor: '.curl_error($curl);
    header('Location: index.php');
    die();
  }
  else if (isset($response->erro)) {
    $_SESSION['mensagem'] = $response->erro;
    header('Location: index.php');
    die();
  }
  $_SESSION['palpites'] = $response->palpites;
  $palpites = array_reverse($_SESSION['palpites']);
}

if (empty($_SESSION['descobriu'])) {
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_COOKIEJAR, $cookieFile);  //tell cUrl where to write cookie data
  curl_setopt($curl, CURLOPT_COOKIEFILE, $cookieFile); //tell cUrl where to read cookie data from
  curl_setopt_array($curl, [
    CURLOPT_RETURNTRANSFER => 1,
    CURLOPT_URL => $URL_BASE.'/jogo',
    CURLOPT_TIMEOUT => $TIMEOUT,
    //CURLOPT_COOKIE => 'PHPSESSID='.$_COOKIE['PHPSESSID']
  ]);
  $response = json_decode(curl_exec($curl));
  curl_close($curl);

  if (!$response) {
    $_SESSION['mensagem'] = 'Erro na comunicação com o servidor: '.curl_error($curl);
    header('Location: index.php');
    die();
  }
  else if (isset($response->erro)) {
    $_SESSION['mensagem'] = $response->erro;
    header('Location: index.php');
    die();
  }
  $_SESSION['descobriu'] = $response->descobriu;
  $_SESSION['geracoes'] = $response->geracoes;
  $_SESSION['geracao_contexto'] = $response->geracao_contexto;
  $descobriu = $_SESSION['descobriu'];
  $geracoes = $_SESSION['geracoes'];
  $geracao_contexto = $_SESSION['geracao_contexto'];
}

$nomes_das_tecnicas_palpitadas = array_map(function($p) {return $p->nome;}, $palpites);
$nomes = array_diff($tecnicas, $nomes_das_tecnicas_palpitadas);

if (isset($tecnica->id_r) && $tecnica->id_r === 1) {
  $descobriu = true;
  $_SESSION["descobriu"] = true;
  $erro = 'Parabéns! Você descobriu a técnica!';
}
?>

<!DOCTYPE html>
<html lang="pt-br"> 
  <head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="favicon.svg"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pokédle+Gerações: Técnicas</title>
  </head>
<body>

<datalist id="tecnicas">
<?php
foreach ($nomes as $p)
 echo '<option value="'.$p.'"></option>';
?>
</datalist>

Pokédle+: Técnicas<br>
seed: [<?php echo $seed; ?>], gerações: [<?php echo implode(',', $geracoes); ?>], contexto: [<?php echo $geracao_contexto; ?>ª geração]<br>

<form action="pokedle-moves.php" method="POST">
  <input type="submit" name="voltar" value="Voltar">
</form>

<label for="palpite">Técnica:</label><br>
<form action="pokedle-moves.php" method="POST" style="margin: 0.5rem 0;">
<input list="tecnicas" id="palpite" name="palpite" autofocus autocomplete="off"/>
<input type="submit" <?php if ($descobriu) echo 'disabled'; ?> value="Enviar">
</form>
<?php echo $erro; ?>
<br>
<br>

Palpites: <?php echo count($palpites); ?>
<br>

<table>
<tr>
  <th>Nome</th>
  <th>Tipo</th>
  <th>Poder</th>
  <th>Precisão</th>
  <th>PP</th>
  <th>Categoria</th>
  <th>Afeta atributo</th>
  <th>Causa condição</th>
  <th>Cura o usuário</th>
  <th>Efeito único</th>
</tr>

<?php
foreach($palpites as $pp) {
  $pp = (object) $pp;
  echo '
  <tr>
    <td style="background-color: '.($pp->nome_r ? 'lime' : 'red').';">'
    .$pp->nome.'</td>
    <td style="background-color: '.($pp->tipo_r === 1 ? 'lime' : ($pp->tipo_r === 2 ? 'yellow' : 'red')).';">'
    .$pp->tipo.'</td>
    <td style="background-color: '.($pp->poder_r === 1 ? 'lime' : 'red').';">'
    .($pp->poder_r === 2 ? '<' : ($pp->poder_r === 0 ? '>' : '')).($pp->poder).'</td>
    <td style="background-color: '.($pp->precisao_r === 1 ? 'lime' : 'red').';">'
    .($pp->precisao_r === 2 ? '<' : ($pp->precisao_r === 0 ? '>' : '')).($pp->precisao).'</td>
    <td style="background-color: '.($pp->pp_r === 1 ? 'lime' : 'red').';">'
    .($pp->pp_r === 2 ? '<' : ($pp->pp_r === 0 ? '>' : '')).($pp->pp).'</td>
    <td style="background-color: '.($pp->categoria_r ? 'lime' : 'red').';">'
    .$pp->categoria.'</td>
    <td style="background-color: '.($pp->afeta_stat_r ? 'lime' : 'red').';">'
    .$pp->afeta_stat.'</td>
    <td style="background-color: '.($pp->causa_ailment_r ? 'lime' : 'red').';">'
    .$pp->causa_ailment.'</td>
    <td style="background-color: '.($pp->cura_usuario_r ? 'lime' : 'red').';">'
    .$pp->cura_usuario.'</td>
    <td style="background-color: '.($pp->efeito_unico_r ? 'lime' : 'red').';">'
    .$pp->efeito_unico.'</td>
  </tr>
  ';
}
?>
</table>

<?php
//if ($descobriu && isset($_POST['palpite']))
//  echo "<script>alert('Parabéns! Você descobriu a técnica!')</script>";
?>

</body>
</html>