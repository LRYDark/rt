<?php
// Connexion à la base de données (ajustez selon votre configuration)
$connection = new PDO('mysql:host=localhost;dbname=glpi', 'username', 'password');

$champ1 = $_POST['champ1'];
$champ2 = $_POST['champ2'];

// Assurez-vous de traiter correctement les données reçues pour éviter les injections SQL
$query = $connection->prepare("INSERT INTO votre_table (colonne1, colonne2) VALUES (:champ1, :champ2)");
$query->bindParam(':champ1', $champ1);
$query->bindParam(':champ2', $champ2);
$query->execute();

echo "Insertion réussie!";
?>