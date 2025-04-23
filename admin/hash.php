<?php
echo password_hash("admin123", PASSWORD_DEFAULT)."\n"."<br>";//administrador, Cristian Coronado
echo password_hash("auditor123", PASSWORD_DEFAULT)."\n"."<br>";//auditor, Eduard Escamilla


?>
<?php
$password = "TuContraseña123"; // Reemplaza esto con la contraseña que desees
$hashed = password_hash($password, PASSWORD_DEFAULT);
echo $hashed;
?>
