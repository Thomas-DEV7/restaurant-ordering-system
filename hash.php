<?php
$senha_digitada = '123456';
$hash = password_hash($senha_digitada, PASSWORD_DEFAULT);
echo "Sua senha (123456) criptografada é:<br>";
echo "<code>" . $hash . "</code>";
?>