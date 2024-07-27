<?php
//header('Access-Control-Allow-Origin: *');
//date_default_timezone_set('America/Sao_Paulo');
//echo session_id();
require 'vendor/autoload.php';
session_start();

if(isset($_POST['continuar'])) {
  if ($_SESSION['jogo'] == 'monstros')
    header('Location: ragnarokdle-monstros.php');
  else if ($_SESSION['jogo'] == 'itens')
    header('Location: ragnarokdle-itens.php');
  die();
}

if(isset($_POST['excluir'])) {
  unset($_SESSION);
}
?>

<!DOCTYPE html>
<html lang="pt-br">
  <head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/svg+xml" href="favicon.svg"/>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ragnarökdle</title>
  </head>
<body>

Ragnarökdle<br>

<form action="index.php" method="POST">
  <input type="submit" name="continuar" <?php if (empty($_SESSION['seed'])) echo 'disabled'?> value="Continuar jogo anterior">
  <input type="submit" name="excluir" <?php if (empty($_SESSION['seed'])) echo 'disabled'?> value="Excluir jogo atual">
</form>

<form method="POST">
  <input type="submit" formaction="ragnarokdle-monstros.php" name="novo" value="Novo jogo - Monstros">
  <br>
  <input type="submit" formaction="ragnarokdle-itens.php" name="novo" value="Novo jogo - Itens">
</form>

<?php
if (!empty($_SESSION['mensagem'])) {
  echo $_SESSION['mensagem'];
  //echo "<script>alert('{$_SESSION['mensagem']}')</script>";
  unset($_SESSION['mensagem']);
}



function obter_dados($id, $post=[]) {
  $ch = curl_init();

  $URL_BASE = 'https://www.divine-pride.net/api/database/Monster/';
  $API_KEY = '?apiKey=7e9552d32c9990d74dd961c53f1a6eed';
  $IDIOMA = '&server=bRO';

  curl_setopt($ch, CURLOPT_URL, $URL_BASE.$id.$API_KEY.$IDIOMA);
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
  return json_decode($dados);
}

for ($i=1; $i <= 50; $i++) {
  $dados = obter_dados(1000+$i);
  echo $dados->id.': '.$dados->name.'<br>';
  //echo $dados;
}
?>

</body>
</html>