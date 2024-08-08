<?php
//header('Access-Control-Allow-Origin: *');
date_default_timezone_set('America/Sao_Paulo');
//echo session_id();
require 'vendor/autoload.php';
if (session_status() === 1)
  session_start();
$seed = (int) date("Ymd");

if(isset($_SESSION['seed']) && $_SESSION['seed'] != date("Ymd"))
  $_SESSION = [];

if(isset($_POST['continuar']) && isset($_SESSION['modo'])) {
  if ($_SESSION['modo'] == 'monstro')
    header('Location: ragnarokdle-monstros.php');
  else if ($_SESSION['modo'] == 'arma')
    header('Location: ragnarokdle-armas.php');
  die();
}

if(isset($_POST['excluir']))
  $_SESSION = [];
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
Ragnarökdle
<br><br>
<form id="jogo" method="POST" action="ragnarokdle-monstros.php">
  Selecione o modo de jogo:<br>
  <input type="radio" name="modo" id="monstro" value="monstro" onchange="atualizarModo()" checked autofocus />
  <label for="monstro">Monstro</label>
  <input type="radio" name="modo" id="item" value="item" onchange="atualizarModo()" />
  <label for="item">Item</label>
  <br><br>
  <input type="submit" name="novo" value="Iniciar novo jogo">
</form>

<form action="index.php" method="POST">
  <input type="submit" name="continuar" <?php if (empty($_SESSION['modo'])) echo 'disabled'?> value="Continuar jogo anterior">
  <br>
  <input type="submit" name="excluir" <?php if (empty($_SESSION['modo'])) echo 'disabled'?> value="Excluir jogo atual">
</form>


<?php
if (!empty($_SESSION['mensagem'])) {
  echo '<span style="color: red;">'.$_SESSION['mensagem'].'</span>';
  unset($_SESSION['mensagem']);
}
?>

</body>

<script>
  function atualizarModo() {
    if (document.getElementById('monstro').checked)
      document.getElementById('jogo').action = 'ragnarokdle-monstros.php';
    if (document.getElementById('item').checked)
      document.getElementById('jogo').action = 'ragnarokdle-armas.php';
  }

  function iniciar() {
    if (document.getElementById('monstro').checked)
      document.getElementById('jogo').action = 'ragnarokdle-monstros.php';
    if (document.getElementById('item').checked)
      document.getElementById('jogo').action = 'ragnarokdle-armas.php';
  }
</script>

</html>